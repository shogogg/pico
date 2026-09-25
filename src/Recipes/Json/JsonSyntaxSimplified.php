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
 * Builds a deliberately simplified JSON value production for learning.
 *
 * It retains JSON's recursive arrays, objects, whitespace, booleans, and null,
 * while omitting numeric fractions and exponents. Strings support only \" as an
 * escape sequence.
 *
 * @internal
 * @phpstan-type JsonValueSimplified array<array-key, *>|string|int|float|bool|null
 */
final class JsonSyntaxSimplified
{
    /**
     * Memoized parsers.
     *
     * @var array<string, Parser<mixed>>
     */
    private static array $parsers = [];

    /**
     * Whitespace parser.
     *
     * @return Parser<string>
     */
    public static function whitespace(): Parser
    {
        return self::memoize('whitespace', static function (): Parser {
            // whitespace = 0x20, horizontal tab = 0x09, LF = 0x0A, CR = 0x0D
            return Pico::oneOf("\x20\x09\x0A\x0D")->repeat()->skip();
        });
    }

    /**
     * Simplified JSON value parser.
     *
     * @return Parser<JsonValueSimplified>
     */
    public static function value(): Parser
    {
        return self::memoize('value', static fn (): Parser => Pico::between(
            self::whitespace(),
            Pico::anyOf(
                Pico::string('null')->map(static fn (): null => null),
                self::boolean(),
                self::number(),
                self::string(),
                // Arrays and objects require lazy() wrappers to handle recursive referencing.
                Pico::lazy(static fn (): Parser => self::array()),
                Pico::lazy(static fn (): Parser => self::object()),
            ),
            self::whitespace(),
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
     * JSON boolean parser.
     *
     * @return Parser<bool>
     */
    private static function boolean(): Parser
    {
        return self::memoize('boolean', static fn (): Parser => Pico::anyOf(
            Pico::string('true')->map(static fn (): bool => true),
            Pico::string('false')->map(static fn (): bool => false),
        ));
    }

    /**
     * JSON number parser.
     *
     * @return Parser<int|float>
     */
    private static function number(): Parser
    {
        return self::memoize('number', static function (): Parser {
            // minus sign
            $minus = Pico::char('-')->optional();

            // integer part
            $integerPart = Pico::anyOf(
                Pico::char('0'),
                Pico::join(Pico::range('1', '9'), Pico::digit()->repeat()->join()),
            );

            return Pico::join($minus, $integerPart)->map(intval(...));
        });
    }

    /**
     * JSON string parser.
     *
     * @return Parser<string>
     */
    private static function string(): Parser
    {
        $char = Pico::anyOf(
            Pico::string('\\"')->map(static fn (): string => '"'),
            Pico::anyChar()->except(Pico::char('"')),
        );

        return self::memoize('string', static fn (): Parser => Pico::between(
            Pico::char('"'),
            $char->repeat()->join(),
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
        return Pico::anyOf(
            Pico::seq(Pico::char('['), self::whitespace(), Pico::char(']'))->map(static fn (): array => []),
            Pico::between(Pico::char('['), Pico::sepBy(self::value(), Pico::char(',')), Pico::char(']')),
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

        $objectContent = Pico::sepBy($objectMember, Pico::char(','))->map(
            static fn (array $members): array => array_merge([], ...$members),
        );

        return Pico::anyOf(
            Pico::seq(Pico::char('{'), $whitespace, Pico::char('}'))->map(static fn (): array => []),
            Pico::between(Pico::char('{'), $objectContent, Pico::char('}')),
        );
    }
}
