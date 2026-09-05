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
 * RFC 5322 current-syntax addr-spec parser recipe.
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
        // HTAB = %x09 ; horizontal tab
        $htab = Pico::char("\t");

        // SP = %x20
        $sp = Pico::char(' ');

        // WSP = SP / HTAB ; white space
        $wsp = Pico::anyOf($sp, $htab);

        // VCHAR = %x21-7E ; visible (printing) characters
        $vchar = Pico::range(chr(0x21), chr(0x7E));

        // quoted-pair = "\\" (VCHAR / WSP)
        $quotedPair = Pico::skipLeft(Pico::char('\\'), Pico::anyOf($vchar, $wsp));

        // FWS = ([*WSP CRLF] 1*WSP)
        $fws = Pico::join(
            Pico::skipRight($wsp->repeat(), Pico::string("\r\n"))->join()->optional(),
            $wsp->repeat(min: 1)->join(),
        );

        // ctext = %d33-39 / %d42-91 / %d93-126 ; Printable US-ASCII characters not including "(", ")", or "\"
        $ctext = Pico::anyOf(
            Pico::range(chr(33), chr(39)),
            Pico::range(chr(42), chr(91)),
            Pico::range(chr(93), chr(126)),
        );

        $comment = null;
        // comment = "(" *([FWS] ccontent) [FWS] ")"
        $comment = Pico::lazy(function () use (&$comment, $ctext, $fws, $quotedPair): Parser {
            assert($comment !== null);

            // ccontent = ctext / quoted-pair / comment
            $ccontent = Pico::anyOf($ctext, $quotedPair, $comment);

            return Pico::skip(
                Pico::char('('),
                Pico::seq($fws->optional(), $ccontent)->repeat(),
                $fws->optional(),
                Pico::char(')'),
            );
        });

        // CFWS = (1*([FWS] comment) [FWS]) / FWS
        $cfws = Pico::anyOf(
            Pico::skip(
                Pico::seq($fws->optional(), $comment)->repeat(min: 1),
                $fws->optional(),
            ),
            Pico::skip($fws),
        );

        // atext = ALPHA / DIGIT / "!" / "#" / "$" / "%" / "&" / "'" / "*" / "+" / "-" / "/" / "=" / "?" / "^" / "_" / "`" / "{" / "|" / "}" / "~"
        $atext = Pico::anyOf(
            Pico::alpha(),
            Pico::digit(),
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

        // dot-atom-text = 1*atext *("." 1*atext)
        $dotAtomText = Pico::join(
            $atext->repeat(min: 1)->join(),
            Pico::join(Pico::char('.'), $atext->repeat(min: 1)->join())->repeat()->join(),
        );

        // dot-atom = [CFWS] dot-atom-text [CFWS]
        $dotAtom = Pico::between(
            $cfws->optional(),
            $cfws->optional(),
            $dotAtomText,
        );

        // qtext = %d33 / %d35-91 / %d93-126
        $qtext = Pico::anyOf(
            Pico::char(chr(33)),
            Pico::range(chr(35), chr(91)),
            Pico::range(chr(93), chr(126)),
        );

        // qcontent = qtext / quoted-pair
        $qcontent = Pico::anyOf($qtext, $quotedPair);

        // quoted-string = [CFWS] DQUOTE *([FWS] qcontent) [FWS] DQUOTE [CFWS]
        $quotedString = Pico::between(
            $cfws->optional(),
            $cfws->optional(),
            Pico::between(
                Pico::char('"'),
                Pico::char('"'),
                Pico::join(
                    Pico::join($fws->optional(), $qcontent)->repeat()->join(),
                    $fws->optional(),
                ),
            ),
        );

        // local-part = dot-atom / quoted-string
        $localPart = Pico::anyOf($dotAtom, $quotedString);

        // dtext = %d33-90 / %d94-126 ; Printable US-ASCII characters not including "[", "]", or "\"
        $dtext = Pico::anyOf(
            Pico::range(chr(33), chr(90)),
            Pico::range(chr(94), chr(126)),
        );

        // domain-literal = [CFWS] "[" *([FWS] dtext) [FWS] "]" [CFWS]
        $domainLiteral = Pico::between(
            $cfws->optional(),
            $cfws->optional(),
            Pico::join(
                Pico::char('['),
                Pico::join($fws->optional(), $dtext)->repeat()->join(),
                $fws->optional(),
                Pico::char(']'),
            ),
        );

        // domain = dot-atom / domain-literal
        $domain = Pico::anyOf($dotAtom, $domainLiteral);

        // addr-spec = local-part "@" domain
        return Pico::seq($localPart, Pico::char('@'), $domain)
            ->map(static fn (array $parts): array => [
                'local_part' => $parts[0],
                'domain' => $parts[2],
            ])
            ->complete();
    }
}
