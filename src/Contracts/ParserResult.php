<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Contracts;

/**
 * Interface for the result of a parser.
 *
 * @template-covariant T
 */
interface ParserResult
{
    /**
     * Whether the parsing was successful.
     */
    public function isSuccess(): bool;

    /**
     * Whether the parsing failed.
     */
    public function isFailure(): bool;

    /**
     * The length of the consumed input.
     */
    public function consumedLength(): int;

    /**
     * The output of the parser.
     *
     * @return T
     */
    public function output();
}
