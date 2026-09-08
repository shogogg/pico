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
use Pico\Internal\PicoInternal;

/**
 * Parser whose definition can refer to itself.
 *
 * @template T
 * @extends AbstractParser<T>
 */
final class RecursiveParser extends AbstractParser
{
    /** @var ContextualParser<T>|null */
    private ?ContextualParser $parser = null;

    private bool $initialized = false;

    /**
     * {@see RecursiveParser} constructor.
     *
     * @param Closure(Parser<T>): Parser<T> $definition
     */
    public function __construct(private readonly Closure $definition)
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
        if ($this->parser !== null) {
            return $this->parser;
        }

        if ($this->initialized) {
            throw new ParserException('The recursive parser definition can only be initialized once.');
        }

        $this->initialized = true;

        return $this->parser = PicoInternal::asContextualParser(($this->definition)($this));
    }
}
