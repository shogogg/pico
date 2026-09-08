<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;
use Pico\Failure;
use Pico\Internal\PicoInternal;
use Pico\Parsers\ParserInput;
use Pico\Pico;

describe('PicoInternal::ensureContextualParser()', function (): void {
    it('should throw an exception containing the class name for a non-contextual parser', function (): void {
        // Arrange
        $parser = new class () implements Parser {
            public function parse(string $input): ParserResult
            {
                return Failure::getInstance();
            }

            public function complete(): Parser
            {
                return $this;
            }

            public function concat(): Parser
            {
                return $this;
            }

            public function repeat(int $min = 0, int $max = PHP_INT_MAX): Parser
            {
                return $this;
            }

            public function except(Parser $except): Parser
            {
                return $this;
            }

            public function map(\Closure $fn): Parser
            {
                return $this;
            }

            public function optional(): Parser
            {
                return $this;
            }

            public function orElse(\Closure $fallback): Parser
            {
                return $this;
            }

            public function skip(): Parser
            {
                return $this;
            }

            public function then(Parser $parser): Parser
            {
                return $this;
            }

            public function where(\Closure $predicate): Parser
            {
                return $this;
            }

            public function join(string $separator = ''): Parser
            {
                return $this;
            }
        };
        /**
         * @template T
         * @param Parser<T> $candidate
         * @return ParserResult<T>
         */
        $parseInput = static function (Parser $candidate): ParserResult {
            PicoInternal::ensureContextualParser($candidate);

            return $candidate->parseInput(new ParserInput('a'));
        };

        // Act
        $action = static function () use ($parseInput, $parser): ParserResult {
            return $parseInput($parser);
        };
        $message = $parser::class . ' is not a ContextualParser';

        // Assert
        expect($action)->toThrow(ParserException::class, $message);
    });

    it('should allow a contextual parser', function (): void {
        // Arrange
        /**
         * @template T
         * @param Parser<T> $candidate
         * @return ParserResult<T>
         */
        $parseInput = static function (Parser $candidate): ParserResult {
            PicoInternal::ensureContextualParser($candidate);

            return $candidate->parseInput(new ParserInput('a'));
        };

        // Act
        $actual = $parseInput(Pico::char('a'));

        // Assert
        expect($actual)->toBeSuccessOf('a', 1);
    });
});

describe('PicoInternal::asContextualParser()', function (): void {
    it('should throw an exception containing the class name for a non-contextual parser', function (): void {
        // Arrange
        $parser = new class () implements Parser {
            public function parse(string $input): ParserResult
            {
                return Failure::getInstance();
            }

            public function complete(): Parser
            {
                return $this;
            }

            public function concat(): Parser
            {
                return $this;
            }

            public function repeat(int $min = 0, int $max = PHP_INT_MAX): Parser
            {
                return $this;
            }

            public function except(Parser $except): Parser
            {
                return $this;
            }

            public function map(\Closure $fn): Parser
            {
                return $this;
            }

            public function optional(): Parser
            {
                return $this;
            }

            public function orElse(\Closure $fallback): Parser
            {
                return $this;
            }

            public function skip(): Parser
            {
                return $this;
            }

            public function then(Parser $parser): Parser
            {
                return $this;
            }

            public function where(\Closure $predicate): Parser
            {
                return $this;
            }

            public function join(string $separator = ''): Parser
            {
                return $this;
            }
        };
        /**
         * @template T
         * @param Parser<T> $candidate
         * @return \Pico\Parsers\ContextualParser<T>
         */
        $asContextualParser = static function (Parser $candidate): \Pico\Parsers\ContextualParser {
            return PicoInternal::asContextualParser($candidate);
        };

        // Act
        $action = static function () use ($asContextualParser, $parser): \Pico\Parsers\ContextualParser {
            return $asContextualParser($parser);
        };
        $message = $parser::class . ' is not a ContextualParser';

        // Assert
        expect($action)->toThrow(ParserException::class, $message);
    });

    it('should return the same contextual parser instance', function (): void {
        // Arrange
        $parser = Pico::char('a');

        // Act
        $actual = PicoInternal::asContextualParser($parser);

        // Assert
        expect($actual)->toBe($parser);
    });
});
