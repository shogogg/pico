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

/**
 * Base class for parsers that operate on a ParserInput.
 *
 * @template T
 * @implements Parser<T>
 * @implements ContextualParser<T>
 */
abstract readonly class AbstractParser implements Parser, ContextualParser
{
    /** {@inheritDoc} */
    final public function parse(string $input): ParserResult
    {
        return $this->parseInput(new ParserInput($input));
    }

    /** {@inheritDoc} */
    abstract public function parseInput(ParserInput $input): ParserResult;
}
