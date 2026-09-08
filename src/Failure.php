<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico;

use Closure;
use LogicException;
use Pico\Contracts\ParserResult;

/**
 * Failed parsing result.
 *
 * @implements ParserResult<never>
 */
final readonly class Failure implements ParserResult
{
    /**
     * {@see Failure} constructor.
     */
    private function __construct()
    {
        // Nothing to do.
    }

    /**
     * Creates a new instance of {@see Failure}.
     *
     * @return ParserResult<never>
     */
    public static function getInstance(): ParserResult
    {
        return new self();
    }

    /** {@inheritDoc} */
    public function isSuccess(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function isFailure(): bool
    {
        return true;
    }

    /** {@inheritDoc} */
    public function consumedLength(): int
    {
        return 0;
    }

    /** {@inheritDoc} */
    public function concat(): ParserResult
    {
        return $this;
    }

    /** {@inheritDoc} */
    public function output(): never
    {
        throw new LogicException('There is no value');
    }

    /**
     * @template U
     * @param Closure(never): U $fn
     * @return ParserResult<U>
     */
    public function map(Closure $fn): ParserResult
    {
        return $this;
    }

    /** {@inheritDoc} */
    public function join(string $separator = ''): ParserResult
    {
        return $this;
    }
}
