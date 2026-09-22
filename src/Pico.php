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
use Pico\Parsers\Combinators;
use Pico\Parsers\Parsers;

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
        return Parsers::alpha();
    }

    /**
     * Creates a parser that matches any ASCII alphanumeric character.
     *
     * @return Parser<string>
     */
    public static function alphaNum(): Parser
    {
        return Parsers::alphaNum();
    }

    /**
     * Creates a parser that matches any single character.
     *
     * @return Parser<string>
     */
    public static function anyChar(): Parser
    {
        return Parsers::anyChar();
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
        return Parsers::ascii();
    }

    /**
     * Creates a parser that matches content between opening and closing parsers.
     *
     * @template TOpen
     * @template TContent
     * @template TClose
     * @param Parser<TOpen> $open
     * @param Parser<TContent> $content
     * @param Parser<TClose> $close
     * @return Parser<TContent>
     */
    public static function between(Parser $open, Parser $content, Parser $close): Parser
    {
        return Combinators::between($open, $content, $close);
    }

    /**
     * Creates a parser that matches the given character.
     *
     * @return Parser<string>
     */
    public static function char(string $char): Parser
    {
        return Parsers::char($char);
    }

    /**
     * Creates a parser that matches a character satisfying a condition.
     *
     * @param Closure(string): bool $predicate
     * @return Parser<string>
     */
    public static function charWhere(Closure $predicate): Parser
    {
        return Parsers::charWhere($predicate);
    }

    /**
     * Creates a parser that recursively concatenates sequential parser outputs.
     *
     * @template T
     * @param Parser<T> ...$parsers
     * @return Parser<string>
     */
    public static function concat(Parser ...$parsers): Parser
    {
        return Combinators::concat(...$parsers);
    }

    /**
     * Creates a parser that matches any ASCII decimal digit.
     *
     * @return Parser<string>
     */
    public static function digit(): Parser
    {
        return Parsers::digit();
    }

    /**
     * Creates a parser that matches the end of input.
     *
     * @return Parser<string>
     */
    public static function eof(): Parser
    {
        return Parsers::eof();
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
        return Parsers::lazy($factory);
    }

    /**
     * Creates a parser that matches a character from the given UTF-8 character set.
     *
     * @return Parser<string>
     * @throws ParserException When the character set is invalid UTF-8.
     */
    public static function oneOf(string $characters): Parser
    {
        return Parsers::oneOf($characters);
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
     * Creates a parser that matches a character within the given Unicode code point range.
     *
     * @return Parser<string>
     * @throws ParserException When either bound is not one UTF-8 character or the range is invalid.
     */
    public static function range(string $from, string $to): Parser
    {
        return Parsers::range($from, $to);
    }

    /**
     * Creates a parser whose definition can refer to itself.
     *
     * @template T
     * @param Closure(Parser<T>): Parser<T> $definition
     * @return Parser<T>
     */
    public static function recursive(Closure $definition): Parser
    {
        return Parsers::recursive($definition);
    }

    /**
    * Creates a parser that matches a regular expression at the current input offset.
     *
     * @return Parser<string>
     */
    public static function regexp(string $pattern): Parser
    {
        return Parsers::regexp($pattern);
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
        return Parsers::string($expected);
    }

    /**
     * Creates a parser that combines the outputs of three sequential parsers.
     *
     * @template A
     * @template B
     * @template C
     * @param Parser<A> $a
     * @param Parser<B> $b
     * @param Parser<C> $c
     * @return Parser<array{A, B, C}>
     */
    public static function triple(Parser $a, Parser $b, Parser $c): Parser
    {
        return Combinators::triple($a, $b, $c);
    }

    /**
     * Creates a parser that matches any ASCII whitespace character.
     *
     * @return Parser<string>
     */
    public static function whitespace(): Parser
    {
        return Parsers::whitespace();
    }

    /**
     * Creates a parser that matches one or more consecutive ASCII whitespace characters.
     *
     * @return Parser<string>
     */
    public static function whitespaces(): Parser
    {
        return Parsers::whitespaces();
    }
}
