<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Tests\Unit\Recipes\Email;

use Pico\Recipes\Email\Email;
use Pico\Recipes\Email\HtmlStandardEmailParser;

describe('HtmlStandardEmailParser::address', function (): void {
    it('should parse an HTML Standard email address and consume the complete input', function (string $input, Email $expected): void {
        // Act
        $actual = HtmlStandardEmailParser::address()->parse($input);

        // Assert
        expect($actual)->toBeSuccess();
        expect($actual->consumedLength())->toBe(mb_strlen($input));
        expect($actual->output()->localPart)->toBe($expected->localPart);
        expect($actual->output()->domain)->toBe($expected->domain);
    })->with([
        'ordinary address' => [
            'simple@example.com',
            new Email('simple', 'example.com'),
        ],
        'local part with leading dot' => [
            '.user@example.com',
            new Email('.user', 'example.com'),
        ],
        'local part with trailing dot' => [
            'user.@example.com',
            new Email('user.', 'example.com'),
        ],
        'local part with consecutive dots' => [
            'a..b@example.com',
            new Email('a..b', 'example.com'),
        ],
        'atext special characters' => [
            "!#$%&'*+-/=?^_`{|}~@example.com",
            new Email("!#$%&'*+-/=?^_`{|}~", 'example.com'),
        ],
        'single character label' => [
            'user@a',
            new Email('user', 'a'),
        ],
        'label with internal hyphen' => [
            'user@sub-domain.example',
            new Email('user', 'sub-domain.example'),
        ],
        'label with 63 characters' => [
            'user@' . str_repeat('a', 63),
            new Email('user', str_repeat('a', 63)),
        ],
    ]);

    it('should fail when an HTML Standard email address is invalid', function (string $input): void {
        // Act
        $actual = HtmlStandardEmailParser::address()->parse($input);

        // Assert
        expect($actual)->toBeFailure();
    })->with([
        'empty local part' => '@example.com',
        'quoted local part' => '"user"@example.com',
        'comment in local part' => 'user(comment)@example.com',
        'domain literal' => 'user@[127.0.0.1]',
        'label with 64 characters' => 'user@' . str_repeat('a', 64),
        'label starting with a hyphen' => 'user@-example.com',
        'label ending with a hyphen' => 'user@example-.com',
        'empty domain' => 'user@',
        'empty label after dot' => 'user@example..com',
        'empty label at domain start' => 'user@.example.com',
        'empty label at domain end' => 'user@example.com.',
        'whitespace' => 'user name@example.com',
        'non-ASCII local part' => 'ユーザー@example.com',
        'non-ASCII domain' => 'user@例え.com',
        'multiple at signs' => 'user@@example.com',
        'trailing input' => 'user@example.com!',
    ]);
});
