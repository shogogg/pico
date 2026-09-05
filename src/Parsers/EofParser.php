<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Pico\Contracts\ParserResult;

/**
 * Parser that matches the end of input.
 *
 * @extends AbstractParser<string>
 */
final class EofParser extends AbstractParser
{
    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        return $input->isAtEnd() ? success('', 0) : failure();
    }
}
