<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Pico\Exceptions\ParserInputException;

/**
 * Input passed to a parser at a character offset.
 */
final readonly class ParserInput
{
    /**
     * {@see ParserInput} constructor.
     *
     * @param string $input The complete UTF-8 input string.
     * @param int $offset The current character offset.
     * @throws ParserInputException When the input or offset is invalid.
     */
    public function __construct(
        public string $input,
        public int $offset = 0,
    ) {
        if (!mb_check_encoding($this->input, 'UTF-8')) {
            throw new ParserInputException('The input must be valid UTF-8.');
        }

        if ($this->offset < 0 || $this->offset > mb_strlen($this->input, 'UTF-8')) {
            throw new ParserInputException('The offset must be within the input.');
        }
    }

    /**
     * Determines whether the requested character length can be consumed.
     *
     * @throws ParserInputException When the length is negative.
     */
    public function canConsume(int $length): bool
    {
        $this->assertValidLength($length);

        return $length <= mb_strlen($this->input, 'UTF-8') - $this->offset;
    }

    /**
     * Returns the requested number of characters from the current offset.
     *
     * @throws ParserInputException When the length cannot be consumed.
     */
    public function take(int $length): string
    {
        $this->assertCanConsume($length);

        return mb_substr($this->input, $this->offset, $length, 'UTF-8');
    }

    /**
     * Returns the current character.
     *
     * @throws ParserInputException When the input is at its end.
     */
    public function current(): string
    {
        return $this->take(1);
    }

    /**
     * Determines whether the remaining input starts with the expected string.
     */
    public function startsWith(string $expected): bool
    {
        $length = mb_strlen($expected, 'UTF-8');

        return $this->canConsume($length) && $this->take($length) === $expected;
    }

    /**
     * Returns this input advanced by the given character length.
     *
     * @throws ParserInputException When the length cannot be consumed.
     */
    public function advanced(int $length): self
    {
        $this->assertCanConsume($length);

        return new self($this->input, $this->offset + $length);
    }

    /**
     * Determines whether the current offset is at the end of the input.
     */
    public function isAtEnd(): bool
    {
        return $this->offset === mb_strlen($this->input, 'UTF-8');
    }

    /**
     * @throws ParserInputException When the length is negative.
     */
    private function assertValidLength(int $length): void
    {
        if ($length < 0) {
            throw new ParserInputException('The length must not be negative.');
        }
    }

    /**
     * @throws ParserInputException When the length cannot be consumed.
     */
    private function assertCanConsume(int $length): void
    {
        if (!$this->canConsume($length)) {
            throw new ParserInputException('The requested length exceeds the remaining input.');
        }
    }
}
