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
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;

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
    public function concat(): ParserResult
    {
        return self::of(self::concatenate($this->output), $this->consumedLength);
    }

    /** {@inheritDoc} */
    public function output()
    {
        return $this->output;
    }

    /**
     * @template U
     * @param Closure(T): U $fn
     * @return ParserResult<U>
     */
    public function map(Closure $fn): ParserResult
    {
        return self::of(($fn)($this->output), $this->consumedLength);
    }

    /** {@inheritDoc} */
    public function join(string $separator = ''): ParserResult
    {
        $output = is_array($this->output)
            ? implode($separator, array_map(self::stringify(...), $this->output))
            : self::stringify($this->output);

        return self::of($output, $this->consumedLength);
    }

    /**
     * @throws ParserException When the value cannot be converted to a string.
     */
    private static function concatenate(mixed $value): string
    {
        return is_array($value)
            ? implode('', array_map(self::concatenate(...), $value))
            : self::stringify($value);
    }

    /**
     * @throws ParserException When the value cannot be converted to a string.
     */
    private static function stringify(mixed $value): string
    {
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string)$value;
        }
        throw new ParserException('The value must be a scalar or implement Stringable.');
    }
}
