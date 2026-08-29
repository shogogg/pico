<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Parsers\CharParser;
use Pico\Parsers\MapParser;
use Pico\Parsers\ParserInput;

describe('MapParser::parseInput', function (): void {
    it('should transform a successful result', function (): void {
        // Act
        $actual = new MapParser(
            new CharParser('a'),
            static fn (string $char): string => strtoupper($char),
        )->parseInput(new ParserInput('xabc', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should not transform a failed result', function (): void {
        $wasCalled = false;
        $parser = new MapParser(
            new CharParser('A'),
            function (string $char) use (&$wasCalled): string {
                $wasCalled = true;

                return $char;
            },
        );

        // Act
        $parser->parseInput(new ParserInput('XYZ'));

        // Assert
        expect($wasCalled)->toBeFalse();
    });
});
