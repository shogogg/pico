<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;

/**
 * Parser that matches a regular expression at the current input offset.
 *
 * @extends AbstractParser<string>
 */
final class RegExpParser extends AbstractParser
{
    private readonly string $expression;

    /**
     * {@see RegExpParser} constructor.
     *
     * @param string $pattern The regular-expression pattern body.
     * @throws ParserException When the pattern is invalid.
     */
    public function __construct(public readonly string $pattern)
    {
        $expression = "\x01\\G(?:{$this->pattern})\x01u";

        self::assertExpressionIsValid($expression);

        $this->expression = $expression;
    }

    /** {@inheritDoc} */
    public function parseInput(ParserInput $input): ParserResult
    {
        $matched = preg_match($this->expression, $input->input, $matches, 0, $input->byteOffset());

        if ($matched === false) {
            throw new ParserException('The regular expression failed to execute: '.preg_last_error_msg());
        }

        if ($matched === 0) {
            return failure();
        }

        return success($matches[0], mb_strlen($matches[0], 'UTF-8'));
    }

    /**
     * Validates an expression without emitting a PHP warning.
     *
     * @throws ParserException When the expression is invalid.
     */
    private static function assertExpressionIsValid(string $expression): void
    {
        // PCRE reports invalid expressions as warnings; expose them as parser errors instead.
        set_error_handler(static function (): never {
            throw new ParserException('The regular expression is invalid.');
        });

        try {
            preg_match($expression, '');
        } finally {
            restore_error_handler();
        }
    }
}
