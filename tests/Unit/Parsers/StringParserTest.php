<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\ParserInput;
use Pico\Parsers\StringParser;

describe('StringParser', function (): void {
    it('should expose the expected string', function (): void {
        // Act
        $parser = new StringParser('abc');

        // Assert
        expect($parser->expected)->toBe('abc');
    });
});

describe('parseInput', function (): void {
    it('should return a success result when the expected string matches at the current input offset', function (
        string $expected,
        string $input,
        int $offset,
        int $consumedLength,
    ): void {
        // Act
        $actual = new StringParser($expected)->parseInput(new ParserInput($input, $offset));

        // Assert
        expect($actual)->toBeSuccessOf($expected, $consumedLength);
    })->with([
        ['abc', 'abcdef', 0, 3],
        ['abc', 'xabcdef', 1, 3],
        ['あい', 'あいう', 0, 2],
    ]);

    it('should return a failure result when the expected string is empty', function (): void {
        // Act
        $actual = new StringParser('')->parseInput(new ParserInput('abc'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should return a failure result when the expected string does not match', function (
        string $expected,
        string $input,
        int $offset,
    ): void {
        // Act
        $actual = new StringParser($expected)->parseInput(new ParserInput($input, $offset));

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        ['abc', 'abd', 0],
        ['abc', 'ab', 0],
        ['a', 'a', 1],
    ]);
});
