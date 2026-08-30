<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use LogicException;
use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;
use Pico\Parsers\RepeatParser;
use Pico\Parsers\SeqParser;

describe('RepeatParser', function (): void {
    it('should reject a negative minimum repetition count', function (): void {
        // Act
        $action = fn (): RepeatParser => new RepeatParser(new CharParser('A'), min: -1);

        // Assert
        expect($action)->toThrow(LogicException::class, 'The minimum repetition count must not be negative.');
    });

    it('should reject a maximum repetition count below the minimum', function (): void {
        // Act
        $action = fn (): RepeatParser => new RepeatParser(new CharParser('A'), min: 2, max: 1);

        // Assert
        expect($action)->toThrow(
            LogicException::class,
            'The maximum repetition count must be at least the minimum repetition count.',
        );
    });
});

describe('RepeatParser::parseInput', function (): void {
    it('should repeat the parser from the current input offset', function (): void {
        // Act
        $actual = new RepeatParser(new CharParser('A'))->parseInput(new ParserInput('xAAAB', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'A', 'A'], 3);
    });

    it('should stop after the maximum repetition count', function (): void {
        // Act
        $actual = new RepeatParser(new CharParser('A'), max: 2)->parseInput(new ParserInput('AAA'));

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'A'], 2);
    });

    it('should succeed with no results when the first match fails and the minimum is zero', function (): void {
        // Act
        $actual = new RepeatParser(new CharParser('A'))->parseInput(new ParserInput('BBB'));

        // Assert
        expect($actual)->toBeSuccessOf([], 0);
    });

    it('should fail when fewer than the minimum repetitions match', function (): void {
        // Act
        $actual = new RepeatParser(new CharParser('A'), min: 2)->parseInput(new ParserInput('AB'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should stop when the parser succeeds without consuming input', function (): void {
        // Act
        $actual = new RepeatParser(new SeqParser(), max: 2)->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf([], 0);
    });
});
