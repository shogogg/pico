<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\EofParser;
use Pico\Parsers\ParserInput;

describe('EofParser::parseInput', function (): void {
    it('should match at the end of input without consuming input', function (string $input, int $offset): void {
        // Act
        $actual = new EofParser()->parseInput(new ParserInput($input, $offset));

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    })->with([
        ['', 0],
        ['あいう', 3],
    ]);

    it('should fail before the end of input', function (): void {
        // Act
        $actual = new EofParser()->parseInput(new ParserInput('あいう', 2));

        // Assert
        expect($actual)->toBeFailure();
    });
});
