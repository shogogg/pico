<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Exceptions\ParserException;
use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;
use Pico\Parsers\RegExpParser;
use Pico\Parsers\SepByParser;

describe('SepByParser', function (): void {
    it('should reject a negative minimum item count', function (): void {
        // Act
        $action = static fn (): SepByParser => new SepByParser(
            new RegExpParser('\\d+'),
            new CharParser(','),
            min: -1,
        );

        // Assert
        expect($action)->toThrow(ParserException::class, 'The minimum item count must not be negative.');
    });
});

describe('SepByParser::parseInput', function (): void {
    it('should parse content separated by the separator', function (): void {
        // Act
        $actual = new SepByParser(
            new RegExpParser('\\d+'),
            new CharParser(','),
        )->parseInput(new ParserInput('1,22,333x'));

        // Assert
        expect($actual)->toBeSuccessOf(['1', '22', '333'], 8);
    });

    it('should succeed with no outputs when the first content parser fails and the minimum is zero', function (): void {
        // Act
        $actual = new SepByParser(
            new RegExpParser('\\d+'),
            new CharParser(','),
        )->parseInput(new ParserInput('abc'));

        // Assert
        expect($actual)->toBeSuccessOf([], 0);
    });

    it('should fail when fewer than the minimum item count matches', function (): void {
        // Act
        $actual = new SepByParser(
            new RegExpParser('\\d+'),
            new CharParser(','),
            min: 2,
        )->parseInput(new ParserInput('1x'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should not consume a trailing separator when the following content parser fails', function (): void {
        // Act
        $actual = new SepByParser(
            new RegExpParser('\\d+'),
            new CharParser(','),
        )->parseInput(new ParserInput('1,'));

        // Assert
        expect($actual)->toBeSuccessOf(['1'], 1);
    });
});
