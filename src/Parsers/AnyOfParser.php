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
 * Parser that matches the first successful parser.
 *
 * @template T
 * @extends AbstractParser<T>
 */
final class AnyOfParser extends AbstractParser
{
    /** @var list<ContextualParser<T>> */
    private readonly array $parsers;

    /**
     * {@see AnyOfParser} constructor.
     *
     * @param Parser<T> ...$parsers
     */
    public function __construct(Parser ...$parsers)
    {
        $contextualParsers = [];

        foreach ($parsers as $parser) {
            PicoInternal::ensureContextualParser($parser);

            $contextualParsers[] = $parser;
        }

        $this->parsers = $contextualParsers;
    }

    /**
     * @return ParserResult<T>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        foreach ($this->parsers as $parser) {
            $result = $parser->parseInput($input);
            if ($result->isSuccess()) {
                return $result;
            }
        }
        return failure();
    }
}
