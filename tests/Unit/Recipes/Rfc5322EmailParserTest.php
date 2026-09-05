<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Recipes;

use Pico\Recipes\Rfc5322EmailParser;

describe('Rfc5322EmailParser::address', function (): void {
    it('should parse an addr-spec and consume the complete input', function (string $input, array $expected): void {
        // Act
        $actual = Rfc5322EmailParser::address()->parse($input);

        // Assert
        expect($actual)->toBeSuccessOf($expected, mb_strlen($input));
    })->with([
        'simple atom' => [
            'simple@example.com',
            ['local_part' => 'simple', 'domain' => 'example.com'],
        ],
        'dot-atoms' => [
            'first.last@sub.example.com',
            ['local_part' => 'first.last', 'domain' => 'sub.example.com'],
        ],
        'digits' => [
            'a1.b2@123.456',
            ['local_part' => 'a1.b2', 'domain' => '123.456'],
        ],
        'atext special characters' => [
            '!#$%&\'*+-/=?^_`{|}~@example.com',
            ['local_part' => '!#$%&\'*+-/=?^_`{|}~', 'domain' => 'example.com'],
        ],
        'quoted local part with FWS' => [
            '"quoted local"@example.com',
            ['local_part' => 'quoted local', 'domain' => 'example.com'],
        ],
        'quoted local part with folded FWS' => [
            "\"quoted\r\n local\"@example.com",
            ['local_part' => 'quoted local', 'domain' => 'example.com'],
        ],
        'dot-atom with nested comments' => [
            'first(outer(inner))@example.com',
            ['local_part' => 'first', 'domain' => 'example.com'],
        ],
        'dot-atom with escaped comment parenthesis' => [
            'first(\\))@example.com',
            ['local_part' => 'first', 'domain' => 'example.com'],
        ],
        'quoted local part with CFWS' => [
            '(comment)"quoted"(comment)@example.com',
            ['local_part' => 'quoted', 'domain' => 'example.com'],
        ],
        'escaped character in quoted local part' => [
            '"foo\\"bar"@example.com',
            ['local_part' => 'foo"bar', 'domain' => 'example.com'],
        ],
        'empty quoted local part' => [
            '""@example.com',
            ['local_part' => '', 'domain' => 'example.com'],
        ],
        'domain literal' => [
            'user@[127.0.0.1]',
            ['local_part' => 'user', 'domain' => '[127.0.0.1]'],
        ],
        'IPv6 domain literal' => [
            'user@[IPv6:2001:db8::1]',
            ['local_part' => 'user', 'domain' => '[IPv6:2001:db8::1]'],
        ],
    ]);

    it('should fail when an addr-spec has an invalid local part or domain', function (string $input): void {
        // Act
        $actual = Rfc5322EmailParser::address()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'missing local part' => '@example.com',
        'leading dot in local part' => '.first@example.com',
        'unterminated quoted local part' => '"foo@example.com',
        'FWS without whitespace after line break' => "\"foo\r\nbar\"@example.com",
        'consecutive dots in local part' => 'first..last@example.com',
        'missing domain' => 'first.last@',
        'unquoted whitespace' => 'first last@example.com',
        'non-ASCII local part' => 'あいう@example.com',
        'DEL in quoted pair' => "\"foo\\" . chr(0x7f) . "bar\"@example.com",
        'backslash in domain literal' => 'user@[a\\b]',
        'unterminated domain literal' => 'user@[127.0.0.1',
        'NUL after domain' => "user@example.com\0",
        'trailing punctuation' => 'user@example.com.',
        'line break without following WSP after domain' => "user@example.com\r\n",
    ]);
});
