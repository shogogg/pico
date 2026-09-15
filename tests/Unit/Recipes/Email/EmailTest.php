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

describe('Email', function (): void {
    it('should expose its local part and domain', function (): void {
        // Act
        $email = new Email('local.part', 'example.com');

        // Assert
        expect($email->localPart)->toBe('local.part');
        expect($email->domain)->toBe('example.com');
    });
});
