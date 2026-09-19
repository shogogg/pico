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
     * The current offset in bytes.
     */
    private int $byteOffset;

    /**
     * The input length in characters.
     */
    private int $length;

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

        $length = mb_strlen($this->input, 'UTF-8');
        if ($this->offset < 0 || $this->offset > $length) {
            throw new ParserInputException('The offset must be within the input.');
        }

        $this->length = $length;
        $this->byteOffset = self::byteOffsetAfter($this->input, 0, $this->offset);
    }

    /**
     * Returns this input advanced by the given character length.
     *
     * @throws ParserInputException When the length cannot be consumed.
     */
    public function advanced(int $length): self
    {
        $this->ensureCanConsume($length);
        return clone($this, [
            'byteOffset' => self::byteOffsetAfter($this->input, $this->byteOffset, $length),
            'offset' => $this->offset + $length,
        ]);
    }

    /**
     * Returns the current offset in bytes.
     */
    public function byteOffset(): int
    {
        return $this->byteOffset;
    }

    /**
     * Determines whether the requested character length can be consumed.
     *
     * @throws ParserInputException When the length is negative.
     */
    public function canConsume(int $length): bool
    {
        if ($length < 0) {
            throw new ParserInputException('The length must not be negative.');
        }
        return $length <= $this->length - $this->offset;
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
     * Determines whether the current offset is at the end of the input.
     */
    public function isAtEnd(): bool
    {
        return $this->offset === $this->length;
    }

    /**
     * Determines whether the remaining input starts with the expected string.
     */
    public function startsWith(string $expected): bool
    {
        $expectedLength = strlen($expected);
        $expectedEnd = $this->byteOffset + $expectedLength;
        $inputLength = strlen($this->input);

        return ($expectedEnd >= $inputLength || (ord($this->input[$expectedEnd]) & 0xc0) !== 0x80)
            && substr_compare($this->input, $expected, $this->byteOffset, $expectedLength) === 0;
    }

    /**
     * Returns the requested number of characters from the current offset.
     *
     * @throws ParserInputException When the length cannot be consumed.
     */
    public function take(int $length): string
    {
        $this->ensureCanConsume($length);

        $end = self::byteOffsetAfter($this->input, $this->byteOffset, $length);

        return substr($this->input, $this->byteOffset, $end - $this->byteOffset);
    }

    /**
     * Throws an exception if the input cannot be consumed.
     *
     * @throws ParserInputException When the length cannot be consumed.
     */
    private function ensureCanConsume(int $length): void
    {
        if (!$this->canConsume($length)) {
            throw new ParserInputException('The requested length exceeds the remaining input.');
        }
    }

    /**
     * Returns the byte length of a character at the given byte offset.
     */
    private static function byteLengthAt(string $input, int $byteOffset): int
    {
        $byte = ord($input[$byteOffset]);

        return match (true) {
            $byte <= 0x7f => 1,
            $byte <= 0xdf => 2,
            $byte <= 0xef => 3,
            default => 4,
        };
    }

    /**
     * Returns the byte offset after the given number of characters.
     */
    private static function byteOffsetAfter(string $input, int $byteOffset, int $length): int
    {
        for ($i = 0; $i < $length; ++$i) {
            $byteOffset += self::byteLengthAt($input, $byteOffset);
        }
        return $byteOffset;
    }
}
