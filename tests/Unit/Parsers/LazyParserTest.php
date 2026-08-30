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
use Pico\Parsers\CharParser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\ParserInput;

describe('LazyParser::parseInput', function (): void {
    it('should delegate parsing to the parser returned by the factory', function (): void {
        // Act
        $actual = new LazyParser(static fn (): Parser => new CharParser('A'))
            ->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should not evaluate the factory until parsing starts', function (): void {
        $wasCalled = false;
        $parser = new LazyParser(function () use (&$wasCalled): Parser {
            $wasCalled = true;

            return new CharParser('A');
        });

        // Assert
        expect($wasCalled)->toBeFalse();

        // Act
        $parser->parseInput(new ParserInput('ABC'));

        // Assert
        expect($wasCalled)->toBeTrue();
    });

    it('should evaluate the factory only once', function (): void {
        $calls = 0;
        $parser = new LazyParser(function () use (&$calls): Parser {
            ++$calls;

            return new CharParser('A');
        });

        // Act
        $parser->parseInput(new ParserInput('ABC'));
        $parser->parseInput(new ParserInput('ABC'));

        // Assert
        expect($calls)->toBe(1);
    });
});
