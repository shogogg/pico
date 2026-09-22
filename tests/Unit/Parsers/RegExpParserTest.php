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
        // Arrange
        $input = ParserInput::of('_ABC')->advanced(1);

        // Act
        $actual = new RegExpParser('[A-Z]+')->parseInput($input);

        // Assert
        expect($actual)->toBeSuccessOf('ABC');
    });

    it('should preserve Unicode character lengths', function (): void {
        // Act
        $actual = new RegExpParser('[あ-お]+')->parseInput(ParserInput::of('あいうえお'));

        // Assert
        expect($actual)->toBeSuccessWith('あいうえお', 5);
    });

    it('should fail when the pattern does not match at the current input offset', function (): void {
        // Act
        $actual = new RegExpParser('[A-Z]+')->parseInput(ParserInput::of('aABC'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should throw when the regular expression cannot be executed', function (): void {
        // Arrange
        $parser = new RegExpParser('a+');
        $previousBacktrackLimit = ini_get('pcre.backtrack_limit');

        if ($previousBacktrackLimit === false) {
            throw new \LogicException('Unable to read the PCRE backtrack limit.');
        }

        try {
            ini_set('pcre.backtrack_limit', '0');

            // Act
            $action = static fn () => $parser->parseInput(ParserInput::of('a'));

            // Assert
            expect($action)->toThrow(
                ParserException::class,
                'The regular expression failed to execute: Backtrack limit exhausted',
            );
        } finally {
            ini_set('pcre.backtrack_limit', $previousBacktrackLimit);
        }
    });

    it('should reject an invalid pattern', function (): void {
        // Act
        $action = static fn (): RegExpParser => new RegExpParser('[');

        // Assert
        expect($action)->toThrow(ParserException::class, 'The regular expression is invalid.');
    });
});
