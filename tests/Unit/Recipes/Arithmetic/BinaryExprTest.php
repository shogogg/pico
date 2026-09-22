<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Recipes\Arithmetic;

use Pico\Recipes\Arithmetic\BinaryExpr;
use Pico\Recipes\Arithmetic\BinaryOperator;
use Pico\Recipes\Arithmetic\NumberExpr;

describe('BinaryExpr::evaluate', function (): void {
    it('should evaluate a binary operation', function (BinaryOperator $operator, int|float $expected): void {
        // Arrange
        $expr = new BinaryExpr(new NumberExpr('6'), $operator, new NumberExpr('2'));

        // Act
        $actual = $expr->evaluate();

        // Assert
        expect($actual)->toBe($expected);
    })->with([
        'addition' => [BinaryOperator::Add, 8],
        'subtraction' => [BinaryOperator::Subtract, 4],
        'multiplication' => [BinaryOperator::Multiply, 12],
        'division' => [BinaryOperator::Divide, 3.0],
    ]);

    it('should throw when evaluating division by zero', function (): void {
        // Arrange
        $expr = new BinaryExpr(new NumberExpr('1'), BinaryOperator::Divide, new NumberExpr('0'));

        // Act
        $evaluate = static fn (): int|float => $expr->evaluate();

        // Assert
        expect($evaluate)->toThrow(\DivisionByZeroError::class);
    });
});
