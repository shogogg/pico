<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use LogicException;
use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;

/**
 * Parser that repeatedly matches another parser.
 *
 * @template T
 * @extends AbstractParser<list<T>>
 */
final readonly class RepeatParser extends AbstractParser
{
    /** @var ContextualParser<T> */
    private ContextualParser $parser;

    /**
     * {@see RepeatParser} constructor.
     *
     * @param Parser<T> $parser
     */
    public function __construct(Parser $parser, private int $min = 0, private int $max = PHP_INT_MAX)
    {
        if (!($parser instanceof ContextualParser)) {
            throw new LogicException('The parser must implement ContextualParser.');
        }

        if ($this->min < 0) {
            throw new LogicException('The minimum repetition count must not be negative.');
        }

        if ($this->max < $this->min) {
            throw new LogicException('The maximum repetition count must be at least the minimum repetition count.');
        }

        $this->parser = $parser;
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
