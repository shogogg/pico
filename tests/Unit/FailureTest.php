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

describe('->output()', function (): void {
    it('should throw an exception because there is no output', function (): void {
        $failure = Failure::getInstance();

        expect(function () use ($failure): void {
            $failure->output();
        })
            ->toThrow(LogicException::class, 'There is no value');
    });
});
