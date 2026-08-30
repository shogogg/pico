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
use Pico\Parsers\PredicateParser;
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
}
