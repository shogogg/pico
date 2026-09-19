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
use Pico\Internal\PicoInternal;

/**
 * Parser that defers constructing another parser until parsing.
 *
 * @template T
 * @extends AbstractParser<T>
 */
final class LazyParser extends AbstractParser
{
    /** @var ContextualParser<T>|null */
    private ?ContextualParser $parser = null;

    /**
     * {@see LazyParser} constructor.
     *
     * @param Closure(): Parser<T> $factory
     */
    public function __construct(private readonly Closure $factory)
    {
    }

    /**
     * @return ParserResult<T>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        return $this->resolveParser()->parseInput($input);
    }

    /**
     * @return ContextualParser<T>
     */
    private function resolveParser(): ContextualParser
    {
        if ($this->parser === null) {
            $this->parser = PicoInternal::asContextualParser(($this->factory)());
        }
        return $this->parser;
    }
}
