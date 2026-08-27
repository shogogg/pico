<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\ParserInput;
use Pico\Parsers\PredicateParser;

describe('PredicateParser::parseInput', function (): void {
    it('should return a success result when the current character satisfies the predicate', function (): void {
        // Arrange
        $parser = new PredicateParser(static fn (string $char): bool => $char === 'あ');

        // Act
        $actual = $parser->parseInput(new ParserInput('いあう', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf('あ', 1);
    });

    it('should return a failure result when the current character does not satisfy the predicate', function (): void {
        // Arrange
        $parser = new PredicateParser(static fn (string $char): bool => $char === 'あ');

        // Act
        $actual = $parser->parseInput(new ParserInput('いうえお', offset: 0));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should return a failure result when the input is at its end', function (): void {
        // Arrange
        $parser = new PredicateParser(static fn (string $char): bool => $char === 'あ');

        // Act
        $actual = $parser->parseInput(new ParserInput('あ', offset: 1));

        // Assert
        expect($actual)->toBeFailure();
    });
});
