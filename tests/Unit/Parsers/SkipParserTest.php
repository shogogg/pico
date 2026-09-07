<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Parsers\ParserInput;
use Pico\Parsers\SkipParser;
use Pico\Parsers\StringParser;
use Pico\Pico;

describe('SkipParser::parseInput', function (): void {
    it('should discard outputs while preserving the total consumed length', function (): void {
        // Act
        $actual = new SkipParser(
            Pico::char('A'),
            new StringParser('BC'),
        )->parseInput(new ParserInput('ABCD'));

        // Assert
        expect($actual)->toBeSuccessOf('', 3);
    });

    it('should fail when a parser in the sequence fails', function (): void {
        // Act
        $actual = new SkipParser(
            Pico::char('A'),
            Pico::char('B'),
        )->parseInput(new ParserInput('AX'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should succeed without consuming input when no parsers are given', function (): void {
        // Act
        $actual = new SkipParser()->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    });
});
