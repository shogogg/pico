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
 * Parser that fails when an exclusion parser succeeds.
 *
 * @template T
 * @extends AbstractParser<T>
 */
final class ExceptParser extends AbstractParser
{
    /** @var ContextualParser<mixed> */
    private readonly ContextualParser $except;

    /** @var ContextualParser<T> */
    private readonly ContextualParser $parser;

    /**
     * {@see ExceptParser} constructor.
     *
     * @param Parser<T> $parser
     * @param Parser<mixed> $except
     * @throws \Pico\Exceptions\ParserException When either parser does not implement ContextualParser.
     */
    public function __construct(Parser $parser, Parser $except)
    {
        $this->except = PicoInternal::asContextualParser($except);
        $this->parser = PicoInternal::asContextualParser($parser);
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        return $this->except->parseInput($input)->isSuccess()
            ? failure()
            : $this->parser->parseInput($input);
    }
}
