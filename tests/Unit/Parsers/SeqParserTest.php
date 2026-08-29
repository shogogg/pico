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
use Pico\Parsers\SeqParser;

describe('SeqParser::parseInput', function (): void {
    it('should parse each parser from the current input offset', function (): void {
        // Act
        $actual = new SeqParser(
            new CharParser('A'),
            new CharParser('B'),
            new CharParser('C'),
        )->parseInput(new ParserInput('xABC', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'B', 'C'], 3);
    });

    it('should fail when a parser in the sequence fails', function (string $input): void {
        // Act
        $actual = new SeqParser(
            new CharParser('A'),
            new CharParser('B'),
            new CharParser('C'),
        )->parseInput(new ParserInput($input));

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'XBC',
        'AXC',
        'ABX',
    ]);

    it('should succeed without consuming input when the sequence is empty', function (): void {
        // Act
        $actual = new SeqParser()->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf([], 0);
    });
});
