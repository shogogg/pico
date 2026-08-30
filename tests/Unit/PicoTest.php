<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

use Pico\Parsers\AnyCharParser;
use Pico\Parsers\AnyOfParser;
use Pico\Parsers\CharParser;
use Pico\Parsers\LazyParser;
use Pico\Parsers\OptionalParser;
use Pico\Parsers\PredicateParser;
use Pico\Parsers\RegExpParser;
use Pico\Parsers\RepeatParser;
use Pico\Parsers\SeqParser;
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

describe('Pico::optional()', function (): void {
    it('should return an OptionalParser instance', function (): void {
        // Act
        $actual = Pico::optional(Pico::char('A'));

        // Assert
        expect($actual)->toBeInstanceOf(OptionalParser::class);
    });

    it('should parse the given parser optionally', function (): void {
        // Act
        $actual = Pico::optional(Pico::char('A'))->parse('ABC');

        // Assert
        expect($actual)->toBeSuccessOf('A', 1);
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

describe('Pico::repeat()', function (): void {
    it('should return a RepeatParser instance', function (): void {
        // Act
        $actual = Pico::repeat(Pico::char('A'));

        // Assert
        expect($actual)->toBeInstanceOf(RepeatParser::class);
    });

    it('should parse the given parser repeatedly', function (): void {
        // Act
        $actual = Pico::repeat(Pico::char('A'), min: 1, max: 2)->parse('AAA');

        // Assert
        expect($actual)->toBeSuccessOf(['A', 'A'], 2);
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
