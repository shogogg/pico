<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\CharParser;
use Pico\Parsers\ExceptParser;
use Pico\Parsers\ParserInput;
use Pico\Parsers\StringParser;

describe('ExceptParser::parseInput', function (): void {
    it('should fail without consuming input when the exclusion parser succeeds', function (): void {
        // Act
        $actual = new ExceptParser(
            new StringParser('apple'),
            new CharParser('a'),
        )->parseInput(new ParserInput('apple'));

        // Assert
        expect($actual)
            ->toBeFailure()
            ->consumedLength()->toBe(0);
    });

    it('should return the parser result when the exclusion parser fails', function (): void {
        // Act
        $actual = new ExceptParser(
            new CharParser('a'),
            new CharParser('b'),
        )->parseInput(new ParserInput('apple'));

        // Assert
        expect($actual)->toBeSuccessOf('a', 1);
    });

    it('should return the parser failure when the exclusion parser fails', function (): void {
        // Act
        $actual = new ExceptParser(
            new CharParser('b'),
            new CharParser('c'),
        )->parseInput(new ParserInput('apple'));

        // Assert
        expect($actual)->toBeFailure();
    });
});
