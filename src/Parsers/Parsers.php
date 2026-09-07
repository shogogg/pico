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

/** @internal */
final class Parsers
{
    /** @var array<string, ContextualParser<string>> */
    private static array $parsers = [];

    private function __construct()
    {
        // Nothing to do.
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
            private readonly Closure $parse;

            /** @param Closure(ParserInput): ParserResult<T> $parse */
            public function __construct(Closure $parse)
            {
                $this->parse = $parse;
            }

            /** @return ParserResult<T> */
            public function parseInput(ParserInput $input): ParserResult
            {
                return ($this->parse)($input);
            }
        };
    }

    /** @return ContextualParser<string> */
    public static function alpha(): ContextualParser
    {
        return self::memoize(
            'alpha',
            fn (): Parser => self::ascii()->where(ctype_alpha(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function alphaNum(): ContextualParser
    {
        return self::memoize(
            'alphaNum',
            static fn (): Parser => self::ascii()->where(ctype_alnum(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function anyChar(): ContextualParser
    {
        return self::memoize('anyChar', static fn (): ContextualParser => self::create(
            static function (ParserInput $input): ParserResult {
                return $input->isAtEnd() ? failure() : success($input->current(), 1);
            },
        ));
    }

    /** @return ContextualParser<string> */
    public static function ascii(): ContextualParser
    {
        return self::memoize('ascii', static fn (): ContextualParser => self::predicate(
            static fn (string $char): bool => strlen($char) === 1,
        ));
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

        return self::create(function (ParserInput $input) use ($char): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }

            return $input->current() === $char ? success($char, 1) : failure();
        });
    }

    /** @return ContextualParser<string> */
    public static function digit(): ContextualParser
    {
        return self::memoize(
            'digit',
            static fn (): Parser => self::ascii()->where(ctype_digit(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function eof(): ContextualParser
    {
        return self::memoize('eof', static fn (): ContextualParser => self::create(
            static fn (ParserInput $input): ParserResult => $input->isAtEnd() ? success('', 0) : failure(),
        ));
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
        if (!mb_check_encoding($characters, 'UTF-8')) {
            throw new ParserException('The character set must be valid UTF-8.');
        }
        if (mb_strlen($characters) === 0) {
            throw new ParserException('The character set must not be empty.');
        }
        $characterSet = array_fill_keys(mb_str_split($characters, 1, 'UTF-8'), true);

        return self::create(static function (ParserInput $input) use ($characterSet): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }

            $char = $input->current();
            return isset($characterSet[$char]) ? success($char, 1) : failure();
        });
    }

    /**
     * @param Closure(string): bool $predicate
     * @return ContextualParser<string>
     */
    public static function predicate(Closure $predicate): ContextualParser
    {
        return self::create(static function (ParserInput $input) use ($predicate): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }

            $char = $input->current();
            return $predicate($char) ? success($char, 1) : failure();
        });
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

        $minCodePoint = mb_ord($from, 'UTF-8');
        $maxCodePoint = mb_ord($to, 'UTF-8');

        if ($minCodePoint > $maxCodePoint) {
            throw new ParserException('The range start must not exceed the range end.');
        }

        return self::create(
            static function (ParserInput $input) use ($from, $to, $minCodePoint, $maxCodePoint): ParserResult {
                if ($input->isAtEnd()) {
                    return failure();
                }

                $char = $input->current();
                if ($char === $from || $char === $to) {
                    return success($char, 1);
                }

                $codePoint = mb_ord($char, 'UTF-8');

                return $codePoint >= $minCodePoint && $codePoint <= $maxCodePoint
                    ? success($char, 1)
                    : failure();
            },
        );
    }

    /** @return ContextualParser<string> */
    public static function regexp(string $pattern): ContextualParser
    {
        return new RegExpParser($pattern);
    }

    /** @return ContextualParser<string> */
    public static function string(string $expected): ContextualParser
    {
        if (!mb_check_encoding($expected, 'UTF-8')) {
            throw new ParserException('The expected string must be valid UTF-8.');
        }
        $length = mb_strlen($expected, 'UTF-8');

        return self::create(static function (ParserInput $input) use ($expected, $length): ParserResult {
            if ($expected === '') {
                return failure();
            }
            return $input->startsWith($expected)
                ? success($expected, $length)
                : failure();
        });
    }

    /** @return ContextualParser<string> */
    public static function whitespace(): ContextualParser
    {
        return self::memoize(
            'whitespace',
            static fn (): Parser => self::ascii()->where(ctype_space(...)),
        );
    }

    /** @return ContextualParser<string> */
    public static function whitespaces(): ContextualParser
    {
        return self::memoize(
            'whitespaces',
            static fn (): ContextualParser => self::regexp('[ \\t\\r\\n\\f\\v]+'),
        );
    }

    /**
     * @param non-empty-string $key
     * @param Closure(): Parser<string> $init
     * @return ContextualParser<string>
     */
    private static function memoize(string $key, Closure $init): ContextualParser
    {
        if (!isset(self::$parsers[$key])) {
            self::$parsers[$key] = PicoInternal::asContextualParser($init());
        }
        return self::$parsers[$key];
    }
}
