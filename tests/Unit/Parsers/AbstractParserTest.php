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
use Pico\Parsers\ParserInput;
use Pico\Parsers\SeqParser;
use Pico\Pico;

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

describe('AbstractParser::complete', function (): void {
    it('should preserve the output and consumed length after consuming the complete input', function (): void {
        // Act
        $actual = Pico::char('A')->complete()->parse('A');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should fail when input remains after parsing', function (): void {
        // Act
        $actual = Pico::char('A')->complete()->parse('AB');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should consume multibyte input completely', function (): void {
        // Act
        $actual = Pico::char('あ')->complete()->parse('あ');

        // Assert
        expect($actual)->toBeSuccessOf('あ', 1);
    });
});

describe('AbstractParser::repeat', function (): void {
    it('should reject a negative minimum repetition count', function (): void {
        // Act
        $action = fn () => Pico::char('A')->repeat(min: -1);

        // Assert
        expect($action)->toThrow(ParserException::class, 'The minimum repetition count must not be negative.');
    });

    it('should reject a maximum repetition count below the minimum', function (): void {
        // Act
        $action = fn () => Pico::char('A')->repeat(min: 2, max: 1);

        // Assert
        expect($action)->toThrow(
            ParserException::class,
            'The maximum repetition count must be at least the minimum repetition count.',
        );
    });

    it('should repeat the parser from the current input offset', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->repeat(),
        )->parseInput(new ParserInput('xAAAB', offset: 1));

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'A', 'A'], 3);
    });

    it('should stop after the maximum repetition count', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->repeat(max: 2),
        )->parseInput(new ParserInput('AAA'));

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'A'], 2);
    });

    it('should succeed with no results when the first match fails and the minimum is zero', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->repeat(),
        )->parseInput(new ParserInput('BBB'));

        // Assert
        expect($actual)->toBeSuccessOf([], 0);
    });

    it('should fail when fewer than the minimum repetitions match', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->repeat(min: 2),
        )->parseInput(new ParserInput('AB'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should stop when the parser succeeds without consuming input', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            (new SeqParser())->repeat(max: 2),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf([], 0);
    });
});

describe('AbstractParser::map', function (): void {
    it('should transform a successful output', function (): void {
        // Act
        $actual = Pico::char('a')
            ->map(static fn (string $char): string => strtoupper($char))
            ->parse('abc');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should not transform a failed output', function (): void {
        // Arrange
        $wasCalled = false;
        $parser = Pico::char('A')->map(function (string $char) use (&$wasCalled): string {
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
            Pico::char('A')->optional(),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should return a successful empty-string result without consuming input when parsing fails', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->optional(),
        )->parseInput(new ParserInput('BBB'));

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    });
});

describe('AbstractParser::orElse', function (): void {
    it('should preserve the original successful result without evaluating the fallback', function (): void {
        // Arrange
        $wasCalled = false;

        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->orElse(function () use (&$wasCalled): string {
                $wasCalled = true;

                return 'fallback';
            }),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
        expect($wasCalled)->toBeFalse();
    });

    it('should return the fallback output without consuming input when parsing fails', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->orElse(static fn (): string => 'fallback'),
        )->parseInput(new ParserInput('BBB'));

        // Assert
        expect($actual)->toBeSuccessOf('fallback', 0);
    });

    it('should propagate a fallback exception', function (): void {
        // Act
        $action = static fn (): ParserResult => Pico::char('A')
            ->orElse(static fn (): never => throw new ParserException('Fallback failed.'))
            ->parse('B');

        // Assert
        expect($action)->toThrow(ParserException::class, 'Fallback failed.');
    });
});

describe('AbstractParser::skip', function (): void {
    it('should discard the output while preserving the consumed length', function (): void {
        // Act
        $actual = Pico::char('A')->skip()->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('', 1);
    });

    it('should return a failure when parsing fails', function (): void {
        // Act
        $actual = Pico::char('A')->skip()->parse('B');

        // Assert
        expect($actual)->toBeFailure();
    });
});

describe('AbstractParser::then', function (): void {
    it('should combine successful outputs and consumed lengths', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->then(Pico::char('B')),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'B'], 2);
    });

    it('should not evaluate the right parser when the left parser fails', function (): void {
        // Arrange
        $right = new class () extends AbstractParser {
            public bool $wasParsed = false;

            public function parseInput(ParserInput $input): ParserResult
            {
                $this->wasParsed = true;

                return success('B', 1);
            }
        };

        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->then($right),
        )->parseInput(new ParserInput('B'));

        // Assert
        expect($actual)->toBeFailure();
        expect($right->wasParsed)->toBeFalse();
    });

    it('should fail when the right parser fails', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->then(Pico::char('B')),
        )->parseInput(new ParserInput('AC'));

        // Assert
        expect($actual)->toBeFailure();
    });
});

describe('AbstractParser::where', function (): void {
    it('should preserve a successful output when the predicate succeeds', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->where(static fn (string $char): bool => $char === 'A'),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should preserve the consumed length when the predicate succeeds', function (): void {
        // Arrange
        $parser = new class () extends AbstractParser {
            public function parseInput(ParserInput $input): ParserResult
            {
                return success('accepted', 2);
            }
        };

        // Act
        $actual = PicoInternal::asContextualParser(
            $parser->where(static fn (string $value): bool => $value === 'accepted'),
        )->parseInput(new ParserInput('AB'));

        // Assert
        expect($actual)->toBeSuccessOf('accepted', 2);
    });

    it('should return a failure when the predicate fails', function (): void {
        // Act
        $actual = PicoInternal::asContextualParser(
            Pico::char('A')->where(static fn (string $char): bool => $char === 'B'),
        )->parseInput(new ParserInput('ABC'));

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should not call the predicate when parsing fails', function (): void {
        // Arrange
        $wasCalled = false;
        $parser = PicoInternal::asContextualParser(
            Pico::char('A')->where(function (string $char) use (&$wasCalled): bool {
                $wasCalled = true;

                return $char === 'A';
            }),
        );

        // Act
        $actual = $parser->parseInput(new ParserInput('B'));

        // Assert
        expect($actual)->toBeFailure();
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
