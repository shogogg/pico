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
 * Parser that matches content between opening and closing parsers.
 *
 * @template TOpen
 * @template TContent
 * @template TClose
 * @extends AbstractParser<TContent>
 */
final class BetweenParser extends AbstractParser
{
    /** @var ContextualParser<TClose> */
    private readonly ContextualParser $close;

    /** @var ContextualParser<TContent> */
    private readonly ContextualParser $content;

    /** @var ContextualParser<TOpen> */
    private readonly ContextualParser $open;

    /**
     * {@see BetweenParser} constructor.
     *
     * @param Parser<TOpen> $open
     * @param Parser<TClose> $close
     * @param Parser<TContent> $content
     */
    public function __construct(Parser $open, Parser $close, Parser $content)
    {
        $this->open = PicoInternal::asContextualParser($open);
        $this->close = PicoInternal::asContextualParser($close);
        $this->content = PicoInternal::asContextualParser($content);
    }

    /**
     * @return ParserResult<TContent>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        $openResult = $this->open->parseInput($input);
        if ($openResult->isFailure()) {
            return failure();
        }

        $openLength = $openResult->consumedLength();
        $contentResult = $this->content->parseInput($input->advanced($openLength));
        if ($contentResult->isFailure()) {
            return failure();
        }

        $contentLength = $contentResult->consumedLength();
        $closeResult = $this->close->parseInput($input->advanced($openLength + $contentLength));
        if ($closeResult->isFailure()) {
            return failure();
        }

        return success(
            $contentResult->output(),
            $openLength + $contentLength + $closeResult->consumedLength(),
        );
    }
}
