<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserInputException;
use Pico\Parsers\AbstractParser;
use Pico\Parsers\ParserInput;

use function Pico\Parsers\success;

describe('AbstractParser::parse', function (): void {
    it('should delegate parsing from the start of the input', function (): void {
        // Arrange
        $parser = new readonly class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success($input->current(), 1);
            }
        };

        // Act
        $actual = $parser->parse('😀b');

        // Assert
        expect($actual)->toBeSuccessOf('😀', 1);
    });

    it('should propagate a ParserInput exception', function (): void {
        // Arrange
        $parser = new readonly class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success($input->current(), 1);
            }
        };

        // Act
        $action = fn (): ParserResult => $parser->parse(chr(255));

        // Assert
        expect($action)->toThrow(ParserInputException::class, 'The input must be valid UTF-8.');
    });
});
