<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;
use Pico\Internal\PicoInternal;

/** @internal */
final class Combinators
{
    private function __construct()
    {
        // Nothing to do.
    }

    /**
     * @template T
     * @param Parser<T> ...$parsers
     * @return ContextualParser<T>
     */
    public static function anyOf(Parser ...$parsers): ContextualParser
    {
        $contextualParsers = [];
        foreach ($parsers as $parser) {
            $contextualParsers[] = PicoInternal::asContextualParser($parser);
        }
        return Parsers::create(function (ParserInput $input) use ($contextualParsers): ParserResult {
            foreach ($contextualParsers as $parser) {
                $result = $parser->parseInput($input);
                if ($result->isSuccess()) {
                    return $result;
                }
            }
            return failure();
        });
    }

    /**
     * @template TOpen
     * @template TContent
     * @template TClose
     * @param Parser<TOpen> $open
     * @param Parser<TClose> $close
     * @param Parser<TContent> $content
     * @return ContextualParser<TContent>
     */
    public static function between(Parser $open, Parser $close, Parser $content): ContextualParser
    {
        /** @var ContextualParser<TContent> $parser */
        $parser = self::seq($open, $content, $close)->map(static fn (array $outputs): mixed => $outputs[1]);
        return PicoInternal::asContextualParser($parser);
    }

    /**
     * @template T
     * @param Parser<T> ...$parsers
     * @return ContextualParser<string>
     */
    public static function join(Parser ...$parsers): ContextualParser
    {
        return PicoInternal::asContextualParser(self::seq(...$parsers)->join());
    }

    /**
     * @template TLeft
     * @template TRight
     * @template TSeparator
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     * @param Parser<TSeparator>|null $sep
     * @return ContextualParser<array{TLeft, TRight}>
     */
    public static function pair(Parser $left, Parser $right, ?Parser $sep = null): ContextualParser
    {
        $parser = $sep === null
            ? $left->then($right)
            : $left->then(self::skipLeft($sep, $right));

        return PicoInternal::asContextualParser($parser);
    }

    /**
     * @template TContent
     * @template TSeparator
     * @param Parser<TContent> $content
     * @param Parser<TSeparator> $separator
     * @return ContextualParser<list<TContent>>
     */
    public static function sepBy(Parser $content, Parser $separator, int $min = 0): ContextualParser
    {
        if ($min < 0) {
            throw new ParserException('The minimum item count must not be negative.');
        }

        $parser = $content
            ->then(self::skipLeft($separator, $content)->repeat())
            ->map(static fn (array $outputs): array => [$outputs[0], ...$outputs[1]], )
            ->orElse(static fn (): array => [])
            ->where(static fn (array $outputs): bool => count($outputs) >= $min);

        return PicoInternal::asContextualParser($parser);
    }

    /**
     * @template T
     * @param Parser<T> ...$parsers
     * @return ContextualParser<list<T>>
     */
    public static function seq(Parser ...$parsers): ContextualParser
    {
        return new SeqParser(...$parsers);
    }

    /**
     * @param Parser<*> ...$parsers
     * @return ContextualParser<string>
     */
    public static function skip(Parser ...$parsers): ContextualParser
    {
        $contextualParsers = [];
        foreach ($parsers as $parser) {
            $contextualParsers[] = PicoInternal::asUntypedContextualParser($parser);
        }

        return Parsers::create(function (ParserInput $input) use ($contextualParsers): ParserResult {
            $consumedLength = 0;
            $currentInput = $input;

            foreach ($contextualParsers as $parser) {
                $result = $parser->parseInput($currentInput);
                if ($result->isFailure()) {
                    return $result;
                }

                $length = $result->consumedLength();
                $consumedLength += $length;
                $currentInput = $currentInput->advanced($length);
            }

            return success('', $consumedLength);
        });
    }

    /**
     * @template TLeft
     * @template TRight
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     * @return ContextualParser<TRight>
     */
    public static function skipLeft(Parser $left, Parser $right): ContextualParser
    {
        $parser = $left->then($right)->map(static fn (array $outputs): mixed => $outputs[1]);
        return PicoInternal::asContextualParser($parser);
    }

    /**
     * @template TLeft
     * @template TRight
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     * @return ContextualParser<TLeft>
     */
    public static function skipRight(Parser $left, Parser $right): ContextualParser
    {
        $parser = $left->then($right)->map(static fn (array $outputs): mixed => $outputs[0]);
        return PicoInternal::asContextualParser($parser);
    }

    /**
     * @template A
     * @template B
     * @template C
     * @param Parser<A> $a
     * @param Parser<B> $b
     * @param Parser<C> $c
     * @return ContextualParser<array{A, B, C}>
     */
    public static function triple(Parser $a, Parser $b, Parser $c): ContextualParser
    {
        /** @var ContextualParser<array{A, B, C}> $parser */
        $parser = self::seq($a, $b, $c);
        return PicoInternal::asContextualParser($parser);
    }
}
