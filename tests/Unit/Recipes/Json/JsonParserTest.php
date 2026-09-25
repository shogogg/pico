<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Recipes\Json;

use Pico\Recipes\Json\JsonParser;

describe('JsonParser::document', function (): void {
    it('should parse a complete JSON text', function (string $input, array|string|int|float|bool|null $expected): void {
        // Act
        $actual = JsonParser::document()->parse($input);

        // Assert
        expect($actual)->toBeSuccessWith($expected, mb_strlen($input));
    })->with([
        'null' => ['null', null],
        'boolean literals' => ['[true, false]', [true, false]],
        'string' => ['"Pico"', 'Pico'],
        'all string escapes' => [
            '"' . '\\"' . '\\\\' . '\\/' . '\b' . '\f' . '\n' . '\r' . '\t' . '"',
            "\"\\/\x08\x0c\n\r\t",
        ],
        'unicode escapes and a surrogate pair' => [
            '"\\u3042\\uD83D\\uDE00"',
            'あ😀',
        ],
        'unicode escapes adjacent to the surrogate range' => [
            '"\\uD7FF\\uE000"',
            "\u{D7FF}\u{E000}",
        ],
        'decoded object keys and nested values' => [
            '{"\\u006b\\u0065\\u0079": ["\\uD83D\\uDE00", -1.5e2, false, null]}',
            ['key' => ['😀', -150.0, false, null]],
        ],
        'integers' => [
            '[0, -12, 123]',
            [0, -12, 123],
        ],
        'decimal numbers' => [
            '[3.14, -0.025]',
            [3.14, -0.025],
        ],
        'exponential numbers' => [
            '[1e3, -2.5E-2, 6E+1]',
            [1000.0, -0.025, 60.0],
        ],
        'large integer becomes a float' => [
            '9223372036854775808',
            9223372036854775808.0,
        ],
        'formatted object and nested values' => [
            "{\n\t\"name\": \"Pico\",\r\n\t\"items\": [1, {\"enabled\": true}, null]\n}",
            [
                'name' => 'Pico',
                'items' => [1, ['enabled' => true], null],
            ],
        ],
        'duplicate object member keeps the final value' => [
            '{"key": 1, "key": 2}',
            ['key' => 2],
        ],
    ]);

    it('should parse an empty array', function (): void {
        // Act
        $actual = JsonParser::document()->parse('[]');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse an empty object', function (): void {
        // Act
        $actual = JsonParser::document()->parse('{}');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse whitespace in an empty array', function (): void {
        // Act
        $actual = JsonParser::document()->parse('[ ]');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse whitespace in an empty object', function (): void {
        // Act
        $actual = JsonParser::document()->parse('{ }');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse whitespace before the root value', function (): void {
        // Act
        $actual = JsonParser::document()->parse(" \t\r\nnull");

        // Assert
        expect($actual)->toBeSuccessOf(null);
    });

    it('should parse whitespace after the root value', function (): void {
        // Act
        $actual = JsonParser::document()->parse("null \t\r\n");

        // Assert
        expect($actual)->toBeSuccessOf(null);
    });

    it('should parse whitespace at array value boundaries', function (string $input, array $expected): void {
        // Act
        $actual = JsonParser::document()->parse($input);

        // Assert
        expect($actual)->toBeSuccessEqualTo($expected);
    })->with([
        'after the opening bracket' => ['[ 1]', [1]],
        'before a comma' => ['[1 ,2]', [1, 2]],
        'after a comma' => ['[1, 2]', [1, 2]],
        'before the closing bracket' => ['[1 ]', [1]],
    ]);

    it('should parse whitespace at object member boundaries', function (string $input, array $expected): void {
        // Act
        $actual = JsonParser::document()->parse($input);

        // Assert
        expect($actual)->toBeSuccessEqualTo($expected);
    })->with([
        'after the opening brace' => ['{ "key":1}', ['key' => 1]],
        'before the name separator' => ['{"key" :1}', ['key' => 1]],
        'after the name separator' => ['{"key": 1}', ['key' => 1]],
        'before a value separator' => ['{"first":1 ,"second":2}', ['first' => 1, 'second' => 2]],
        'after a value separator' => ['{"first":1, "second":2}', ['first' => 1, 'second' => 2]],
        'before the closing brace' => ['{"key":1 }', ['key' => 1]],
    ]);

    it('should reject non-strict JSON', function (string $input): void {
        // Act
        $actual = JsonParser::document()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'empty input' => [''],
        'bare word' => ['truth'],
        'single-quoted string' => ["'Pico'"],
        'raw line break in string' => ["\"Pico\n\""],
        'raw control character in string' => ["\"Pico\x1f\""],
        'unpaired high surrogate' => ['"\\uD800"'],
        'unpaired low surrogate' => ['"\\uDC00"'],
        'leading zero' => ['01'],
        'negative leading zero' => ['-01'],
        'minus without an integer' => ['-'],
        'fraction without digits' => ['1.'],
        'exponent without significand' => ['e1'],
        'exponent without digits' => ['1e+'],
        'trailing comma in array' => ['[1,]'],
        'trailing comma in object' => ['{"key": 1,}'],
        'unquoted object key' => ['{key: 1}'],
        'comment' => ['/* comment */ null'],
        'trailing content' => ['null true'],
    ]);

    it('should reject a number outside the supported floating-point range', function (): void {
        // Act
        $parse = static fn () => JsonParser::document()->parse('1e9999');

        // Assert
        expect($parse)->toThrow(\Pico\Exceptions\ParserException::class);
    });
});

