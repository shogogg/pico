<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Rfc5322;

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
        // WSP = SP(%x20) / HTAB(%x09) ; white space
        $wsp = Pico::oneOf(" \t");

        // DQUOTE = %x22 ; double quote
        $dquote = Pico::char('"');

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

        // comment = "(" *([FWS] ccontent) [FWS] ")"
        $comment = Pico::recursive(static function (Parser $self) use ($ctext, $fws, $quotedPair): Parser {
            // ccontent = ctext / quoted-pair / comment
            $ccontent = Pico::anyOf($ctext, $quotedPair, $self);

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
            Pico::oneOf("!#$%&'*+-/=?^_`{|}~"),
        );

        // dot-atom-text = 1*atext *("." 1*atext)
        $dotAtomText = Pico::concat(
            $atext->repeat(min: 1),
            Pico::seq(Pico::char('.'), $atext->repeat(min: 1))->repeat(),
        );

        // dot-atom = [CFWS] dot-atom-text [CFWS]
        $dotAtom = Pico::between(
            $cfws->optional(),
            $dotAtomText,
            $cfws->optional(),
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
        $quotedString = Pico::concat(
            Pico::skip($cfws->optional()),
            Pico::skip($dquote),
            Pico::seq($fws->optional(), $qcontent)->repeat(),
            $fws->optional(),
            Pico::skip($dquote),
            Pico::skip($cfws->optional()),
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
            Pico::concat(
                Pico::char('['),
                Pico::seq($fws->optional(), $dtext)->repeat(),
                $fws->optional(),
                Pico::char(']'),
            ),
            $cfws->optional(),
        );

        // domain = dot-atom / domain-literal
        $domain = Pico::anyOf($dotAtom, $domainLiteral);

        // addr-spec = local-part "@" domain
        $addrSpec = Pico::pair(
            $localPart,
            $domain,
            sep: Pico::char('@'),
        );
        return $addrSpec
            ->map(static fn (array $parts): array => [
                'local_part' => $parts[0],
                'domain' => $parts[1],
            ])
            ->complete();
    }
}
