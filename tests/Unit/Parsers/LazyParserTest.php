<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Contracts\Parser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\ParserInput;
use Pico\Pico;

describe('LazyParser::parseInput', function (): void {
    it('should delegate parsing to the parser returned by the factory', function (): void {
        // Act
        $actual = new LazyParser(static fn (): Parser => Pico::char('A'))
            ->parseInput(ParserInput::of('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should not evaluate the factory until parsing starts', function (): void {
        $wasCalled = false;
        $parser = new LazyParser(function () use (&$wasCalled): Parser {
            $wasCalled = true;

            return Pico::char('A');
        });

        // Assert
        expect($wasCalled)->toBeFalse();

        // Act
        $parser->parseInput(ParserInput::of('ABC'));

        // Assert
        expect($wasCalled)->toBeTrue();
    });

    it('should evaluate the factory only once', function (): void {
        $calls = 0;
        $parser = new LazyParser(function () use (&$calls): Parser {
            ++$calls;

            return Pico::char('A');
        });

        // Act
        $parser->parseInput(ParserInput::of('ABC'));
        $parser->parseInput(ParserInput::of('ABC'));

        // Assert
        expect($calls)->toBe(1);
    });
});
