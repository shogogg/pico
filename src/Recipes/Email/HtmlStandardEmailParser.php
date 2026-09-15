<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Recipes\Email;

use Pico\Contracts\Parser;
use Pico\Pico;

/**
 * HTML Standard email-address parser recipe.
 */
final class HtmlStandardEmailParser
{
    /**
     * Creates a parser for an HTML Standard email address.
     *
     * @return Parser<Email>
     */
    public static function address(): Parser
    {
        // let-dig = ALPHA / DIGIT
        $letDig = Pico::anyOf(Pico::alpha(), Pico::digit());

        // let-dig-hyp = let-dig / "-"
        $letDigHyp = Pico::anyOf($letDig, Pico::char('-'));

        // ldh-str = let-dig-hyp *(let-dig-hyp)
        $ldhStr = $letDigHyp->repeat(min: 1)->concat();

        // label = let-dig [ [ldh-str] let-dig ] ; limited to a length of 63 characters by RFC 1034 section 3.5
        // Must be limited to 63 characters, where both the first and last characters are let-dig (alphanumeric).
        $label = $ldhStr->where(static function (string $value): bool {
            return strlen($value) <= 63 && ctype_alnum($value[0]) && ctype_alnum($value[-1]);
        });

        // atext = ALPHA / DIGIT / "!" / "#" / "$" / "%" / "&" / "'" / "*" / "+" / "-" / "/" / "=" / "?" / "^" / "_" / "`" / "{" / "|" / "}" / "~"
        $atext = Pico::anyOf(
            Pico::alpha(),
            Pico::digit(),
            Pico::oneOf("!#$%&'*+-/=?^_`{|}~"),
        );

        // email = 1*( atext / "." ) "@" label *( "." label )
        $email = Pico::pair(
            Pico::anyOf($atext, Pico::char('.'))->repeat(min: 1)->concat(),
            Pico::sepBy($label, Pico::char('.'), min: 1)->join('.'),
            sep: Pico::char('@'),
        );
        return $email
            ->map(static fn (array $outputs): Email => new Email(
                localPart: $outputs[0],
                domain: $outputs[1],
            ))
            ->complete();
    }
}
