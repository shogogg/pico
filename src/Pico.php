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
use Pico\Exceptions\ParserException;
use Pico\Internal\Combinators;
use Pico\Internal\CoreParsers;

/**
 * Parser factory facade.
 */
final class Pico
{
    /**
     * {@see Pico} constructor.
     */
    private function __construct()
    {
        // Nothing to do.
    }

    /**
     * Creates a parser that matches any ASCII alphabetic character.
     *
     * @return Parser<string>
     */
    public static function alpha(): Parser
    {
        return CoreParsers::alpha();
    }

    /**
     * Creates a parser that matches any ASCII alphanumeric character.
     *
     * @return Parser<string>
     */
    public static function alphaNum(): Parser
    {
        return CoreParsers::alphaNum();
    }

    /**
     * Creates a parser that matches any single character.
     *
     * @return Parser<string>
     */
    public static function anyChar(): Parser
    {
        return CoreParsers::anyChar();
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
        return Combinators::anyOf(...$parsers);
    }

    /**
     * Creates a parser that matches any ASCII character.
     *
     * @return Parser<string>
     */
    public static function ascii(): Parser
    {
        return CoreParsers::ascii();
    }

    /**
     * Creates a parser that matches content between opening and closing parsers.
     *
     * @template TOpen
     * @template TContent
     * @template TClose
     * @param Parser<TOpen> $open
     * @param Parser<TClose> $close
     * @param Parser<TContent> $content
     * @return Parser<TContent>
     */
    public static function between(Parser $open, Parser $close, Parser $content): Parser
    {
        return Combinators::between($open, $close, $content);
    }

    /**
     * Creates a parser that matches the given character.
     *
     * @return Parser<string>
     */
    public static function char(string $char): Parser
    {
        return CoreParsers::char($char);
    }

    /**
     * Creates a parser that matches any ASCII decimal digit.
     *
     * @return Parser<string>
     */
    public static function digit(): Parser
    {
        return CoreParsers::digit();
    }

    /**
     * Creates a parser that matches the end of input.
     *
     * @return Parser<string>
     */
    public static function eof(): Parser
    {
        return CoreParsers::eof();
    }

    /**
     * Creates a parser that joins sequential parser outputs into a string.
     *
     * @template T
     * @param Parser<T> ...$parsers
     * @return Parser<string>
     */
    public static function join(Parser ...$parsers): Parser
    {
        return Combinators::join(...$parsers);
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
        return CoreParsers::lazy($factory);
    }

    /**
     * Creates a parser that matches a character from the given UTF-8 character set.
     *
     * @return Parser<string>
     * @throws ParserException When the character set is empty or invalid UTF-8.
     */
    public static function oneOf(string $characters): Parser
    {
        return CoreParsers::oneOf($characters);
    }

    /**
     * Creates a parser that combines the outputs of two sequential parsers.
     *
     * @template TLeft
     * @template TRight
     * @template TSeparator
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     * @param Parser<TSeparator>|null $sep
     * @return Parser<array{TLeft, TRight}>
     */
    public static function pair(Parser $left, Parser $right, ?Parser $sep = null): Parser
    {
        return Combinators::pair($left, $right, $sep);
    }

    /**
     * Creates a parser that matches a character satisfying the predicate.
     *
     * @param Closure(string): bool $predicate
     * @return Parser<string>
     */
    public static function predicate(Closure $predicate): Parser
    {
        return CoreParsers::predicate($predicate);
    }

    /**
     * Creates a parser that matches a character within the given Unicode code point range.
     *
     * @return Parser<string>
     * @throws ParserException When either bound is not one UTF-8 character or the range is invalid.
     */
    public static function range(string $from, string $to): Parser
    {
        return CoreParsers::range($from, $to);
    }

    /**
     * Creates a parser that matches a regular expression at the current input offset.
     *
     * @return Parser<string>
     */
    public static function regexp(string $pattern): Parser
    {
        return CoreParsers::regexp($pattern);
    }

    /**
     * Creates a parser that matches content separated by another parser.
     *
     * @template TContent
     * @template TSeparator
     * @param Parser<TContent> $content
     * @param Parser<TSeparator> $separator
     * @return Parser<list<TContent>>
     */
    public static function sepBy(Parser $content, Parser $separator, int $min = 0): Parser
    {
        return Combinators::sepBy($content, $separator, $min);
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
        return Combinators::seq(...$parsers);
    }

    /**
     * Creates a parser that consumes sequential parser outputs without retaining them.
     *
     * @param Parser<*> ...$parsers
     * @return Parser<string>
     */
    public static function skip(Parser ...$parsers): Parser
    {
        return Combinators::skip(...$parsers);
    }

    /**
     * Parses the left parser, then returns the output of the right parser.
     *
     * @template TLeft
     * @template TRight
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     * @return Parser<TRight>
     */
    public static function skipLeft(Parser $left, Parser $right): Parser
    {
        return Combinators::skipLeft($left, $right);
    }

    /**
     * Parses the right parser after the left parser, then returns the left output.
     *
     * @template TLeft
     * @template TRight
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     * @return Parser<TLeft>
     */
    public static function skipRight(Parser $left, Parser $right): Parser
    {
        return Combinators::skipRight($left, $right);
    }

    /**
     * Creates a parser that matches the given string.
     *
     * @return Parser<string>
     */
    public static function string(string $expected): Parser
    {
        return CoreParsers::string($expected);
    }

    /**
     * Creates a parser that matches any ASCII whitespace character.
     *
     * @return Parser<string>
     */
    public static function whitespace(): Parser
    {
        return CoreParsers::whitespace();
    }

    /**
     * Creates a parser that matches one or more consecutive ASCII whitespace characters.
     *
     * @return Parser<string>
     */
    public static function whitespaces(): Parser
    {
        return CoreParsers::whitespaces();
    }
}
