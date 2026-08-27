<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\AnyCharParser;
use Pico\Parsers\ParserInput;

describe('AnyCharParser::parseInput', function (): void {
    it('should match the character at the current input offset', function (
        string $input,
        int $offset,
        string $expected,
    ): void {
        // Act
        $actual = new AnyCharParser()->parseInput(new ParserInput($input, $offset));

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        ['book', 0, 'b'],
        ['あいう', 0, 'あ'],
        ['a😀b', 1, '😀'],
    ]);

    it('should fail when the input is at its end', function (): void {
        // Act
        $actual = new AnyCharParser()->parseInput(new ParserInput('a', 1));

        // Assert
        expect($actual)->toBeFailure();
    });
});
