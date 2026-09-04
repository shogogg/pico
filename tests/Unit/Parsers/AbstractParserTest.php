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
use Pico\Exceptions\ParserException;
use Pico\Parsers\AbstractParser;
use Pico\Parsers\CharParser;
use Pico\Parsers\ParserInput;

use function Pico\Parsers\success;

describe('AbstractParser::parse', function (): void {
    it('should delegate parsing from the start of the input', function (): void {
        // Arrange
        $parser = new class () extends AbstractParser {
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
        $parser = new class () extends AbstractParser {
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

describe('AbstractParser::map', function (): void {
    it('should transform a successful output', function (): void {
        // Act
        $actual = (new CharParser('a'))
            ->map(static fn (string $char): string => strtoupper($char))
            ->parse('abc');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should not transform a failed output', function (): void {
        // Arrange
        $wasCalled = false;
        $parser = (new CharParser('A'))->map(function (string $char) use (&$wasCalled): string {
            $wasCalled = true;

            return $char;
        });

        // Act
        $parser->parse('B');

        // Assert
        expect($wasCalled)->toBeFalse();
    });
});

describe('AbstractParser::join', function (): void {
    it('should join an array output with the separator', function (): void {
        // Act
        $actual = (new class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success(['first', 3], 0);
            }
        })->join(', ')->parse('');

        // Assert
        expect($actual)->toBeSuccessOf('first, 3', 0);
    });

    it('should stringify a scalar output', function (): void {
        // Act
        $actual = (new class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success(42, 0);
            }
        })->join()->parse('');

        // Assert
        expect($actual)->toBeSuccessOf('42', 0);
    });

    it('should stringify a Stringable output', function (): void {
        // Act
        $actual = (new class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success(new class () implements \Stringable {
                    public function __toString(): string
                    {
                        return 'stringable';
                    }
                }, 0);
            }
        })->join()->parse('');

        // Assert
        expect($actual)->toBeSuccessOf('stringable', 0);
    });

    it('should throw when an array output contains a non-stringable value', function (): void {
        // Act
        $action = static fn (): ParserResult => (new class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success(['first', new \stdClass()], 0);
            }
        })->join()->parse('');

        // Assert
        expect($action)->toThrow(ParserException::class, 'The value must be a scalar or implement Stringable.');
    });

    it('should throw when a non-array output cannot be stringified', function (): void {
        // Act
        $action = static fn (): ParserResult => (new class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success(null, 0);
            }
        })->join()->parse('');

        // Assert
        expect($action)->toThrow(ParserException::class, 'The value must be a scalar or implement Stringable.');
    });
});
