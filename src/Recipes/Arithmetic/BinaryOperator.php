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
 * A binary arithmetic operator.
 */
enum BinaryOperator: string
{
    case Add = '+';
    case Subtract = '-';
    case Multiply = '*';
    case Divide = '/';
}
