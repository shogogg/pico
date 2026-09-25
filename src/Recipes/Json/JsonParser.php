<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Json;

use Pico\Contracts\Parser;

/**
 * RFC 8259 JSON parser recipe.
 *
 * @phpstan-import-type JsonValue from JsonSyntax
 * @phpstan-import-type JsonValueSimplified from JsonSyntaxSimplified
 */
final class JsonParser
{
    /**
     * Creates a parser for a complete RFC 8259 JSON text.
     *
     * @return Parser<JsonValue>
     */
    public static function document(): Parser
    {
        return JsonSyntax::value()->complete();
    }

    /**
     * Creates a parser for a complete, deliberately simplified JSON text.
     *
     * Strings support only escaped double quotes, and numbers support only
     * integer syntax. Arrays, objects, whitespace, booleans, and null retain
     * their JSON structure for learning purposes.
     *
     * @return Parser<JsonValueSimplified>
     */
    public static function simplified(): Parser
    {
        return JsonSyntaxSimplified::value()->complete();
    }
}
