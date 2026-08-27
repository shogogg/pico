<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico;

use Pico\Contracts\ParserResult;

/**
 * Successful parsing result.
 *
 * @template T
 * @implements ParserResult<T>
 */
final readonly class Success implements ParserResult
{
    /**
     * {@see Success} constructor.
     *
     * @param T $output
     * @param int $consumedLength
     */
    private function __construct(
        private mixed $output,
        private int $consumedLength,
    ) {
    }

    /**
     * Creates a new instance of {@see Success}.
     *
     * @template U
     * @param U $output
     * @param int $consumedLength
     * @return ParserResult<U>
     */
    public static function of(mixed $output, int $consumedLength): ParserResult
    {
        return new self($output, $consumedLength);
    }

    /** {@inheritDoc} */
    public function isSuccess(): bool
    {
        return true;
    }

    /** {@inheritDoc} */
    public function isFailure(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function consumedLength(): int
    {
        return $this->consumedLength;
    }

    /** {@inheritDoc} */
    public function output()
    {
        return $this->output;
    }
}
