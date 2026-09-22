<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Recipes\Arithmetic;

use Pico\Recipes\Arithmetic\NumberExpr;

describe('NumberExpr', function (): void {
    it('should retain its numeric literal', function (): void {
        // Arrange
        $expr = new NumberExpr('003.140');

        // Assert
        expect($expr->literal)->toBe('003.140');
    });

    describe('evaluate', function (): void {
        it('should convert its literal to a numeric value', function (string $literal, int|float $expected): void {
            // Arrange
            $expr = new NumberExpr($literal);

            // Act
            $actual = $expr->evaluate();

            // Assert
            expect($actual)->toBe($expected);
        })->with([
            'integer literal' => ['42', 42],
            'decimal literal' => ['3.14', 3.14],
        ]);
    });
});
