<?php

use Pico\Contracts\ParserResult;
use Pest\Expectation;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

// pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeFailure', function () {
    return expect(parserResult($this)->isFailure())->toBeTrue();
});
expect()->extend('toBeSuccess', function () {
    return expect(parserResult($this)->isSuccess())->toBeTrue();
});
/**
 * @template T
 * @param T $output
 */
expect()->extend('toBeSuccessEqualTo', function ($output) {
    $result = parserResult($this);
    return expect($result->isSuccess())
        ->toBeTrue()
        ->and($result->output())
        ->toEqual($output);
});
/**
 * @template T
 * @param T $output
 */
expect()->extend('toBeSuccessOf', function ($output) {
    $result = parserResult($this);
    return expect($result->isSuccess())
        ->toBeTrue()
        ->and($result->output())
        ->toBe($output);
});
/**
 * @template T
 * @param T $output
 */
expect()->extend('toBeSuccessWith', function ($output, int $consumedLength) {
    $result = parserResult($this);
    return expect($result->isSuccess())->toBeTrue()
        ->and($result->output())->toBe($output)
        ->and($result->consumedLength())->toBe($consumedLength);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param Expectation<*> $expectation
 * @return ParserResult<*>
 */
function parserResult(Expectation $expectation): ParserResult
{
    if (!$expectation->value instanceof ParserResult) {
        throw new LogicException('The expectation value must implement ParserResult.');
    }
    return $expectation->value;
}
