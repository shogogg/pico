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

        $wsp = Pico::anyOf(Pico::char(' '), Pico::char("\t"));

        $fws = Pico::anyOf(
            Pico::seq(
                Pico::repeat($wsp)->join(),
                Pico::string("\r\n"),
                Pico::repeat($wsp, min: 1)->join(),
            )->join(),
            Pico::repeat($wsp, min: 1)->join(),
        );

        $qtext = Pico::anyOf(
            Pico::char(chr(33)),            // %d33 ('!')
            Pico::range(chr(35), chr(91)),  // %d35-91 ('#' から '[')
            Pico::range(chr(93), chr(126)), // %d93-126 (']' から '~')
        );

        $quotedPair = Pico::seq(Pico::char('\\'), Pico::anyChar())
            ->map(static fn (array $parts): string => $parts[1]);

        $qcontent = Pico::anyOf($qtext, $quotedPair);

        $quotedString = Pico::between(
            Pico::char('"'),
            Pico::char('"'),
            Pico::seq(
                Pico::repeat(
                    Pico::seq(
                        Pico::optional($fws)->map(static fn (?string $value): string => $value ?? ''),
                        $qcontent,
                    )->join(),
                )->join(),
                Pico::optional($fws)->map(static fn (?string $value): string => $value ?? ''),
            )->join(),
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