describe('JsonParser::simplified', function (): void {
    it('should parse an integer', function (string $input, int $expected): void {
        // Act
        $actual = JsonParser::simplified()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected);
    })->with([
        'zero' => ['0', 0],
        'negative integer' => ['-12', -12],
        'positive integer' => ['123', 123],
    ]);

    it('should parse string content', function (string $input, string $expected): void {
        // Act
        $actual = JsonParser::simplified()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected);
    })->with([
        'ordinary characters' => ['"Pico"', 'Pico'],
        'a literal backslash' => ['"Pico\\Parser"', 'Pico\\Parser'],
        'an escape-looking sequence' => ['"Pico\\n"', 'Pico\\n'],
        'a Unicode escape-looking sequence' => ['"\\u3042"', '\\u3042'],
    ]);

    it('should parse an escaped double quote in a string', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('"Pico \\"Parser\\""');

        // Assert
        expect($actual)->toBeSuccessOf('Pico "Parser"');
    });

    it('should parse an array', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('[1, "Pico", true, null]');

        // Assert
        expect($actual)->toBeSuccessEqualTo([1, 'Pico', true, null]);
    });

    it('should parse an empty array', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('[]');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse an object', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('{"name": "Pico", "count": 1}');

        // Assert
        expect($actual)->toBeSuccessEqualTo(['name' => 'Pico', 'count' => 1]);
    });

    it('should parse an empty object', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('{}');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse whitespace in an empty array', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('[ ]');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse whitespace in an empty object', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('{ }');

        // Assert
        expect($actual)->toBeSuccessOf([]);
    });

    it('should parse whitespace before the root value', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse(" \t\r\nnull");

        // Assert
        expect($actual)->toBeSuccessOf(null);
    });

    it('should parse whitespace after the root value', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse("null \t\r\n");

        // Assert
        expect($actual)->toBeSuccessOf(null);
    });

    it('should parse whitespace at array value boundaries', function (string $input, array $expected): void {
        // Act
        $actual = JsonParser::simplified()->parse($input);

        // Assert
        expect($actual)->toBeSuccessEqualTo($expected);
    })->with([
        'after the opening bracket' => ['[ 1]', [1]],
        'before a comma' => ['[1 ,2]', [1, 2]],
        'after a comma' => ['[1, 2]', [1, 2]],
        'before the closing bracket' => ['[1 ]', [1]],
    ]);

    it('should parse whitespace at object member boundaries', function (string $input, array $expected): void {
        // Act
        $actual = JsonParser::simplified()->parse($input);

        // Assert
        expect($actual)->toBeSuccessEqualTo($expected);
    })->with([
        'after the opening brace' => ['{ "key":1}', ['key' => 1]],
        'before the name separator' => ['{"key" :1}', ['key' => 1]],
        'after the name separator' => ['{"key": 1}', ['key' => 1]],
        'before a value separator' => ['{"first":1 ,"second":2}', ['first' => 1, 'second' => 2]],
        'after a value separator' => ['{"first":1, "second":2}', ['first' => 1, 'second' => 2]],
        'before the closing brace' => ['{"key":1 }', ['key' => 1]],
    ]);

    it('should consume the complete simplified JSON text', function (): void {
        // Arrange
        $input = '123';

        // Act
        $actual = JsonParser::simplified()->parse($input);

        // Assert
        expect($actual)->toBeSuccessWith(123, mb_strlen($input));
    });

    it('should reject an unsupported decimal number', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('3.14');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should reject an unsupported exponential number', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('1e3');

        // Assert
        expect($actual)->toBeFailure();
    });

    it('should reject trailing content', function (): void {
        // Act
        $actual = JsonParser::simplified()->parse('null true');

        // Assert
        expect($actual)->toBeFailure();
    });
});
