<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Closure;
use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;

/**
 * Parser that transforms the output of another parser.
 *
 * @template T
 * @template U
 * @extends AbstractParser<U>
 */
final readonly class MapParser extends AbstractParser
{
    /** @var ContextualParser<T> */
    private ContextualParser $parser;

    /** @var Closure(T): U */
    private Closure $fn;

    /**
     * {@see MapParser} constructor.
     *
     * @param Parser<T> $parser
     * @param Closure(T): U $fn
     */
    public function __construct(Parser $parser, Closure $fn)
    {
        if (!($parser instanceof ContextualParser)) {
            throw new ParserException('The parser must implement ContextualParser.');
        }

        $this->parser = $parser;
        $this->fn = $fn;
    }

    /**
     * @return ParserResult<U>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        return $this->parser->parseInput($input)->map($this->fn);
    }
}
