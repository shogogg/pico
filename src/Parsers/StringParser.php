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
 * Parser that matches a specific string.
 *
 * @extends AbstractParser<string>
 */
final readonly class StringParser extends AbstractParser
{
    private int $length;

    /**
     * {@see StringParser} constructor.
     */
    public function __construct(
        public string $expected,
    ) {
        $this->length = mb_strlen($this->expected, 'UTF-8');
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        if ($this->expected === '') {
            return failure();
        }
        return $input->startsWith($this->expected)
            ? success($this->expected, $this->length)
            : failure();
    }
}
