<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Contracts\Parser;
use Pico\Exceptions\ParserException;
use Pico\Parsers\AnyCharParser;
use Pico\Parsers\BetweenParser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\RegExpParser;
use Pico\Parsers\SepByParser;
use Pico\Parsers\SeqParser;
use Pico\Parsers\SkipLeftParser;
use Pico\Parsers\SkipRightParser;
use Pico\Parsers\StringParser;
use Pico\Pico;

describe('Pico::alpha()', function (): void {
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
    it('should return the first successful result and its consumed length', function (): void {
        // Act
        $actual = Pico::anyOf(Pico::string('AB'), Pico::char('A'))->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('AB', 2);
    });

    it('should try the next parser after a failure', function (): void {
        // Act
        $actual = Pico::anyOf(Pico::char('Z'), Pico::char('A'))->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should not evaluate parsers after a success', function (): void {
        // Arrange
        $wasResolved = false;
        $second = Pico::lazy(function () use (&$wasResolved): Parser {
            $wasResolved = true;

            return Pico::char('B');
        });

        // Act
        $actual = Pico::anyOf(Pico::char('A'), $second)->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
        expect($wasResolved)->toBeFalse();
    });

    it('should fail when every parser fails', function (): void {
        // Act
        $actual = Pico::anyOf(Pico::char('X'), Pico::char('Y'))->parse('ABC');

        // Assert
        expect($actual)->toBeFailure();
    });

});

describe('Pico::ascii()', function (): void {
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
    it('should parse the given character', function (): void {
        // Act
        $actual = Pico::char('A')->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });

    it('should parse a multibyte character', function (): void {
        // Act
        $actual = Pico::char('😀')->parse('😀ABC');

        // Assert
        expect($actual)->toBeSuccessOf('😀', 1);
    });

    it('should fail when the current character does not match', function (): void {
        // Act
        $actual = Pico::char('A')->parse('BCD');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should reject an invalid UTF-8 character', function (string $char): void {
        Pico::char($char);
    })->with([
        'invalid byte' => "\x80",
        'incomplete multibyte character' => "\xE3\x81",
    ])->throws(ParserException::class, 'The character must be valid UTF-8.');

    it('should reject a character that is not exactly one character long', function (string $char): void {
        Pico::char($char);
    })->with([
        '',
        'ab',
        'あい',
    ])->throws(ParserException::class, 'The character must be exactly one character long.');
});

describe('Pico::digit()', function (): void {
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
        $actual = Pico::lazy(static fn (): Parser => Pico::char('A'));

        // Assert
        expect($actual)->toBeInstanceOf(LazyParser::class);
    });

    it('should parse the parser returned by the factory', function (): void {
        // Act
        $actual = Pico::lazy(static fn (): Parser => Pico::char('A'))->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
    });
});

describe('Pico::oneOf()', function (): void {
    it('should reject an invalid UTF-8 character set', function (string $characters): void {
        Pico::oneOf($characters);
    })->with([
        'invalid byte' => "\x80",
        'incomplete multibyte character' => "\xE3\x81",
    ])->throws(ParserException::class, 'The character set must be valid UTF-8.');

    it('should reject an empty character set', function (): void {
        Pico::oneOf('');
    })->throws(ParserException::class, 'The character set must not be empty.');

    it('should parse a character in the given character set', function (string $characters, string $input, string $expected): void {
        // Act
        $actual = Pico::oneOf($characters)->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, 1);
    })->with([
        'ASCII character' => ['ABC', 'BCD', 'B'],
        'multibyte character' => ['あいう', 'いえお', 'い'],
    ]);

    it('should fail when the current character is not in the character set', function (): void {
        // Act
        $actual = Pico::oneOf('ABC')->parse('XYZ');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail at the end of input', function (): void {
        // Act
        $actual = Pico::oneOf('ABC')->parse('');

        // Assert
        expect($actual)->toBeFailure();
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
    it('should parse a UTF-8 character satisfying the predicate', function (): void {
        // Arrange
        $received = '';
        $parser = Pico::predicate(function (string $char) use (&$received): bool {
            $received = $char;

            return $char === 'あ';
        });

        // Act
        $actual = $parser->parse('あいう');

        // Assert
        expect($actual)->toBeSuccessOf('あ', 1);
        expect($received)->toBe('あ');
    });

    it('should fail when the current character does not satisfy the predicate', function (): void {
        // Act
        $actual = Pico::predicate(static fn (string $char): bool => $char === 'あ')->parse('いうえお');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should fail at the end of input without evaluating the predicate', function (): void {
        // Arrange
        $wasEvaluated = false;
        $parser = Pico::predicate(function (string $char) use (&$wasEvaluated): bool {
            $wasEvaluated = true;

            return $char === 'あ';
        });

        // Act
        $actual = $parser->parse('');

        // Assert
        expect($actual)->toBeFailure();
        expect($wasEvaluated)->toBeFalse();
    });

    it('should propagate an exception raised by the predicate', function (): void {
        Pico::predicate(static function (string $char): bool {
            throw new \LogicException("Unable to evaluate '{$char}'.");
        })->parse('あいう');
    })->throws(\LogicException::class, "Unable to evaluate 'あ'.");
});

describe('Pico::range()', function (): void {
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

    it('should parse an emoji within the range', function (): void {
        // Act
        $actual = Pico::range('😀', '😂')->parse('😁!');

        // Assert
        expect($actual)->toBeSuccessOf('😁', 1);
    });

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

    it('should fail at the end of input', function (): void {
        // Act
        $actual = Pico::range('a', 'z')->parse('');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should reject invalid UTF-8 bounds', function (string $from, string $to): void {
        // Act
        $action = static fn (): Parser => Pico::range($from, $to);

        // Assert
        expect($action)->toThrow(ParserException::class, 'Range bounds must be valid UTF-8.');
    })->with([
        'invalid byte start' => ["\x80", 'z'],
        'incomplete multibyte start' => ["\xE3\x81", 'z'],
        'invalid byte end' => ['a', "\x80"],
        'incomplete multibyte end' => ['a', "\xE3\x81"],
    ]);

    it('should reject bounds that are not exactly one UTF-8 character', function (string $from, string $to): void {
        // Act
        $action = static fn (): Parser => Pico::range($from, $to);

        // Assert
        expect($action)->toThrow(ParserException::class, 'Range bounds must be exactly one UTF-8 character.');
    })->with([
        'empty start' => ['', 'z'],
        'multiple-character start' => ['ab', 'z'],
        'multiple-character UTF-8 end' => ['a', 'あい'],
    ]);

    it('should reject a range whose start exceeds its end', function (): void {
        // Act
        $action = static fn (): Parser => Pico::range('z', 'a');

        // Assert
        expect($action)->toThrow(ParserException::class, 'The range start must not exceed the range end.');
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
