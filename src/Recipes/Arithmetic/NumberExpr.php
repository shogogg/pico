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
 * A numeric arithmetic expression.
 */
final readonly class NumberExpr implements Expr
{
    public function __construct(public string $literal)
    {
    }

    public function evaluate(): int|float
    {
        return str_contains($this->literal, '.') ? (float) $this->literal : (int) $this->literal;
    }
}
