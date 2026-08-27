<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;

describe('CharParser', function (): void {
    it('should reject a character that is not exactly one character long', function (string $char): void {
        new CharParser($char);
    })->with([
        '',
        'ab',
        'あい',
    ])->throws(LogicException::class, 'The character must be exactly one character long.');
});

describe('CharParser::parseInput', function (): void {
    it('should return a success result when the character matches at the current input offset', function (
        string $char,
        string $input,
        int $offset,
    ): void {
        // Act
        $actual = new CharParser($char)->parseInput(new ParserInput($input, $offset));

        // Assert
        expect($actual)->toBeSuccessOf($char, 1);
    })->with([
        ['a', 'apple', 0],
        ['あ', 'あいう', 0],
        ['😀', 'a😀b', 1],
    ]);

    it('should return a failure result when the current character does not match', function (): void {
        // Act
        $actual = new CharParser('a')->parseInput(new ParserInput('banana'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should return a failure result when the input is at its end', function (): void {
        // Act
        $actual = new CharParser('a')->parseInput(new ParserInput('a', 1));

        // Assert
        expect($actual)->toBeFailure();
    });
});
