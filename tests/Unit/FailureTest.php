<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Failure;

describe('::getInstance()', function (): void {
    it('should return a new instance of Failure', function (): void {
        $actual = Failure::getInstance();
        expect($actual)->toBeInstanceOf(Failure::class);
    });
});

describe('->isSuccess()', function (): void {
    it('should be false', function (): void {
        $failure = Failure::getInstance();
        expect($failure->isSuccess())->toBeFalse();
    });
});

describe('->isFailure()', function (): void {
    it('should be true', function (): void {
        $failure = Failure::getInstance();
        expect($failure->isFailure())->toBeTrue();
    });
});

describe('->consumedLength()', function (): void {
    it('should be zero', function (): void {
        $failure = Failure::getInstance();
        expect($failure->consumedLength())->toBe(0);
    });
});

describe('->concat()', function (): void {
    it('should return itself', function (): void {
        // Arrange
        $failure = Failure::getInstance();

        // Act
        $actual = $failure->concat();

        // Assert
        expect($actual)->toBe($failure);
    });
});

describe('->output()', function (): void {
    it('should throw an exception because there is no output', function (): void {
        // Arrange
        $failure = Failure::getInstance();

        expect(function () use ($failure): void {
            $failure->output();
        })->toThrow(
            LogicException::class,
            'There is no value',
        );
    });
});

describe('->map()', function (): void {
    it('should return a failure', function (): void {
        // Arrange
        $failure = Failure::getInstance();

        // Act
        $actual = $failure->map(static fn (): string => 'value');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should not call the transformation', function (): void {
        // Arrange
        $failure = Failure::getInstance();
        $wasCalled = false;

        // Act
        $failure->map(function () use (&$wasCalled): string {
            $wasCalled = true;
            return 'value';
        });

        // Assert
        expect($wasCalled)->toBeFalse();
    });
});

describe('->join()', function (): void {
    it('should return itself', function (): void {
        // Arrange
        $failure = Failure::getInstance();

        // Act
        $actual = $failure->join();

        // Assert
        expect($actual)->toBe($failure);
    });
});
