<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Email;

/**
 * An email address parsed by an email recipe.
 */
final readonly class Email
{
    public function __construct(
        public string $localPart,
        public string $domain,
    ) {
    }
}
