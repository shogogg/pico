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
use Pico\Exceptions\ParserException;

/**
 * Parser that matches a specific character.
 *
 * @extends AbstractParser<string>
 */
final readonly class CharParser extends AbstractParser
{
    /**
     * {@see CharParser} constructor.
     */
    public function __construct(
        private string $char,
    ) {
        if (mb_strlen($this->char) !== 1) {
            throw new ParserException('The character must be exactly one character long.');
        }
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        if ($input->isAtEnd()) {
            return failure();
        }
        $char = $input->current();
        return $char === $this->char ? success($char, 1) : failure();
    }
}
