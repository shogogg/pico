<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico;

use Closure;
use Pico\Contracts\Parser;
use Pico\Parsers\AnyCharParser;
use Pico\Parsers\AnyOfParser;
use Pico\Parsers\CharParser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\OptionalParser;
use Pico\Parsers\PredicateParser;
use Pico\Parsers\RegExpParser;
use Pico\Parsers\RepeatParser;
use Pico\Parsers\SeqParser;
use Pico\Parsers\StringParser;

/**
 * Parser factory facade.
 */
final class Pico
{
    /**
     * Memoized parsers.
     *
     * @var array<string, Parser<mixed>>
     */
    private static array $parsers = [];

    /**
     * {@see Pico} constructor.
     */
    private function __construct()
    {
        // Nothing to do.
    }

    /**
     * Creates a memoized parser.
     *
     * @template T
     * @param string $key
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
     * Creates a parser that matches an ASCII character satisfying the predicate.
     *
     * @param string $key
     * @param Closure(string): bool $predicate
     * @return Parser<string>
     */
    private static function createAsciiParser(string $key, Closure $predicate): Parser
    {
        return self::memoize($key, static fn (): Parser => self::predicate(
            static fn (string $char): bool => strlen($char) === 1 && $predicate($char),
        ));
    }

    /**
     * Creates a parser that matches any ASCII alphabetic character.
     *
     * @return Parser<string>
     */
    public static function alpha(): Parser
    {
        return self::createAsciiParser('alpha', ctype_alpha(...));
    }

    /**
     * Creates a parser that matches any ASCII alphanumeric character.
     *
     * @return Parser<string>
     */
    public static function alphaNum(): Parser
    {
        return self::createAsciiParser('alphaNum', ctype_alnum(...));
    }

    /**
     * Creates a parser that matches any single character.
     *
     * @return Parser<string>
     */
    public static function anyChar(): Parser
    {
        return self::memoize(
            'anyChar',
            static fn (): Parser => new AnyCharParser(),
        );
    }

    /**
     * Creates a parser that matches the first successful parser.
     *
     * @template T
     * @param Parser<T> ...$parsers
     * @return Parser<T>
     */
    public static function anyOf(Parser ...$parsers): Parser
    {
        return new AnyOfParser(...$parsers);
    }

    /**
     * Creates a parser that matches any ASCII character.
     *
     * @return Parser<string>
     */
    public static function ascii(): Parser
    {
        return self::memoize(
            'ascii',
            static fn (): Parser => self::predicate(static fn (string $char): bool => strlen($char) === 1),
        );
    }

    /**
     * Creates a parser that matches the given character.
     *
     * @return Parser<string>
     */
    public static function char(string $char): Parser
    {
        return new CharParser($char);
    }

    /**
     * Creates a parser that matches any ASCII decimal digit.
     *
     * @return Parser<string>
     */
    public static function digit(): Parser
    {
        return self::createAsciiParser('digit', ctype_digit(...));
    }

    /**
     * Creates a parser that defers constructing a parser until parsing.
     *
     * @template T
     * @param Closure(): Parser<T> $factory
     * @return Parser<T>
     */
    public static function lazy(Closure $factory): Parser
    {
        return new LazyParser($factory);
    }

    /**
     * Creates a parser that makes the given parser optional.
     *
     * @template T
     * @param Parser<T> $parser
     * @return Parser<T|null>
     */
    public static function optional(Parser $parser): Parser
    {
        return new OptionalParser($parser);
    }

    /**
     * Creates a parser that matches a character satisfying the predicate.
     *
     * @param Closure(string): bool $predicate
     * @return Parser<string>
     */
    public static function predicate(Closure $predicate): Parser
    {
        return new PredicateParser($predicate);
    }

    /**
     * Creates a parser that repeatedly matches the given parser.
     *
     * @template T
     * @param Parser<T> $parser
     * @return Parser<list<T>>
     */
    public static function repeat(Parser $parser, int $min = 0, int $max = PHP_INT_MAX): Parser
    {
        return new RepeatParser($parser, $min, $max);
    }

    /**
     * Creates a parser that matches a regular expression at the current input offset.
     *
     * @return Parser<string>
     */
    public static function regexp(string $pattern): Parser
    {
        return new RegExpParser($pattern);
    }

    /**
     * Creates a parser that matches a sequence of parsers.
     *
     * @template T
     * @param Parser<T> ...$parsers
     * @return Parser<list<T>>
     */
    public static function seq(Parser ...$parsers): Parser
    {
        return new SeqParser(...$parsers);
    }

    /**
     * Creates a parser that matches the given string.
     *
     * @return Parser<string>
     */
    public static function string(string $expected): Parser
    {
        return new StringParser($expected);
    }

    /**
     * Creates a parser that matches any ASCII whitespace character.
     *
     * @return Parser<string>
     */
    public static function whitespace(): Parser
    {
        return self::createAsciiParser('whitespace', ctype_space(...));
    }

    /**
     * Creates a parser that matches one or more consecutive ASCII whitespace characters.
     *
     * @return Parser<string>
     */
    public static function whitespaces(): Parser
    {
        return self::memoize(
            'whitespaces',
            static fn (): Parser => self::regexp('[ \t\r\n\f\v]+'),
        );
    }
}
