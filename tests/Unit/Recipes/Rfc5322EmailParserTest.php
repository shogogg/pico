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
        'quoted local part' => [
            '"quoted local"@example.com',
            ['local_part' => 'quoted local', 'domain' => 'example.com'],
        ],
        'escaped character in quoted local part' => [
            '"foo\\"bar"@example.com',
            ['local_part' => 'foo"bar', 'domain' => 'example.com'],
        ],
        'empty quoted local part' => [
            '""@example.com',
            ['local_part' => '', 'domain' => 'example.com'],
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
        'consecutive dots in local part' => 'first..last@example.com',
        'missing domain' => 'first.last@',
        'first last@example.com',
        'あいう@example.com',
    ]);
});
