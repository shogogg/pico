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
    /**
     * {@see PicoInternal} constructor.
     */
    private function __construct()
    {
        // Nothing to do.
    }

    /**
     * Returns the parser as a ContextualParser.
     *
     * @template T
     * @param Parser<T> $parser
     * @return ContextualParser<T>
     * @throws ParserException When the parser does not implement ContextualParser.
     * @internal
     */
    public static function asContextualParser(Parser $parser): ContextualParser
    {
        self::ensureContextualParser($parser);
        return $parser;
    }

    /**
     * Ensures that the parser supports parsing from a ParserInput.
     *
     * @template T
     * @param Parser<T> $parser
     * @phpstan-assert ContextualParser<T> $parser
     * @psalm-assert ContextualParser<T> $parser
     * @throws ParserException When the parser does not implement ContextualParser.
     */
    public static function ensureContextualParser(Parser $parser): void
    {
        if (!($parser instanceof ContextualParser)) {
            $className = $parser::class;
            throw new ParserException($className . ' is not a ContextualParser');
        }
    }
}
