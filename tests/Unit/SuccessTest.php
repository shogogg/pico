<?php
/*
 * Copyright (c) 2025 shogogg
 * This code is licensed under MIT license (see LICENSE.md for details)
 */
declare(strict_types=1);

use Pico\Success;

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
