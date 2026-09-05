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

/**
 * Base class for parsers that operate on a ParserInput.
 *
 * @template T
 * @implements Parser<T>
 * @implements ContextualParser<T>
 */
abstract class AbstractParser implements Parser, ContextualParser
{
    /** {@inheritDoc} */
    abstract public function parseInput(ParserInput $input): ParserResult;

    /** {@inheritDoc} */
    final public function parse(string $input): ParserResult
    {
        return $this->parseInput(new ParserInput($input));
    }

    /**
     * @template TExcept
     * @param Parser<TExcept> $except
     * @return Parser<T>
     */
    final public function except(Parser $except): Parser
    {
        return new ExceptParser($this, $except);
    }

    /** {@inheritDoc} */
    final public function join(string $separator = ''): Parser
    {
        return $this->createParser(
            fn (ParserInput $input): ParserResult => $this->parseInput($input)->join($separator),
        );
    }

    /**
     * @template U
     * @param Closure(T): U $fn
     * @return Parser<U>
     */
    final public function map(Closure $fn): Parser
    {
        return $this->createParser(
            fn (ParserInput $input): ParserResult => $this->parseInput($input)->map($fn),
        );
    }

    /** {@inheritDoc} */
    final public function optional(): Parser
    {
        return $this->createParser(function (ParserInput $input): ParserResult {
            $result = $this->parseInput($input);
            return $result->isSuccess() ? $result : success('', 0);
        });
    }

    /**
     * @template U
     * @param Closure(ParserInput): ParserResult<U> $parse
     * @return Parser<U>
     */
    private function createParser(Closure $parse): Parser
    {
        return new class ($parse) extends AbstractParser {
            private readonly Closure $parse;

            /**
             * @param Closure(ParserInput): ParserResult<U> $parse
             */
            public function __construct(Closure $parse)
            {
                $this->parse = $parse;
            }

            /**
             * @return ParserResult<U>
             */
            public function parseInput(ParserInput $input): ParserResult
            {
                return ($this->parse)($input);
            }
        };
    }
}
