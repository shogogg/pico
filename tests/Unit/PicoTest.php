<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Contracts\Parser;
use Pico\Parsers\AnyCharParser;
use Pico\Parsers\AnyOfParser;
use Pico\Parsers\BetweenParser;
use Pico\Parsers\CharParser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\PredicateParser;
use Pico\Parsers\RangeParser;
use Pico\Parsers\RegExpParser;
use Pico\Parsers\SepByParser;
use Pico\Parsers\SeqParser;
use Pico\Parsers\SkipLeftParser;
use Pico\Parsers\SkipRightParser;
use Pico\Parsers\StringParser;
use Pico\Pico;

describe('Pico::alpha()', function (): void {
    it('should return a PredicateParser instance', function (): void {
        // Act
        $actual = Pico::alpha();

        // Assert
        expect($actual)->toBeInstanceOf(PredicateParser::class);
    });

    it('should parse an ASCII alphabetic character', function (string $input, string $expected): void {
        // Act
        $actual = Pico::alpha()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        ['ABC', 'A'],
        ['xyz', 'x'],
    ]);

    it('should fail for a character outside the ASCII alphabet', function (string $input): void {
        // Act
        $actual = Pico::alpha()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        '123',
        'あいう',
        'ＡBC',
    ]);
});

describe('Pico::alphaNum()', function (): void {
    it('should return a PredicateParser instance', function (): void {
        // Act
        $actual = Pico::alphaNum();

        // Assert
        expect($actual)->toBeInstanceOf(PredicateParser::class);
    });

    it('should parse an ASCII alphanumeric character', function (string $input, string $expected): void {
        // Act
        $actual = Pico::alphaNum()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        ['ABC', 'A'],
        ['123', '1'],
    ]);

    it('should fail for a character outside the ASCII alphanumeric characters', function (string $input): void {
        // Act
        $actual = Pico::alphaNum()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        '!@#',
        'あいう',
        'ＡBC',
        '１２3',
    ]);
});

describe('Pico::anyChar()', function (): void {
    it('should return an AnyCharParser instance', function (): void {
        // Act
        $actual = Pico::anyChar();

        // Assert
        expect($actual)->toBeInstanceOf(AnyCharParser::class);
    });

    it('should parse any character', function (): void {
        // Act
        $actual = Pico::anyChar()->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });
});

describe('Pico::anyOf()', function (): void {
    it('should return an AnyOfParser instance', function (): void {
        // Act
        $actual = Pico::anyOf(Pico::char('A'), Pico::digit());

        // Assert
        expect($actual)->toBeInstanceOf(AnyOfParser::class);
    });

    it('should parse the first matching parser', function (): void {
        // Act
        $actual = Pico::anyOf(Pico::char('A'), Pico::digit())->parse('A1');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });
});

describe('Pico::ascii()', function (): void {
    it('should return a PredicateParser instance', function (): void {
        // Act
        $actual = Pico::ascii();

        // Assert
        expect($actual)->toBeInstanceOf(PredicateParser::class);
    });

    it('should parse an ASCII character', function (): void {
        // Act
        $actual = Pico::ascii()->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should fail for a non-ASCII character', function (string $input): void {
        // Act
        $actual = Pico::ascii()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'あいう',
        'ＡBC',
        '１２3',
    ]);
});

describe('Pico::between()', function (): void {
    it('should return a BetweenParser instance', function (): void {
        // Act
        $actual = Pico::between(
            Pico::char('('),
            Pico::char(')'),
            Pico::regexp('[a-z]+'),
        );

        // Assert
        expect($actual)->toBeInstanceOf(BetweenParser::class);
    });

    it('should return only the content output', function (): void {
        // Act
        $actual = Pico::between(
            Pico::char('('),
            Pico::char(')'),
            Pico::regexp('[a-z]+'),
        )->parse('(foo)');

        // Assert
        expect($actual)->toBeSuccessOf('foo', 5);
    });
});

describe('Pico::char()', function (): void {
    it('should return a CharParser instance', function (): void {
        // Act
        $actual = Pico::char('A');

        // Assert
        expect($actual)->toBeInstanceOf(CharParser::class);
    });

    it('should parse the given character', function (): void {
        // Act
        $actual = Pico::char('A')->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });
});

describe('Pico::digit()', function (): void {
    it('should return a PredicateParser instance', function (): void {
        // Act
        $actual = Pico::digit();

        // Assert
        expect($actual)->toBeInstanceOf(PredicateParser::class);
    });

    it('should parse an ASCII decimal digit', function (string $input, string $expected): void {
        // Act
        $actual = Pico::digit()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        ['012', '0'],
        ['987', '9'],
    ]);

    it('should fail for a character outside the ASCII decimal digits', function (string $input): void {
        // Act
        $actual = Pico::digit()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'abc',
        '１２3',
    ]);
});

