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

/**
 * Interface for a parser.
 *
 * @template T
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
}
