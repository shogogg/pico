<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes;

use Pico\Contracts\Parser;
use Pico\Pico;

/**
 * RFC 5322 addr-spec parser recipe.
 */
final class Rfc5322EmailParser
{
    /**
     * Creates a parser for an RFC 5322 addr-spec.
     *
     * @return Parser<array{local_part: string, domain: string}>
     */
    public static function address(): Parser
    {
        $alpha = Pico::alpha();

        $digit = Pico::digit();

        $specials = Pico::anyOf(
            Pico::char('!'),
            Pico::char('#'),
            Pico::char('$'),
            Pico::char('%'),
            Pico::char('&'),
            Pico::char('\''),
            Pico::char('*'),
            Pico::char('+'),
            Pico::char('-'),
            Pico::char('/'),
            Pico::char('='),
            Pico::char('?'),
            Pico::char('^'),
            Pico::char('_'),
            Pico::char('`'),
            Pico::char('{'),
            Pico::char('|'),
            Pico::char('}'),
            Pico::char('~'),
        );

        $atext = Pico::anyOf($alpha, $digit, $specials);

        $atom = Pico::repeat($atext, min: 1)->join();

        $dotAtom = Pico::sepBy($atom, Pico::char('.'), min: 1)->join('.');

        $qtext = Pico::predicate(static fn (string $char): bool => strlen($char) === 1
            && ord($char) >= 0x20
            && ord($char) <= 0x7e
            && $char !== '"'
            && $char !== '\\');

        $quotedPair = Pico::seq(Pico::char('\\'), Pico::anyChar())
            ->map(static fn (array $parts): string => $parts[1]);

        $quotedString = Pico::between(
            Pico::char('"'),
            Pico::char('"'),
            Pico::repeat(Pico::anyOf($qtext, $quotedPair))->join(),
        );

        $localPart = Pico::anyOf($dotAtom, $quotedString);

        $domain = $dotAtom;

        return Pico::seq($localPart, Pico::char('@'), $domain)
            ->map(static fn (array $parts): array => [
                'local_part' => $parts[0],
                'domain' => $parts[2],
            ]);
    }
}
