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
 * Parser that matches the first successful parser.
 *
 * @template T
 * @extends AbstractParser<T>
 */
final readonly class AnyOfParser extends AbstractParser
{
    /** @var list<ContextualParser<T>> */
    private array $parsers;

    /**
     * {@see AnyOfParser} constructor.
     *
     * @param Parser<T> ...$parsers
     */
    public function __construct(Parser ...$parsers)
    {
        foreach ($parsers as $parser) {
            if (!($parser instanceof ContextualParser)) {
                throw new LogicException('All parsers must implement ContextualParser.');
            }
        }
        $this->parsers = array_values($parsers);
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
