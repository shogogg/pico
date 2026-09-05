<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Exceptions\ParserException;
use Pico\Parsers\OneOfParser;
use Pico\Parsers\ParserInput;

describe('OneOfParser', function (): void {
    it('should reject an invalid UTF-8 character set', function (string $characters): void {
        new OneOfParser($characters);
    })->with([
        'invalid byte' => "\x80",
        'incomplete multibyte character' => "\xE3\x81",
    ])->throws(ParserException::class, 'The character set must be valid UTF-8.');

    it('should reject an empty character set', function (): void {
        new OneOfParser('');
    })->throws(ParserException::class, 'The character set must not be empty.');
});

describe('OneOfParser::parseInput', function (): void {
    it('should return a success result when the current character is in the character set', function (
        string $characters,
        string $input,
        int $offset,
        string $expected,
    ): void {
        // Act
        $actual = new OneOfParser($characters)->parseInput(new ParserInput($input, $offset));

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        'ASCII character' => ['abc', 'bcd', 0, 'b'],
        'multibyte character' => ['あいう', 'えおい', 2, 'い'],
    ]);

    it('should return a failure result when the current character is not in the character set', function (): void {
        // Act
        $actual = new OneOfParser('abc')->parseInput(new ParserInput('xyz'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should return a failure result when the input is at its end', function (): void {
        // Act
        $actual = new OneOfParser('abc')->parseInput(new ParserInput('abc', 3));

        // Assert
        expect($actual)->toBeFailure();
    });
});
