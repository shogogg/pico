<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use function Pico\Parsers\failure;
use function Pico\Parsers\success;

describe('Pico\\Parsers\\success()', function (): void {
    it('should return a Success instance with the supplied output and consumed length', function (): void {
        $actual = success('value', 5);
        expect($actual)->toBeSuccessWith(
            output: 'value',
            consumedLength: 5,
        );
    });
});

describe('Pico\\Parsers\\failure()', function (): void {
    it('should return a Failure instance', function (): void {
        $actual = failure();
        expect($actual)->toBeFailure();
    });
});
