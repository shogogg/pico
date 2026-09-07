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
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;
use Pico\Internal\PicoInternal;
use Pico\Parsers\AbstractParser;
use Pico\Parsers\AnyCharParser;
use Pico\Parsers\AnyOfParser;
use Pico\Parsers\BetweenParser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\ParserInput;
use Pico\Parsers\RegExpParser;
use Pico\Parsers\SepByParser;
use Pico\Parsers\SeqParser;
use Pico\Parsers\SkipParser;
use Pico\Parsers\SkipLeftParser;
use Pico\Parsers\SkipRightParser;
use Pico\Parsers\StringParser;

use function Pico\Parsers\failure;
use function Pico\Parsers\success;

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
     * Creates a parser that matches an ASCII character satisfying the predicate.
     *
     * @param non-empty-string $key
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
        return self::memoize('anyChar', static fn (): Parser => new AnyCharParser());
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
        $ps = [];
        foreach ($parsers as $parser) {
            $ps[] = PicoInternal::asContextualParser($parser);
        }
        return AbstractParser::createParser(function (ParserInput $input) use ($ps): ParserResult {
            foreach ($ps as $p) {
                $result = $p->parseInput($input);
                if ($result->isSuccess()) {
                    return $result;
                }
            }
            return failure();
        });
    }

    /**
     * Creates a parser that matches any ASCII character.
     *
     * @return Parser<string>
     */
    public static function ascii(): Parser
    {
        return self::memoize('ascii', static function (): Parser {
            return self::predicate(static fn (string $char): bool => strlen($char) === 1);
        });
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
        return new BetweenParser($open, $close, $content);
    }

    /**
     * Creates a parser that matches the given character.
     *
     * @return Parser<string>
     */
    public static function char(string $char): Parser
    {
        if (!mb_check_encoding($char, 'UTF-8')) {
            throw new ParserException('The character must be valid UTF-8.');
        }
        if (mb_strlen($char) !== 1) {
            throw new ParserException('The character must be exactly one character long.');
        }
        return AbstractParser::createParser(function (ParserInput $input) use ($char): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }
            return $input->current() === $char ? success($char, 1) : failure();
        });
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
     * Creates a parser that matches the end of input.
     *
     * @return Parser<string>
     */
    public static function eof(): Parser
    {
        return self::memoize('eof', static fn (): Parser => AbstractParser::createParser(
            static fn (ParserInput $input): ParserResult => $input->isAtEnd() ? success('', 0) : failure(),
        ));
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
        return self::seq(...$parsers)->join();
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
     * Creates a parser that matches a character from the given UTF-8 character set.
     *
     * @return Parser<string>
     * @throws ParserException When the character set is empty or invalid UTF-8.
     */
    public static function oneOf(string $characters): Parser
    {
        if (!mb_check_encoding($characters, 'UTF-8')) {
            throw new ParserException('The character set must be valid UTF-8.');
        }
        if (mb_strlen($characters) === 0) {
            throw new ParserException('The character set must not be empty.');
        }
        $characterSet = array_fill_keys(mb_str_split($characters, 1, 'UTF-8'), true);

        return AbstractParser::createParser(static function (ParserInput $input) use ($characterSet): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }
            $char = $input->current();
            return isset($characterSet[$char]) ? success($char, 1) : failure();
        });
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
        return $left->then($sep === null ? $right : self::skipLeft($sep, $right));
    }

    /**
     * Creates a parser that matches a character satisfying the predicate.
     *
     * @param Closure(string): bool $predicate
     * @return Parser<string>
     */
    public static function predicate(Closure $predicate): Parser
    {
        return AbstractParser::createParser(static function (ParserInput $input) use ($predicate): ParserResult {
            if ($input->isAtEnd()) {
                return failure();
            }
            $char = $input->current();
            return $predicate($char) ? success($char, 1) : failure();
        });
    }

    /**
     * Creates a parser that matches a character within the given Unicode code point range.
     *
     * @return Parser<string>
     * @throws ParserException When either bound is not one UTF-8 character or the range is invalid.
     */
    public static function range(string $from, string $to): Parser
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

        return AbstractParser::createParser(
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
        return new SepByParser($content, $separator, $min);
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
     * Creates a parser that consumes sequential parser outputs without retaining them.
     *
     * @param Parser<*> ...$parsers
     * @return Parser<string>
     */
    public static function skip(Parser ...$parsers): Parser
    {
        return new SkipParser(...$parsers);
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
        return new SkipLeftParser($left, $right);
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
        return new SkipRightParser($left, $right);
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
