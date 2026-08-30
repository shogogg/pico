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
 * Parser that makes another parser optional.
 *
 * @template T
 * @extends AbstractParser<T|null>
 */
final class OptionalParser extends AbstractParser
{
    /** @var ContextualParser<T> */
    private readonly ContextualParser $parser;

    /**
     * {@see OptionalParser} constructor.
     *
     * @param Parser<T> $parser
     */
    public function __construct(Parser $parser)
    {
        if (!($parser instanceof ContextualParser)) {
            throw new ParserException('The parser must implement ContextualParser.');
        }

        $this->parser = $parser;
    }

    /**
     * @return ParserResult<T|null>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        /** @var ParserResult<T> $result */
        $result = $this->parser->parseInput($input);
        return $result->isSuccess() ? $result : success(null, 0);
    }
}
