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
    final public function parse(string $input): ParserResult
    {
        return $this->parseInput(new ParserInput($input));
    }

    /**
     * @template U
     * @param Closure(T): U $fn
     * @return Parser<U>
     */
    final public function map(Closure $fn): Parser
    {
        return $this->transformResult(static fn (ParserResult $result): ParserResult => $result->map($fn));
    }

    /** {@inheritDoc} */
    final public function join(string $separator = ''): Parser
    {
        return $this->transformResult(static fn (ParserResult $result): ParserResult => $result->join($separator));
    }

    /** {@inheritDoc} */
    abstract public function parseInput(ParserInput $input): ParserResult;

    /**
     * @template U
     * @param Closure(ParserResult<T>): ParserResult<U> $fn
     * @return Parser<U>
     */
    private function transformResult(Closure $fn): Parser
    {
        return new class ($this, $fn) extends AbstractParser {
            private readonly Closure $fn;

            /** @var ContextualParser<T> */
            private readonly ContextualParser $parser;

            /**
             * @param ContextualParser<T> $parser
             * @param Closure(ParserResult<T>): ParserResult<U> $fn
             */
            public function __construct(ContextualParser $parser, Closure $fn)
            {
                $this->parser = $parser;
                $this->fn = $fn;
            }

            /**
             * @return ParserResult<U>
             */
            public function parseInput(ParserInput $input): ParserResult
            {
                return ($this->fn)($this->parser->parseInput($input));
            }
        };
    }
}
