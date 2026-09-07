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
use Pico\Exceptions\ParserInputException;

/**
 * Contract for parsers that parse a ParserInput.
 *
 * @template-covariant T
 * @extends Parser<T>
 * @internal
 */
interface ContextualParser extends Parser
{
    /**
     * Parses the input at its current offset.
     *
     * @return ParserResult<T>
     * @throws ParserInputException
     */
    public function parseInput(ParserInput $input): ParserResult;
}
