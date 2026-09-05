<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;
use Pico\Parsers\SkipRightParser;

describe('SkipRightParser::parseInput', function (): void {
    it('should return the left output after consuming both parsers', function (): void {
        // Act
        $actual = new SkipRightParser(
            new CharParser('A'),
            new CharParser(';'),
        )->parseInput(new ParserInput('A;'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 2);
    });

    it('should fail when the left parser fails', function (): void {
        // Act
        $actual = new SkipRightParser(
            new CharParser('A'),
            new CharParser(';'),
        )->parseInput(new ParserInput('B;'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail when the right parser fails', function (): void {
        // Act
        $actual = new SkipRightParser(
            new CharParser('A'),
            new CharParser(';'),
        )->parseInput(new ParserInput('AB'));

        // Assert
        expect($actual)->toBeFailure();
    });
});
