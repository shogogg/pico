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
use Pico\Parsers\OptionalParser;
use Pico\Parsers\ParserInput;

describe('OptionalParser::parseInput', function (): void {
    it('should return the successful result from the inner parser', function (): void {
        // Act
        $actual = new OptionalParser(new CharParser('A'))->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should return a successful null result without consuming input when the inner parser fails', function (): void {
        // Act
        $actual = new OptionalParser(new CharParser('A'))->parseInput(new ParserInput('BBB'));

        // Assert
        expect($actual)->toBeSuccessOf(null, 0);
    });
});
