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
 * Parser that matches content separated by another parser.
 *
 * @template TContent
 * @template TSeparator
 * @extends AbstractParser<list<TContent>>
 */
final class SepByParser extends AbstractParser
{
    /** @var ContextualParser<TContent> */
    private readonly ContextualParser $content;

    /** @var ContextualParser<TSeparator> */
    private readonly ContextualParser $separator;

    /**
     * {@see SepByParser} constructor.
     *
     * @param Parser<TContent> $content
     * @param Parser<TSeparator> $separator
     */
    public function __construct(Parser $content, Parser $separator, private readonly int $min = 0)
    {
        if ($this->min < 0) {
            throw new ParserException('The minimum item count must not be negative.');
        }

        PicoInternal::ensureContextualParser($content);
        PicoInternal::ensureContextualParser($separator);

        $this->content = $content;
        $this->separator = $separator;
    }

    /**
     * @return ParserResult<list<TContent>>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        /** @var list<TContent> $outputs */
        $outputs = [];
        $consumedLength = 0;
        $currentInput = $input;

        $contentResult = $this->parseContent($currentInput);
        if ($contentResult->isFailure()) {
            return $this->min === 0 ? success([], 0) : failure();
        }

        $contentLength = $contentResult->consumedLength();
        $outputs[] = $contentResult->output();
        $consumedLength += $contentLength;
        $currentInput = $currentInput->advanced($contentLength);

        while (true) {
            $separatorResult = $this->separator->parseInput($currentInput);
            if ($separatorResult->isFailure()) {
                break;
            }

            $separatorLength = $separatorResult->consumedLength();
            $contentInput = $currentInput->advanced($separatorLength);
            $contentResult = $this->parseContent($contentInput);
            if ($contentResult->isFailure()) {
                break;
            }

            $contentLength = $contentResult->consumedLength();
            if ($separatorLength + $contentLength === 0) {
                break;
            }

            $outputs[] = $contentResult->output();
            $consumedLength += $separatorLength + $contentLength;
            $currentInput = $contentInput->advanced($contentLength);
        }

        return count($outputs) < $this->min ? failure() : success($outputs, $consumedLength);
    }

    /**
     * @return ParserResult<TContent>
     */
    private function parseContent(ParserInput $input): ParserResult
    {
        return $this->content->parseInput($input);
    }
}
