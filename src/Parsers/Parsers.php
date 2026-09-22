<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Closure;
use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;
use Pico\Internal\PicoInternal;

/**
 * Creates the primitive parsers used by the Pico facade.
 *
 * @internal
 */
final class Parsers
{
    /**
     * Memoized parsers.
     *
     * @var array<string, ContextualParser<string>>
     */
    private static array $parsers = [];

    private function __construct()
    {
        // Instantiation is not allowed.
    }

    /** @return ContextualParser<string> */
    public static function alpha(): ContextualParser
    {
        return self::memoize(
            'alpha',
            static fn (): Parser => self::charWhere(ctype_alpha(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function alphaNum(): ContextualParser
    {
        return self::memoize(
            'alphaNum',
            static fn (): Parser => self::charWhere(ctype_alnum(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function anyChar(): ContextualParser
    {
        return self::memoize(
            'anyChar',
            static fn (): ContextualParser => self::charWhere(static fn (): bool => true),
        );
    }

    /** @return ContextualParser<string> */
    public static function ascii(): ContextualParser
    {
        return self::memoize(
            'ascii',
            static fn (): ContextualParser => self::charWhere(static fn (string $x): bool => strlen($x) === 1),
        );
    }

    /** @return ContextualParser<string> */
    public static function char(string $char): ContextualParser
    {
        if (!mb_check_encoding($char, 'UTF-8')) {
            throw new ParserException('The character must be valid UTF-8.');
        }
        if (mb_strlen($char) !== 1) {
            throw new ParserException('The character must be exactly one character long.');
        }
        return self::charWhere(static fn (string $x): bool => $x === $char);
    }

    /**
     * @param Closure(string): bool $predicate
     * @return ContextualParser<string>
     */
    public static function charWhere(Closure $predicate): ContextualParser
    {
        return self::create(static function (ParserInput $input) use ($predicate): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }
            $char = $input->current();
            return $predicate($char) ? success($char, 1) : failure();
        });
    }

    /**
     * @internal
     * @template T
     * @param Closure(ParserInput): ParserResult<T> $parse
     * @return ContextualParser<T>
     */
    public static function create(Closure $parse): ContextualParser
    {
        /** @extends AbstractParser<T> */
        return new class ($parse) extends AbstractParser {
            /** @param Closure(ParserInput): ParserResult<T> $parse */
            public function __construct(private readonly Closure $parse)
            {
            }

            /** @return ParserResult<T> */
            public function parseInput(ParserInput $input): ParserResult
            {
                return ($this->parse)($input);
            }
        };
    }

    /** @return ContextualParser<string> */
    public static function digit(): ContextualParser
    {
        return self::memoize(
            'digit',
            static fn (): Parser => self::charWhere(ctype_digit(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function eof(): ContextualParser
    {
        return self::memoize(
            'eof',
            static fn (): ContextualParser => self::create(
                static fn (ParserInput $input): ParserResult => $input->isAtEnd()
                    ? success('', 0)
                    : failure(),
            ),
        );
    }

    /**
     * @template T
     * @param Closure(): Parser<T> $factory
     * @return ContextualParser<T>
     */
    public static function lazy(Closure $factory): ContextualParser
    {
        return new LazyParser($factory);
    }

    /** @return ContextualParser<string> */
    public static function oneOf(string $characters): ContextualParser
    {
        if ($characters === '') {
            return self::failure();
        }
        if (!mb_check_encoding($characters, 'UTF-8')) {
            throw new ParserException('The character set must be valid UTF-8.');
        }

        $characterSet = array_fill_keys(mb_str_split($characters, 1, 'UTF-8'), true);
        return self::charWhere(static fn (string $x): bool => isset($characterSet[$x]));
    }

    /** @return ContextualParser<string> */
    public static function range(string $from, string $to): ContextualParser
    {
        if (!mb_check_encoding($from, 'UTF-8') || !mb_check_encoding($to, 'UTF-8')) {
            throw new ParserException('Range bounds must be valid UTF-8.');
        }
        if (mb_strlen($from, 'UTF-8') !== 1 || mb_strlen($to, 'UTF-8') !== 1) {
            throw new ParserException('Range bounds must be exactly one UTF-8 character.');
        }

        $min = mb_ord($from, 'UTF-8');
        $max = mb_ord($to, 'UTF-8');

        if ($min > $max) {
            throw new ParserException('The range start must not exceed the range end.');
        }

        return self::charWhere(static function (string $x) use ($from, $to, $min, $max): bool {
            if ($x === $from || $x === $to) {
                return true;
            }
            $codePoint = mb_ord($x, 'UTF-8');
            return $codePoint >= $min && $codePoint <= $max;
        });
    }

    /** @return ContextualParser<string> */
    public static function regexp(string $pattern): ContextualParser
    {
        return new RegExpParser($pattern);
    }

    /**
     * @template T
     * @param Closure(Parser<T>): Parser<T> $definition
     * @return ContextualParser<T>
     */
    public static function recursive(Closure $definition): ContextualParser
    {
        return new RecursiveParser($definition);
    }

    /** @return ContextualParser<string> */
    public static function string(string $expected): ContextualParser
    {
        if ($expected === '') {
            return self::failure();
        }
        if (!mb_check_encoding($expected, 'UTF-8')) {
            throw new ParserException('The expected string must be valid UTF-8.');
        }

        $length = mb_strlen($expected, 'UTF-8');
        return self::create(static function (ParserInput $input) use ($expected, $length): ParserResult {
            return $input->startsWith($expected) ? success($expected, $length) : failure();
        });
    }

    /** @return ContextualParser<string> */
    public static function whitespace(): ContextualParser
    {
        return self::memoize(
            'whitespace',
            static fn (): Parser => self::charWhere(ctype_space(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function whitespaces(): ContextualParser
    {
        return self::memoize(
            'whitespaces',
            static fn (): ContextualParser => self::regexp("[ \t\r\n\f\v]+"),
        );
    }

    /** @return ContextualParser<never> */
    private static function failure(): ContextualParser
    {
        return self::memoize(
            'failure',
            static fn (): ContextualParser => self::create(static fn (): ParserResult => failure()),
        );
    }

    /**
     * @template T
     * @param non-empty-string $key
     * @param Closure(): Parser<T> $init
     * @return ContextualParser<T>
     */
    private static function memoize(string $key, Closure $init): ContextualParser
    {
        if (!isset(self::$parsers[$key])) {
            $parser = $init();
            assert($parser instanceof ContextualParser);
            self::$parsers[$key] = $parser;
        }
        return self::$parsers[$key];
    }
}
