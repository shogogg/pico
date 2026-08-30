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

/**
 * Parser that matches a sequence of parsers.
 *
 * @template T
 * @extends AbstractParser<list<T>>
 */
final readonly class SeqParser extends AbstractParser
{
    /** @var list<ContextualParser<T>> */
    private array $parsers;

    /**
     * {@see SeqParser} constructor.
     *
     * @param Parser<T> ...$parsers
     */
    public function __construct(Parser ...$parsers)
    {
        foreach ($parsers as $parser) {
            if (!($parser instanceof ContextualParser)) {
                throw new ParserException('All parsers must implement ContextualParser.');
            }
        }
        $this->parsers = array_values($parsers);
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

        foreach ($this->parsers as $parser) {
            /** @var ParserResult<T> $result */
            $result = $parser->parseInput($currentInput);
            if ($result->isFailure()) {
                return failure();
            }
            $outputs[] = $result->output();
            $length = $result->consumedLength();
            $consumedLength += $length;
            $currentInput = $currentInput->advanced($length);
        }

        /** @var ParserResult<list<T>> */
        return success($outputs, $consumedLength);
    }
}
