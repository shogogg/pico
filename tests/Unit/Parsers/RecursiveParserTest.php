<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Parsers;

use Pico\Contracts\Parser;
use Pico\Exceptions\ParserException;
use Pico\Parsers\ParserInput;
use Pico\Parsers\RecursiveParser;
use Pico\Pico;

describe('RecursiveParser::parseInput', function (): void {
    it('should parse a recursively defined parser', function (): void {
        // Arrange
        $parser = new RecursiveParser(static function (Parser $self): Parser {
            return Pico::anyOf(
                Pico::char('x'),
                Pico::between(Pico::char('('), $self, Pico::char(')')),
            );
        });

        // Act
        $actual = $parser->parseInput(ParserInput::of('((x))'));

        // Assert
        expect($actual)->toBeSuccessOf('x', 5);
    });

    it('should evaluate the definition only once', function (): void {
        // Arrange
        $calls = 0;
        $parser = new RecursiveParser(function (Parser $self) use (&$calls): Parser {
            ++$calls;

            return Pico::anyOf(
                Pico::char('x'),
                Pico::between(Pico::char('('), $self, Pico::char(')')),
            );
        });

        // Act
        $parser->parseInput(ParserInput::of('(x)'));
        $parser->parseInput(ParserInput::of('x'));

        // Assert
        expect($calls)->toBe(1);
    });

    it('should reject parsing itself while initializing its definition', function (): void {
        $parser = new RecursiveParser(static function (Parser $self): Parser {
            $self->parse('x');

            return Pico::char('x');
        });

        $parser->parseInput(ParserInput::of('x'));
    })->throws(ParserException::class, 'The recursive parser definition can only be initialized once.');

    it('should propagate an exception thrown while evaluating the definition', function (): void {
        // Arrange
        $parser = new RecursiveParser(static function (Parser $self): Parser {
            throw new \LogicException('Definition failed.');
        });

        // Act
        $action = static fn () => $parser->parseInput(ParserInput::of('x'));

        // Assert
        expect($action)->toThrow(\LogicException::class, 'Definition failed.');
    });

    it('should not reevaluate a definition after its evaluation fails', function (): void {
        // Arrange
        $calls = 0;
        $parser = new RecursiveParser(function (Parser $self) use (&$calls): Parser {
            ++$calls;

            throw new \LogicException('Definition failed.');
        });

        // Arrange the failed initialization state.
        try {
            $parser->parseInput(ParserInput::of('x'));
        } catch (\LogicException) {
        }

        // Act
        $action = static fn () => $parser->parseInput(ParserInput::of('x'));

        // Assert
        expect($action)
            ->toThrow(ParserException::class, 'The recursive parser definition can only be initialized once.');
        expect($calls)->toBe(1);
    });
});
