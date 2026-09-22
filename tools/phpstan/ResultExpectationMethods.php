<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\PHPStan;

use Pest\Expectation;

/**
 * PHPStan declarations for Pico's custom Pest expectations.
 */
interface ResultExpectationMethods
{
    /**
     * @return Expectation<int>
     */
    public function consumedLength(): Expectation;

    /**
     * @return Expectation<mixed>
     */
    public function output(): Expectation;

    /**
     * @return Expectation<mixed>
     */
    public function toBeFailure(): Expectation;

    /**
     * @return Expectation<mixed>
     */
    public function toBeSuccess(): Expectation;

    /**
     * @template T
     * @param T $output
     * @return Expectation<T>
     */
    public function toBeSuccessEqualTo($output): Expectation;

    /**
     * @template T
     * @param T $output
     * @return Expectation<T>
     */
    public function toBeSuccessOf($output): Expectation;

    /**
     * @template T
     * @param T $output
     * @return Expectation<T>
     */
    public function toBeSuccessWith($output, int $consumedLength): Expectation;
}
