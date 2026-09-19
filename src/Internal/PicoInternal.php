<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Internal;

use Pico\Contracts\Parser;
use Pico\Exceptions\ParserException;
use Pico\Parsers\ContextualParser;

/**
 * Internal helpers shared by Pico parsers.
 *
 * @internal
 */
final class PicoInternal
{
    private function __construct()
    {
        // Instantiation is not allowed.
    }

    /**
     * Returns the parser as a ContextualParser.
     *
     * @template T
     * @param Parser<T> $parser
     * @return ContextualParser<T>
     * @internal
     */
    public static function asContextualParser(Parser $parser): ContextualParser
    {
        assert($parser instanceof ContextualParser);
        return $parser;
    }

    /**
     * Returns a parser with an intentionally discarded output type as a ContextualParser.
     *
     * @param Parser<*> $parser
     * @return ContextualParser<*>
     * @internal
     */
    public static function asUntypedContextualParser(Parser $parser): ContextualParser
    {
        assert($parser instanceof ContextualParser);
        return $parser;
    }
}
