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
use Pico\Contracts\ParserResult;

/**
 * Parser that matches a character satisfying a predicate.
 *
 * @extends AbstractParser<string>
 */
final readonly class PredicateParser extends AbstractParser
{
    /**
     * {@see PredicateParser} constructor.
     *
     * @param Closure(string): bool $predicate
     */
    public function __construct(
        private Closure $predicate,
    ) {
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        if ($input->isAtEnd()) {
            return failure();
        }
        $char = $input->current();
        return ($this->predicate)($char) ? success($char, 1) : failure();
    }
}
