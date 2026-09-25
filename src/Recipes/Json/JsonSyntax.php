<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Json;

use Closure;
use Pico\Contracts\Parser;
use Pico\Exceptions\ParserException;
use Pico\Pico;

/**
 * Builds the RFC 8259 value production.
 *
 * @internal
 * @phpstan-type JsonValue array<array-key, *>|string|int|float|bool|null
 */
final class JsonSyntax
{
    /**
     * Memoized parsers.
     *
     * @var array<string, Parser<mixed>>
     */
    private static array $parsers = [];

    /**
     * JSON value parser.
     *
     * @return Parser<JsonValue>
     */
    public static function value(): Parser
    {
        $whitespace = self::whitespace();
        return self::memoize('value', static fn (): Parser => Pico::between(
            $whitespace,
            Pico::anyOf(
                self::string(),
                self::number(),
                // Arrays and objects require lazy() wrappers to handle recursive referencing.
                Pico::lazy(static fn (): Parser => self::array()),
                Pico::lazy(static fn (): Parser => self::object()),
                Pico::string('true')->map(static fn (): true => true),
                Pico::string('false')->map(static fn (): false => false),
                Pico::string('null')->map(static fn (): null => null),
            ),
            $whitespace,
        ));
    }

    /**
     * Creates a memoized parser.
     *
     * @template T
     * @param non-empty-string $key
     * @param Closure(): Parser<T> $init
     * @return Parser<T>
     */
    private static function memoize(string $key, Closure $init): Parser
    {
        if (!isset(self::$parsers[$key])) {
            self::$parsers[$key] = $init();
        }
        return self::$parsers[$key];
    }

    /**
     * JSON number parser.
     *
     * @return Parser<int|float>
     */
    private static function number(): Parser
    {
        // minus sign
        $minus = Pico::char('-')->optional();

        // integer part
        $integerPart = Pico::anyOf(
            Pico::char('0'),
            Pico::join(Pico::range('1', '9'), Pico::digit()->repeat()->join()),
        );

        // fraction part of float
        $frac = Pico::join(Pico::char('.'), Pico::digit()->repeat(min: 1)->join());

        // exponent part of float
        $exp = Pico::join(
            Pico::oneOf('eE'),
            Pico::oneOf('+-')->optional(),
            Pico::digit()->repeat(min: 1)->join(),
        );

        // float
        $float = Pico::anyOf(
            Pico::join($minus, $integerPart, $frac, $exp->optional()),
            Pico::join($minus, $integerPart, $exp),
        );

        // integer
        $integer = Pico::join($minus, $integerPart);

        // number = integer or float
        // Parse a float first because an integer parser would consume its integer part.
        return Pico::anyOf(
            $float->map(self::decodeFloat(...)),
            $integer->map(self::decodeInteger(...)),
        );
    }

    /**
     * Decode string to integer.
     *
     * @param string $input
     * @return float|int
     */
    private static function decodeInteger(string $input): float|int
    {
        $integer = filter_var($input, FILTER_VALIDATE_INT);
        return $integer === false ? self::decodeFloat($input) : $integer;
    }

    /**
     * Decode string to float.
     *
     * @param string $input
     * @return float
     */
    private static function decodeFloat(string $input): float
    {
        $float = (float)$input;
        if (!is_finite($float)) {
            throw new ParserException('The JSON number is outside the supported numeric range.');
        }
        return $float;
    }

    /**
     * JSON string parser.
     *
     * @return Parser<string>
     */
    private static function string(): Parser
    {
        // unescaped = %x20-21 / %x23-5B / %x5D-10FFFF
        //           = %x20-10FFFF except %x22 or %x5C
        $unescaped = Pico::range("\x20", "\u{10FFFF}")
            ->except(Pico::char("\x22"))
            ->except(Pico::char("\x5C"))
            ->repeat(min: 1)
            ->join();

        $simpleEscape = Pico::skipLeft(Pico::char('\\'), Pico::oneOf('"\\\\/bfnrt'))->map(fn (string $x): string => match ($x) {
            '"', '\\', '/' => $x,
            'b' => "\x08",
            'f' => "\x0C",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            default => throw new ParserException('The JSON string contains an invalid escape sequence.'),
        });
        $unicodeEscape = Pico::skipLeft(
            Pico::string('\\u'),
            Pico::regexp('[0-9A-Fa-f]{4}'),
        )->map(static function (string $codeUnit): int {
            return intval($codeUnit, 16);
        });
        $highSurrogate = $unicodeEscape->where(static function (int $codeUnit): bool {
            return $codeUnit >= 0xD800 && $codeUnit <= 0xDBFF;
        });
        $lowSurrogate = $unicodeEscape->where(static function (int $codeUnit): bool {
            return $codeUnit >= 0xDC00 && $codeUnit <= 0xDFFF;
        });
        $notSurrogate = $unicodeEscape->where(static function (int $codeUnit): bool {
            return $codeUnit < 0xD800 || $codeUnit > 0xDFFF;
        });
        $surrogatePair = Pico::seq($highSurrogate, $lowSurrogate)->map(static function (array $codeUnits): string {
            [$a, $b] = $codeUnits;
            return mb_chr(0x10000 + (($a - 0xD800) << 10) + ($b - 0xDC00), 'UTF-8');
        });
        $codeUnit = $notSurrogate->map(static fn (int $codeUnit): string => mb_chr($codeUnit, 'UTF-8'));
        $escaped = Pico::anyOf($simpleEscape, $surrogatePair, $codeUnit);

        return self::memoize('string', static fn (): Parser => Pico::between(
            Pico::char('"'),
            Pico::anyOf($unescaped, $escaped)->repeat()->join(),
            Pico::char('"'),
        ));
    }

    /**
     * JSON array parser.
     *
     * @return Parser<list<*>>
     */
    private static function array(): Parser
    {
        $emptyArray = Pico::seq(
            Pico::char('['),
            self::whitespace(),
            Pico::char(']'),
        );
        $nonEmptyArray = Pico::between(
            Pico::char('['),
            Pico::sepBy(self::value(), Pico::char(',')),
            Pico::char(']'),
        );
        return Pico::anyOf(
            $emptyArray->map(static fn (): array => []),
            $nonEmptyArray,
        );
    }

    /**
     * JSON object parser.
     *
     * @return Parser<array<string, *>>
     */
    private static function object(): Parser
    {
        $whitespace = self::whitespace();
        $objectMember = Pico::pair(
            Pico::between($whitespace, self::string(), $whitespace),
            self::value(),
            sep: Pico::char(':'),
        )->map(
            static fn (array $outputs): array => [$outputs[0] => $outputs[1]],
        );
        $emptyObject = Pico::seq(Pico::char('{'), $whitespace, Pico::char('}'));
        $nonEmptyObject = Pico::between(
            Pico::char('{'),
            Pico::sepBy($objectMember, Pico::char(','))->map(
                static fn (array $members): array => array_merge([], ...$members),
            ),
            Pico::char('}'),
        );
        return Pico::anyOf(
            $emptyObject->map(static fn (): array => []),
            $nonEmptyObject,
        );
    }

    /**
     * Whitespace parser.
     *
     * @return Parser<string>
     */
    private static function whitespace(): Parser
    {
        return self::memoize('whitespace', static function (): Parser {
            // whitespace = 0x20, horizontal tab = 0x09, LF = 0x0A, CR = 0x0D
            return Pico::oneOf("\x20\x09\x0A\x0D")->repeat()->skip();
        });
    }
}
