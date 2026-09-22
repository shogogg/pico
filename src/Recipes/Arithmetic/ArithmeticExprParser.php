<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Arithmetic;

use Pico\Contracts\Parser;
use Pico\Pico;

/**
 * Arithmetic expression parser recipe.
 */
final class ArithmeticExprParser
{
    /**
     * Creates a parser for a complete arithmetic expression.
     *
     * @return Parser<Expr>
     */
    public static function expr(): Parser
    {
        $expression = Pico::recursive(function (Parser $self): Parser {
            // number = 1*DIGIT [ "." 1*DIGIT ]
            $digits = Pico::digit()->repeat(min: 1)->concat();
            $number = self::lexeme(
                Pico::join(
                    $digits, // integer-part
                    Pico::join(Pico::char('.'), $digits)->optional(), // fraction part
                )->map(
                    static fn (string $x): NumberExpr => new NumberExpr($x)
                ),
            );

            // primary = number / "(" expression ")"
            $primary = Pico::anyOf(
                $number,
                Pico::between(self::lexeme(Pico::char('(')), $self, self::lexeme(Pico::char(')'))),
            );

            // term = primary *( ("*" / "/") primary )
            $term = Pico::pair(
                $primary,
                Pico::pair(self::operator('*/'), $primary)->repeat(),
            )->map(
                static fn (array $output): Expr => self::fold($output[0], $output[1]),
            );

            // expression = term *( ("+" | "-") term )
            return Pico::pair(
                $term,
                Pico::pair(self::operator('+-'), $term)->repeat(),
            )->map(
                static fn (array $output): Expr => self::fold($output[0], $output[1]),
            );
        });

        return Pico::skipRight($expression, self::whitespaces())->complete();
    }

    /**
     * Creates a parser for the evaluated value of a complete arithmetic expression.
     *
     * @return Parser<int|float>
     */
    public static function value(): Parser
    {
        return self::expr()->map(static fn (Expr $expr): int|float => $expr->evaluate());
    }

    /**
     * @param list<array{BinaryOperator, Expr}> $operations
     */
    private static function fold(Expr $expr, array $operations): Expr
    {
        foreach ($operations as [$operator, $right]) {
            $expr = new BinaryExpr($expr, $operator, $right);
        }
        return $expr;
    }

    /**
     * Reads an element after optional whitespaces.
     *
     * @template T
     * @param Parser<T> $parser
     * @return Parser<T>
     */
    private static function lexeme(Parser $parser): Parser
    {
        return Pico::skipLeft(self::whitespaces(), $parser);
    }

    /**
     * Creates an operator parser.
     *
     * @param string $operators
     * @return Parser<BinaryOperator>
     */
    private static function operator(string $operators): Parser
    {
        return self::lexeme(
            Pico::oneOf($operators)->map(
                static fn (string $op): BinaryOperator => BinaryOperator::from($op),
            ),
        );
    }

    /**
     * Reads and discards zero or more whitespace characters.
     *
     * @return Parser<string>
     */
    private static function whitespaces(): Parser
    {
        return Pico::whitespace()->repeat()->skip();
    }
}
