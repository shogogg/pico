<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\ParserInput;
use Pico\Parsers\RangeParser;
use Pico\Exceptions\ParserException;

describe('RangeParser', function (): void {
    it('should reject invalid UTF-8 bounds', function (string $from, string $to): void {
        new RangeParser($from, $to);
    })->with([
        'invalid byte start' => ["\x80", 'z'],
        'incomplete multibyte start' => ["\xE3\x81", 'z'],
        'invalid byte end' => ['a', "\x80"],
        'incomplete multibyte end' => ['a', "\xE3\x81"],
    ])->throws(ParserException::class, 'Range bounds must be valid UTF-8.');

    it('should reject bounds that are not exactly one UTF-8 character', function (string $from, string $to): void {
        new RangeParser($from, $to);
    })->with([
        ['', 'z'],
        ['ab', 'z'],
        ['あ', 'いう'],
    ])->throws(ParserException::class, 'Range bounds must be exactly one UTF-8 character.');

    it('should reject a range whose start exceeds its end', function (): void {
        new RangeParser('お', 'あ');
    })->throws(ParserException::class, 'The range start must not exceed the range end.');
});

describe('RangeParser::parseInput', function (): void {
    it('should match a character whose Unicode code point is within the range', function (
        string $from,
        string $to,
        string $input,
        string $expected,
    ): void {
        // Act
        $actual = new RangeParser($from, $to)->parseInput(new ParserInput($input));

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        'ASCII range start' => ['a', 'z', 'apple', 'a'],
        'ASCII range end' => ['a', 'z', 'zebra', 'z'],
        'Hiragana range middle' => ['あ', 'お', 'えお', 'え'],
        'emoji range' => ['😀', '😂', '😁!', '😁'],
    ]);

    it('should fail when the current character is outside the Unicode code point range', function (): void {
        // Act
        $actual = new RangeParser('あ', 'お')->parseInput(new ParserInput('かきく'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail when the input is at its end', function (): void {
        // Act
        $actual = new RangeParser('a', 'z')->parseInput(new ParserInput('abc', 3));

        // Assert
        expect($actual)->toBeFailure();
    });
});
