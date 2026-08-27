<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Pico\Contracts\ParserResult;
use Pico\Failure;
use Pico\Success;

/**
 * Creates a successful parsing result.
 *
 * @template T
 * @param T $output
 * @return ParserResult<T>
 */
function success(mixed $output, int $consumedLength): ParserResult
{
    return Success::of($output, $consumedLength);
}

/**
 * Creates a failed parsing result.
 *
 * @return ParserResult<never>
 */
function failure(): ParserResult
{
    return Failure::getInstance();
}
