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
 * An arithmetic expression.
 */
interface Expr
{
    public function evaluate(): int|float;
}
