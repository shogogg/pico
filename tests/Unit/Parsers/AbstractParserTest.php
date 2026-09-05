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
use Pico\Internal\PicoInternal;
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

describe('AbstractParser::optional', function (): void {
    it('should return the original successful result', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            (new CharParser('A'))->optional(),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should return a successful empty-string result without consuming input when parsing fails', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            (new CharParser('A'))->optional(),
        )->parseInput(new ParserInput('BBB'));

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    });
});

describe('AbstractParser::skip', function (): void {
    it('should discard the output while preserving the consumed length', function (): void {
        // Act
        $actual = (new CharParser('A'))->skip()->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('', 1);
    });

    it('should return a failure when parsing fails', function (): void {
        // Act
        $actual = (new CharParser('A'))->skip()->parse('B');

        // Assert
        expect($actual)->toBeFailure();
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
