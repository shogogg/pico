<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\PHPStan;

use LogicException;
use Pest\Expectation;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Makes Pico's custom Pest expectations available to PHPStan.
 */
final readonly class ResultExpectationMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    /** @var list<string> */
    private const array METHOD_NAMES = [
        'consumedLength',
        'output',
        'toBeFailure',
        'toBeSuccess',
        'toBeSuccessOf',
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->is(Expectation::class)
            && in_array($methodName, self::METHOD_NAMES, true);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if (!in_array($methodName, self::METHOD_NAMES, true)) {
            throw new LogicException(sprintf('Unknown custom expectation: %s.', $methodName));
        }

        return $this->reflectionProvider
            ->getClass(ResultExpectationMethods::class)
            ->getNativeMethod($methodName);
    }
}
