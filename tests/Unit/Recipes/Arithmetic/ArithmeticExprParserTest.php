<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Recipes\Arithmetic;

use Pico\Recipes\Arithmetic\ArithmeticExprParser;
use Pico\Recipes\Arithmetic\BinaryExpr;
use Pico\Recipes\Arithmetic\BinaryOperator;
use Pico\Recipes\Arithmetic\NumberExpr;

describe('ArithmeticExprParser::expr', function (): void {
    it('should create a number expression from its literal', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse('003.140');

        // Assert
        expect($actual)->toBeSuccessEqualTo(new NumberExpr('003.140'));
    });

    it('should give multiplication precedence over addition in the expression tree', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse('1 + 2 * 3');

        // Assert
        expect($actual)->toBeSuccessEqualTo(new BinaryExpr(
            new NumberExpr('1'),
            BinaryOperator::Add,
            new BinaryExpr(new NumberExpr('2'), BinaryOperator::Multiply, new NumberExpr('3')),
        ));
    });

    it('should associate subtraction to the left in the expression tree', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse('10 - 3 - 2');

        // Assert
        expect($actual)->toBeSuccessEqualTo(new BinaryExpr(
            new BinaryExpr(new NumberExpr('10'), BinaryOperator::Subtract, new NumberExpr('3')),
            BinaryOperator::Subtract,
            new NumberExpr('2'),
        ));
    });

    it('should preserve parenthesized precedence in the expression tree', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse('(1 + 2) * 3');

        // Assert
        expect($actual)->toBeSuccessEqualTo(new BinaryExpr(
            new BinaryExpr(new NumberExpr('1'), BinaryOperator::Add, new NumberExpr('2')),
            BinaryOperator::Multiply,
            new NumberExpr('3'),
        ));
    });

    it('should accept leading whitespace', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse(" \t1");

        // Assert
        expect($actual)->toBeSuccessEqualTo(new NumberExpr('1'));
    });

    it('should accept whitespace before each subsequent token', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse("1 \t+ \n2");

        // Assert
        expect($actual)->toBeSuccessEqualTo(new BinaryExpr(
            new NumberExpr('1'),
            BinaryOperator::Add,
            new NumberExpr('2'),
        ));
    });

    it('should accept trailing whitespace', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse("1\t \n");

        // Assert
        expect($actual)->toBeSuccessEqualTo(new NumberExpr('1'));
    });

    it('should require the entire input to be an arithmetic expression', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse('1x');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should parse a zero divisor without evaluating it', function (): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse('1 / 0');

        // Assert
        expect($actual)->toBeSuccess();
    });

    it('should fail for an incomplete or invalid arithmetic expression', function (string $input): void {
        // Act
        $actual = ArithmeticExprParser::expr()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'empty input' => '',
        'unary minus' => '-1',
        'unary plus' => '+1',
        'missing right operand' => '1 +',
        'missing closing parenthesis' => '(1 + 2',
        'unexpected closing parenthesis' => '1 + 2)',
        'adjacent numbers' => '1 2',
        'decimal point without fractional digits' => '1.',
        'multiple decimal points' => '1..2',
        'division without right operand' => '1 /',
    ]);
});

describe('ArithmeticExprParser::value', function (): void {
    it('should evaluate a complete arithmetic expression', function (string $input, int|float $expected): void {
        // Act
        $actual = ArithmeticExprParser::value()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected);
    })->with([
        'addition' => ['1 + 2', 3],
        'subtraction' => ['5 - 2', 3],
        'multiplication' => ['3 * 4', 12],
        'division' => ['7 / 2', 3.5],
        'operator precedence' => ['1 + 2 * 3 - 4 / 2', 5.0],
        'parenthesized division' => ['20 / (5 / 2)', 8.0],
        'nested parentheses' => ['((1 + 2) * (3 - 4 / 2))', 3.0],
        'decimal values' => ['1.5 * 2 + 0.25', 3.25],
        'whitespace' => ["\n (1 + 2.5) * 4 \t", 14.0],
    ]);
});
