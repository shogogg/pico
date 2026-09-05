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
 * Parser that matches a character from a given set.
 *
 * @extends AbstractParser<string>
 */
final class OneOfParser extends AbstractParser
{
    /** @var array<string, true> */
    private readonly array $characters;

    /**
     * {@see OneOfParser} constructor.
     *
     * @throws ParserException When the character set is empty or invalid UTF-8.
     */
    public function __construct(string $characters)
    {
        if (!mb_check_encoding($characters, 'UTF-8')) {
            throw new ParserException('The character set must be valid UTF-8.');
        }

        $characterList = mb_str_split($characters, 1, 'UTF-8');

        if ($characterList === []) {
            throw new ParserException('The character set must not be empty.');
        }

        $this->characters = array_fill_keys($characterList, true);
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        if ($input->isAtEnd()) {
            return failure();
        }

        $char = $input->current();

        return isset($this->characters[$char]) ? success($char, 1) : failure();
    }
}