describe('Pico::eof()', function (): void {
    it('should match the end of input without consuming input', function (): void {
        // Act
        $actual = Pico::eof()->parse('');

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    });

    it('should fail before the end of input', function (): void {
        // Act
        $actual = Pico::eof()->parse('ABC');

        // Assert
        expect($actual)->toBeFailure();
    });
});

describe('Pico::join()', function (): void {
    it('should join sequential parser outputs while preserving the total consumed length', function (): void {
        // Act
        $actual = Pico::join(
            Pico::char('A'),
            Pico::string('BC'),
            Pico::char('D'),
        )->parse('ABCD!');

        // Assert
        expect($actual)->toBeSuccessOf('ABCD', 4);
    });

    it('should fail when a parser in the sequence fails', function (): void {
        // Act
        $actual = Pico::join(Pico::char('A'), Pico::char('B'))->parse('AX');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should succeed with an empty string when no parsers are given', function (): void {
        // Act
        $actual = Pico::join()->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    });
});

describe('Pico::lazy()', function (): void {
    it('should return a LazyParser instance', function (): void {
        // Act
        $actual = Pico::lazy(static fn (): CharParser => new CharParser('A'));

        // Assert
        expect($actual)->toBeInstanceOf(LazyParser::class);
    });

    it('should parse the parser returned by the factory', function (): void {
        // Act
        $actual = Pico::lazy(static fn (): CharParser => new CharParser('A'))->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });
});

describe('Pico::oneOf()', function (): void {
    it('should parse a character in the given character set', function (): void {
        // Act
        $actual = Pico::oneOf('ABC')->parse('BCD');

        // Assert
        expect($actual)->toBeSuccessOf('B', 1);
    });
});

describe('Pico::pair()', function (): void {
    it('should combine the outputs of two sequential parsers without a separator', function (): void {
        // Act
        $actual = Pico::pair(Pico::string('AB'), Pico::char('C'))->parse('ABCD');

        // Assert
        expect($actual)->toBeSuccessOf(['AB', 'C'], 3);
    });

    it('should discard a named separator while preserving the outputs and consumed length', function (): void {
        // Act
        $actual = Pico::pair(
            Pico::string('key'),
            Pico::string('value'),
            sep: Pico::char(':'),
        )->parse('key:value');

        // Assert
        expect($actual)->toBeSuccessOf(['key', 'value'], 9);
    });

    it('should not evaluate the right parser when the separator fails', function (): void {
        // Arrange
        $wasResolved = false;
        $right = Pico::lazy(function () use (&$wasResolved): Parser {
            $wasResolved = true;

            return Pico::char('B');
        });

        // Act
        $actual = Pico::pair(Pico::char('A'), $right, sep: Pico::char(':'))->parse('A;B');

        // Assert
        expect($actual)->toBeFailure();
        expect($wasResolved)->toBeFalse();
    });

    it('should fail when either parser fails', function (): void {
        // Act
        $actual = Pico::pair(Pico::char('A'), Pico::char('B'))->parse('AC');

        // Assert
        expect($actual)->toBeFailure();
    });
});

describe('Pico::predicate()', function (): void {
    it('should return a PredicateParser instance', function (): void {
        // Act
        $actual = Pico::predicate(static fn (string $char): bool => $char === 'x');

        // Assert
        expect($actual)->toBeInstanceOf(PredicateParser::class);
    });

    it('should parse a character satisfying the predicate', function (): void {
        // Act
        $actual = Pico::predicate(static fn (string $char): bool => $char === 'x')->parse('xyz');

        // Assert
        expect($actual)->toBeSuccessOf('x', 1);
    });
});

describe('Pico::range()', function (): void {
    it('should return a RangeParser instance', function (): void {
        // Act
        $actual = Pico::range('あ', 'お');

        // Assert
        expect($actual)->toBeInstanceOf(RangeParser::class);
    });

    it('should parse a character within the range', function (string $input, string $expected): void {
        // Act
        $actual = Pico::range('あ', 'お')->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        'range start' => ['あいう', 'あ'],
        'range middle' => ['えお', 'え'],
        'range end' => ['おかき', 'お'],
    ]);

    it('should fail for a character outside the range', function (string $input): void {
        // Act
        $actual = Pico::range('あ', 'お')->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'ASCII character' => 'apple',
        'before range' => 'ぁいう',
        'after range' => 'かきく',
    ]);

    it('should reject bounds that are not exactly one UTF-8 character', function (string $from, string $to): void {
        // Act
        $action = static fn (): \Pico\Contracts\Parser => Pico::range($from, $to);

        // Assert
        expect($action)->toThrow(\Pico\Exceptions\ParserException::class, 'Range bounds must be exactly one UTF-8 character.');
    })->with([
        'empty start' => ['', 'z'],
        'multiple-character start' => ['ab', 'z'],
        'multiple-character UTF-8 end' => ['a', 'あい'],
    ]);

    it('should reject a range whose start exceeds its end', function (): void {
        // Act
        $action = static fn (): \Pico\Contracts\Parser => Pico::range('z', 'a');

        // Assert
        expect($action)->toThrow(\Pico\Exceptions\ParserException::class, 'The range start must not exceed the range end.');
    });
});

