<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Contracts;

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
}
