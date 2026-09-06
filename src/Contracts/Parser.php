<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Contracts;

use Closure;
use Pico\Exceptions\ParserInputException;
use Pico\Exceptions\ParserException;

/**
 * Interface for a parser.
 *
 * @template-covariant T
 */
interface Parser
{
    /**
     * Parses the input string.
     *
     * @param string $input
     * @return ParserResult<T>
     * @throws ParserInputException
     */
    public function parse(string $input): ParserResult;

    /**
     * Requires this parser to consume the complete input.
     *
     * @return Parser<T>
     */
    public function complete(): Parser;

    /**
     * Repeats this parser.
     *
     * @return Parser<list<T>>
     * @throws ParserException When the repetition bounds are invalid.
     */
    public function repeat(int $min = 0, int $max = PHP_INT_MAX): Parser;

    /**
     * Parses this parser unless the exclusion parser succeeds.
     *
     * @template TExcept
     * @param Parser<TExcept> $except
     * @return Parser<T>
     */
    public function except(Parser $except): Parser;

    /**
     * Joins the output into a string.
     *
     * @return Parser<string>
     */
    public function join(string $separator = ''): Parser;

    /**
     * Transforms the output of a successful parse.
     *
     * @template U
     * @param Closure(T): U $fn
     * @return Parser<U>
     */
    public function map(Closure $fn): Parser;

    /**
     * Makes this parser optional.
     *
     * @return Parser<T|string>
     */
    public function optional(): Parser;

    /**
     * Discards the output of a successful parse.
     *
     * @return Parser<string>
     */
    public function skip(): Parser;
}
