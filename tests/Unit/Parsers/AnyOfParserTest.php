<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Parsers\AnyOfParser;
use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;

describe('AnyOfParser::parseInput', function (): void {
    it('should return the first successful result', function (): void {
        // Act
        $actual = new AnyOfParser(
            new CharParser('A'),
            new CharParser('B'),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should try the next parser after a failure', function (): void {
        // Act
        $actual = new AnyOfParser(
            new CharParser('Z'),
            new CharParser('A'),
        )->parseInput(new ParserInput('xABC', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should fail when every parser fails', function (): void {
        // Act
        $actual = new AnyOfParser(
            new CharParser('X'),
            new CharParser('Y'),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail when no parsers are given', function (): void {
        // Act
        $actual = new AnyOfParser()->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeFailure();
    });
});
