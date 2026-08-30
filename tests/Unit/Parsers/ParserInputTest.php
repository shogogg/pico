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

describe('ParserInput', function (): void {
    it('should reject invalid UTF-8 input', function (): void {
        // Act
        $action = fn (): ParserInput => new ParserInput(chr(255));

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The input must be valid UTF-8.');
    });

    it('should reject an offset outside the input', function (int $offset): void {
        // Act
        $action = fn (): ParserInput => new ParserInput('abc', $offset);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The offset must be within the input.');
    })->with([-1, 4]);
});

describe('ParserInput::canConsume', function (): void {
    it('should determine whether the requested character length is available', function (
        string $input,
        int $offset,
        int $length,
        bool $expected,
    ): void {
        // Act
        $actual = new ParserInput($input, $offset);

        // Assert
        expect($actual->canConsume($length))->toBe($expected);
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
        $action = fn (): bool => new ParserInput('abc')->canConsume(-1);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The length must not be negative.');
    });
});

describe('ParserInput::take', function (): void {
    it('should return the requested characters from the current offset', function (): void {
        // Act
        $actual = new ParserInput('a😀b', 1)->take(2);

        // Assert
        expect($actual)->toBe('😀b');
    });

    it('should reject a character length that cannot be consumed', function (): void {
        // Act
        $action = fn (): string => new ParserInput('abc', 2)->take(2);

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The requested length exceeds the remaining input.');
    });
});

describe('ParserInput::current', function (): void {
    it('should return the current character', function (): void {
        // Act
        $actual = new ParserInput('a😀b', 1)->current();

        // Assert
        expect($actual)->toBe('😀');
    });

    it('should reject an input at its end', function (): void {
        // Act
        $action = fn (): string => new ParserInput('a', 1)->current();

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The requested length exceeds the remaining input.');
    });
});

describe('ParserInput::startsWith', function (): void {
    it('should determine whether the remaining input starts with the expected string', function (
        string $input,
        int $offset,
        string $expected,
        bool $matches,
    ): void {
        // Act
        $actual = new ParserInput($input, $offset);

        // Assert
        expect($actual->startsWith($expected))->toBe($matches);
    })->with([
        ['abcdef', 0, 'abc', true],
        ['xabcdef', 1, 'abc', true],
        ['あいう', 0, 'あい', true],
        ['abcdef', 0, 'abd', false],
        ['abcdef', 4, 'cde', false],
    ]);
});

describe('ParserInput::advanced', function (): void {
    it('should return a new input with the character offset advanced', function (): void {
        // Act
        $actual = new ParserInput('a😀b', 1)->advanced(1);

        // Assert
        expect($actual)
            ->input->toBe('a😀b')
            ->offset->toBe(2);
    });
});

describe('ParserInput::isAtEnd', function (): void {
    it('should determine whether the current offset is at the end of the input', function (
        string $input,
        int $offset,
        bool $expected,
    ): void {
        // Act
        $actual = new ParserInput($input, $offset);

        // Assert
        expect($actual->isAtEnd())->toBe($expected);
    })->with([
        ['', 0, true],
        ['abc', 2, false],
        ['abc', 3, true],
        ['a😀', 2, true],
    ]);
});
