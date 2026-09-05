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
use Pico\Internal\PicoInternal;

/**
 * Parser that consumes a sequence of parsers without retaining their outputs.
 *
 * @template T
 * @extends AbstractParser<string>
 */
final class SkipParser extends AbstractParser
{
    /** @var list<ContextualParser<T>> */
    private readonly array $parsers;

    /**
     * {@see SkipParser} constructor.
     *
     * @param Parser<T> ...$parsers
     */
    public function __construct(Parser ...$parsers)
    {
        $contextualParsers = [];

        foreach ($parsers as $parser) {
            $contextualParsers[] = PicoInternal::asContextualParser($parser);
        }

        $this->parsers = $contextualParsers;
    }

    /**
     * @return ParserResult<string>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        $consumedLength = 0;
        $currentInput = $input;

        foreach ($this->parsers as $parser) {
            $result = $parser->parseInput($currentInput);
            if ($result->isFailure()) {
                return failure();
            }

            $length = $result->consumedLength();
            $consumedLength += $length;
            $currentInput = $currentInput->advanced($length);
        }

        return success('', $consumedLength);
    }
}
