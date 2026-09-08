<?php
/*
 * Copyright (c) 2025 shogogg
 * This code is licensed under MIT license (see LICENSE.md for details)
 */
declare(strict_types=1);

use Pico\Success;
use Pico\Exceptions\ParserException;

describe('::of', function (): void {
    it('should returns a new instance of Success', function (): void {
        $success = Success::of('value', 5);
        expect($success)->toBeInstanceOf(Success::class);
    });
});

describe('->isSuccess()', function (): void {
    it('should be true', function (): void {
        $success = Success::of('value', 5);
        expect($success->isSuccess())->toBeTrue();
    });
});

describe('->isFailure()', function (): void {
    it('should be false', function (): void {
        $success = Success::of('value', 5);
        expect($success->isFailure())->toBeFalse();
    });
});

describe('->consumedLength()', function (): void {
    it('should be the length of the consumed input', function (string $output, int $consumedLength): void {
        $success = Success::of($output, $consumedLength);
        expect($success->consumedLength())->toBe($consumedLength);
    })->with([
        ['book', 4],
        ['computer', 8],
        ['eraser', 6],
        ['highlight', 9],
    ]);
});

describe('->concat()', function (): void {
    it('should recursively concatenate nested array output and preserve the consumed length', function (): void {
        // Act
        $actual = Success::of(['one', ['-', 2], new class () implements \Stringable {
            public function __toString(): string
            {
                return '!';
            }
        }], 5)->concat();

        // Assert
        expect($actual)->toBeSuccessOf('one-2!', 5);
    });

    it('should throw when a nested array contains a non-stringable value', function (): void {
        // Act
        $action = static fn () => Success::of(['one', ['two', new \stdClass()]], 5)->concat();

        // Assert
        expect($action)->toThrow(ParserException::class, 'The value must be a scalar or implement Stringable.');
    });
});

describe('->output()', function (): void {
    it('should returns the output', function (string $output, int $consumedLength): void {
        $success = Success::of($output, $consumedLength);
        expect($success->output())->toBe($output);
    })->with([
        ['book', 4],
        ['computer', 8],
        ['eraser', 6],
        ['highlight', 9],
    ]);
});

describe('->map()', function (): void {
    it('should transform the output and preserve the consumed length', function (): void {
        // Act
        $actual = Success::of('abc', 5)->map(static fn (string $output): int => strlen($output));

        // Assert
        expect($actual)->toBeSuccessOf(3, 5);
    });
});

describe('->join()', function (): void {
    it('should join an array output and preserve the consumed length', function (): void {
        // Act
        $actual = Success::of(['first', 3], 5)->join(', ');

        // Assert
        expect($actual)->toBeSuccessOf('first, 3', 5);
    });

    it('should stringify a scalar output', function (): void {
        // Act
        $actual = Success::of(42, 5)->join();

        // Assert
        expect($actual)->toBeSuccessOf('42', 5);
    });

    it('should stringify a Stringable output', function (): void {
        // Act
        $actual = Success::of(new class () implements \Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        }, 5)->join();

        // Assert
        expect($actual)->toBeSuccessOf('stringable', 5);
    });

    it('should throw when an array output contains a non-stringable value', function (): void {
        // Act
        $action = static fn () => Success::of(['first', new \stdClass()], 5)->join();

        // Assert
        expect($action)->toThrow(ParserException::class, 'The value must be a scalar or implement Stringable.');
    });

    it('should throw when a non-array output cannot be stringified', function (): void {
        // Act
        $action = static fn () => Success::of(null, 5)->join();

        // Assert
        expect($action)->toThrow(ParserException::class, 'The value must be a scalar or implement Stringable.');
    });
});
