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
 * Parser that matches a character within a Unicode code point range.
 *
 * @extends AbstractParser<string>
 */
final class RangeParser extends AbstractParser
{
    private readonly int $maxCodePoint;
    private readonly int $minCodePoint;
    private readonly string $from;
    private readonly string $to;

    /**
     * {@see RangeParser} constructor.
     *
     * @throws ParserException When either bound is not one UTF-8 character or the range is invalid.
     */
    public function __construct(string $from, string $to)
    {
        if (!mb_check_encoding($from, 'UTF-8') || !mb_check_encoding($to, 'UTF-8')) {
            throw new ParserException('Range bounds must be valid UTF-8.');
        }

        if (mb_strlen($from, 'UTF-8') !== 1 || mb_strlen($to, 'UTF-8') !== 1) {
            throw new ParserException('Range bounds must be exactly one UTF-8 character.');
        }

        $minCodePoint = mb_ord($from, 'UTF-8');
        $maxCodePoint = mb_ord($to, 'UTF-8');

        if ($minCodePoint > $maxCodePoint) {
            throw new ParserException('The range start must not exceed the range end.');
        }

        $this->maxCodePoint = $maxCodePoint;
        $this->minCodePoint = $minCodePoint;
        $this->from = $from;
        $this->to = $to;
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        if ($input->isAtEnd()) {
            return failure();
        }

        $char = $input->current();

        if ($char === $this->from || $char === $this->to) {
            return success($char, 1);
        }

        $codePoint = mb_ord($char, 'UTF-8');

        return $codePoint >= $this->minCodePoint && $codePoint <= $this->maxCodePoint
            ? success($char, 1)
            : failure();
    }
}
