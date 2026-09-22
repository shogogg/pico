<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Arithmetic;

/**
 * An arithmetic expression composed from two operands and an operator.
 */
final readonly class BinaryExpr implements Expr
{
    public function __construct(
        public Expr $left,
        public BinaryOperator $operator,
        public Expr $right,
    ) {
    }

    public function evaluate(): int|float
    {
        return match ($this->operator) {
            BinaryOperator::Add => $this->left->evaluate() + $this->right->evaluate(),
            BinaryOperator::Subtract => $this->left->evaluate() - $this->right->evaluate(),
            BinaryOperator::Multiply => $this->left->evaluate() * $this->right->evaluate(),
            BinaryOperator::Divide => (float) $this->left->evaluate() / $this->right->evaluate(),
        };
    }
}