describe('Pico::regexp()', function (): void {
    it('should return a RegExpParser instance', function (): void {
        // Act
        $actual = Pico::regexp('[A-Z]+');

        // Assert
        expect($actual)->toBeInstanceOf(RegExpParser::class);
    });

    it('should parse the given regular expression', function (): void {
        // Act
        $actual = Pico::regexp('[A-Z]+')->parse('ABC123');

        // Assert
        expect($actual)->toBeSuccessOf('ABC', 3);
    });
});

describe('Pico::sepBy()', function (): void {
    it('should return a SepByParser instance', function (): void {
        // Act
        $actual = Pico::sepBy(Pico::regexp('\\d+'), Pico::char(','));

        // Assert
        expect($actual)->toBeInstanceOf(SepByParser::class);
    });

    it('should parse content separated by the given parser', function (): void {
        // Act
        $actual = Pico::sepBy(Pico::regexp('\\d+'), Pico::char(','))->parse('1,22,333x');

        // Assert
        expect($actual)->toBeSuccessOf(['1', '22', '333'], 8);
    });
});

describe('Pico::seq()', function (): void {
    it('should return a SeqParser instance', function (): void {
        // Act
        $actual = Pico::seq(Pico::char('A'), Pico::digit());

        // Assert
        expect($actual)->toBeInstanceOf(SeqParser::class);
    });

    it('should parse each parser in sequence', function (): void {
        // Act
        $actual = Pico::seq(Pico::char('A'), Pico::digit())->parse('A123');

        // Assert
        expect($actual)->toBeSuccessOf(['A', '1'], 2);
    });
});

describe('Pico::skip()', function (): void {
    it('should discard the outputs while preserving the total consumed length', function (): void {
        // Act
        $actual = Pico::skip(Pico::char('A'), Pico::string('BC'))->parse('ABCD');

        // Assert
        expect($actual)->toBeSuccessOf('', 3);
    });

    it('should fail when a parser in the sequence fails', function (): void {
        // Act
        $actual = Pico::skip(Pico::char('A'), Pico::char('B'))->parse('AX');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should succeed without consuming input when no parsers are given', function (): void {
        // Act
        $actual = Pico::skip()->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('', 0);
    });
});

describe('Pico::skipLeft()', function (): void {
    it('should return a SkipLeftParser instance', function (): void {
        // Act
        $actual = Pico::skipLeft(Pico::char(':'), Pico::char('A'));

        // Assert
        expect($actual)->toBeInstanceOf(SkipLeftParser::class);
    });
});

describe('Pico::skipRight()', function (): void {
    it('should return a SkipRightParser instance', function (): void {
        // Act
        $actual = Pico::skipRight(Pico::char('A'), Pico::char(';'));

        // Assert
        expect($actual)->toBeInstanceOf(SkipRightParser::class);
    });
});

describe('Pico::string()', function (): void {
    it('should return a StringParser instance', function (): void {
        // Act
        $actual = Pico::string('AB');

        // Assert
        expect($actual)->toBeInstanceOf(StringParser::class);
    });

    it('should parse the given string', function (): void {
        // Act
        $actual = Pico::string('AB')->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('AB', 2);
    });
});

describe('Pico::whitespace()', function (): void {
    it('should return a PredicateParser instance', function (): void {
        // Act
        $actual = Pico::whitespace();

        // Assert
        expect($actual)->toBeInstanceOf(PredicateParser::class);
    });

    it('should parse an ASCII whitespace character', function (string $input, string $expected): void {
        // Act
        $actual = Pico::whitespace()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        [' ABC', ' '],
        ["\tABC", "\t"],
        ["\nABC", "\n"],
    ]);

    it('should fail for a character outside the ASCII whitespace characters', function (string $input): void {
        // Act
        $actual = Pico::whitespace()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'ABC',
        '　ABC',
    ]);
});

describe('Pico::whitespaces()', function (): void {
    it('should return a RegExpParser instance', function (): void {
        // Act
        $actual = Pico::whitespaces();

        // Assert
        expect($actual)->toBeInstanceOf(RegExpParser::class);
    });

    it('should parse consecutive ASCII whitespace characters', function (): void {
        // Act
        $actual = Pico::whitespaces()->parse(" \t\nABC");

        // Assert
        expect($actual)->toBeSuccessOf(" \t\n", 3);
    });

    it('should fail when the input does not start with an ASCII whitespace character', function (string $input): void {
        // Act
        $actual = Pico::whitespaces()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'ABC',
        '　ABC',
    ]);
});
