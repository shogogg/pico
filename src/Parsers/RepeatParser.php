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
use Pico\Exceptions\ParserException;
use Pico\Internal\PicoInternal;

/**
 * Parser that repeatedly matches another parser.
 *
 * @template T
 * @extends AbstractParser<list<T>>
 */
final class RepeatParser extends AbstractParser
{
    /** @var ContextualParser<T> */
    private readonly ContextualParser $parser;

    /**
     * {@see RepeatParser} constructor.
     *
     * @param Parser<T> $parser
     */
    public function __construct(Parser $parser, private readonly int $min = 0, private readonly int $max = PHP_INT_MAX)
    {
        if ($this->min < 0) {
            throw new ParserException('The minimum repetition count must not be negative.');
        }
        if ($this->max < $this->min) {
            throw new ParserException('The maximum repetition count must be at least the minimum repetition count.');
        }
        $this->parser = PicoInternal::asContextualParser($parser);
    }

    /**
     * @return ParserResult<list<T>>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        /** @var list<T> $outputs */
        $outputs = [];
        $consumedLength = 0;
        $currentInput = $input;

        for ($count = 0; $count < $this->max; ++$count) {
            /** @var ParserResult<T> $result */
            $result = $this->parser->parseInput($currentInput);
            if ($result->isFailure()) {
                break;
            }

            $length = $result->consumedLength();
            if ($length === 0) {
                break;
            }

            $outputs[] = $result->output();
            $consumedLength += $length;
            $currentInput = $currentInput->advanced($length);
        }

        if ($count < $this->min) {
            return failure();
        }

        /** @var ParserResult<list<T>> */
        return success($outputs, $consumedLength);
    }
}
