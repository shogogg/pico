<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Parsers\BetweenParser;
use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;
use Pico\Parsers\RegExpParser;

describe('BetweenParser::parseInput', function (): void {
    it('should return the content output after parsing the delimiters', function (): void {
        // Act
        $actual = new BetweenParser(
            new CharParser('('),
            new CharParser(')'),
            new RegExpParser('[a-z]+'),
        )->parseInput(new ParserInput('x(foo)', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf('foo', 5);
    });

    it('should succeed when the content parser succeeds without consuming input', function (): void {
        // Act
        $actual = new BetweenParser(
            new CharParser('('),
            new CharParser(')'),
            (new CharParser('x'))->optional(),
        )->parseInput(new ParserInput('()'));

        // Assert
        expect($actual)->toBeSuccessOf('', 2);
    });

    it('should fail when the opening parser fails', function (): void {
        // Act
        $actual = new BetweenParser(
            new CharParser('('),
            new CharParser(')'),
            new RegExpParser('[a-z]+'),
        )->parseInput(new ParserInput('[foo]'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail when the content parser fails', function (): void {
        // Act
        $actual = new BetweenParser(
            new CharParser('('),
            new CharParser(')'),
            new RegExpParser('[a-z]+'),
        )->parseInput(new ParserInput('(123)'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail when the closing parser fails', function (): void {
        // Act
        $actual = new BetweenParser(
            new CharParser('('),
            new CharParser(')'),
            new RegExpParser('[a-z]+'),
        )->parseInput(new ParserInput('(foo]'));

        // Assert
        expect($actual)->toBeFailure();
    });
});
