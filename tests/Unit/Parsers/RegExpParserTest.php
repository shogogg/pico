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
use Pico\Parsers\ParserInput;
use Pico\Parsers\RegExpParser;

describe('RegExpParser::parseInput', function (): void {
    it('should match the pattern at the current input offset', function (): void {
        // Act
        $actual = new RegExpParser('[A-Z]+')->parseInput(new ParserInput('_ABC', 1));

        // Assert
        expect($actual)->toBeSuccessOf('ABC', 3);
    });

    it('should preserve Unicode character lengths', function (): void {
        // Act
        $actual = new RegExpParser('[あ-お]+')->parseInput(new ParserInput('あいうえお'));

        // Assert
        expect($actual)->toBeSuccessOf('あいうえお', 5);
    });

    it('should fail when the pattern does not match at the current input offset', function (): void {
        // Act
        $actual = new RegExpParser('[A-Z]+')->parseInput(new ParserInput('aABC'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should reject an invalid pattern', function (): void {
        // Act
        $action = static fn (): RegExpParser => new RegExpParser('[');

        // Assert
        expect($action)->toThrow(ParserException::class, 'The regular expression is invalid.');
    });
});
