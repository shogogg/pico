<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Exceptions\ParserInputException;
use Pico\Parsers\ParserInput;

describe('ParserInput::advanced', function (): void {
    it('should advance by the requested character length', function (): void {
        // Arrange
        $input = ParserInput::of('a😀b');

        // Act
        $actual = $input->advanced(2);

        // Assert
        expect($actual->offset)->toBe(2);
    });

    it('should reject a negative character length', function (): void {
        // Act
        $action = fn (): ParserInput => ParserInput::of('abc')->advanced(-1);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The length must not be negative.');
    });

    it('should reject a character length that cannot be consumed', function (): void {
        // Arrange
        $input = ParserInput::of('abc')->advanced(2);

        // Act
        $action = fn (): ParserInput => $input->advanced(2);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The requested length exceeds the remaining input.');
    });

    it('should keep the offset when advancing by zero characters', function (): void {
        // Arrange
        $input = ParserInput::of('abc')->advanced(1);

        // Act
        $actual = $input->advanced(0);

        // Assert
        expect($actual->offset)->toBe(1);
    });
});

describe('ParserInput::byteOffset', function (): void {
    it('should return the current byte offset', function (): void {
        // Arrange
        $input = ParserInput::of('aあい')->advanced(2);

        // Act
        $actual = $input->byteOffset();

        // Assert
        expect($actual)->toBe(4);
    });
});

describe('ParserInput::canConsume', function (): void {
    it('should determine whether the requested character length is available', function (
        string $input,
        int $offset,
        int $length,
        bool $expected,
    ): void {
        // Arrange
        $parserInput = ParserInput::of($input)->advanced($offset);

        // Act
        $actual = $parserInput->canConsume($length);

        // Assert
        expect($actual)->toBe($expected);
    })->with([
        ['abc', 0, 0, true],
        ['abc', 1, 2, true],
        ['abc', 3, 0, true],
        ['あ', 0, 1, true],
        ['a😀b', 1, 2, true],
        ['abc', 3, 1, false],
        ['あ', 0, 2, false],
    ]);

    it('should reject a negative character length', function (): void {
        // Act
        $action = fn (): bool => ParserInput::of('abc')->canConsume(-1);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The length must not be negative.');
    });
});

describe('ParserInput::current', function (): void {
    it('should return the current character', function (): void {
        // Arrange
        $input = ParserInput::of('a😀b')->advanced(1);

        // Act
        $actual = $input->current();

        // Assert
        expect($actual)->toBe('😀');
    });

    it('should reject an input at its end', function (): void {
        // Arrange
        $input = ParserInput::of('a')->advanced(1);

        // Act
        $action = fn (): string => $input->current();

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The requested length exceeds the remaining input.');
    });
});

describe('ParserInput::isAtEnd', function (): void {
    it('should determine whether the current offset is at the end of the input', function (
        string $input,
        int $offset,
        bool $expected,
    ): void {
        // Arrange
        $parserInput = ParserInput::of($input)->advanced($offset);

        // Act
        $actual = $parserInput->isAtEnd();

        // Assert
        expect($actual)->toBe($expected);
    })->with([
        ['', 0, true],
        ['abc', 2, false],
        ['abc', 3, true],
        ['a😀', 2, true],
    ]);
});

describe('ParserInput::of', function (): void {
    it('should create an input at the beginning', function (): void {
        // Act
        $actual = ParserInput::of('a😀b');

        // Assert
        expect($actual)
            ->input->toBe('a😀b')
            ->offset->toBe(0);
    });

    it('should reject invalid UTF-8 input', function (): void {
        // Act
        $action = fn (): ParserInput => ParserInput::of(chr(255));

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The input must be valid UTF-8.');
    });
});

describe('ParserInput::startsWith', function (): void {
    it('should determine whether the remaining input starts with the expected string', function (
        string $input,
        int $offset,
        string $expected,
        bool $matches,
    ): void {
        // Arrange
        $parserInput = ParserInput::of($input)->advanced($offset);

        // Act
        $actual = $parserInput->startsWith($expected);

        // Assert
        expect($actual)->toBe($matches);
    })->with([
        ['abcdef', 0, 'abc', true],
        ['xabcdef', 1, 'abc', true],
        ['あいう', 0, 'あい', true],
        ['abcdef', 0, 'abd', false],
        ['abcdef', 4, 'cde', false],
    ]);
});

describe('ParserInput::take', function (): void {
    it('should return the requested characters from the current offset', function (): void {
        // Arrange
        $input = ParserInput::of('a😀b')->advanced(1);

        // Act
        $actual = $input->take(2);

        // Assert
        expect($actual)->toBe('😀b');
    });

    it('should reject a character length that cannot be consumed', function (): void {
        // Arrange
        $input = ParserInput::of('abc')->advanced(2);

        // Act
        $action = fn (): string => $input->take(2);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The requested length exceeds the remaining input.');
    });
});
