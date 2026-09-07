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
use Pico\Pico;

/**
 * RFC 8259 JSON parser recipe.
 *
 * @phpstan-import-type JsonValue from JsonSyntax
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
        $whitespace = JsonSyntax::whitespace();
        $json = Pico::between(
            $whitespace,
            $whitespace,
            JsonSyntax::value(),
        );
        return $json->complete();
    }
}
