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
 * Parser that matches any single character.
 *
 * @extends AbstractParser<string>
 */
final readonly class AnyCharParser extends AbstractParser
{
    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        if ($input->isAtEnd()) {
            return failure();
        }
        return success($input->current(), 1);
    }
}
