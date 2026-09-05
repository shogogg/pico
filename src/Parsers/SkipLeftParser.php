<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;
use Pico\Internal\PicoInternal;

/**
 * Parser that discards the output of the left parser.
 *
 * @template TLeft
 * @template TRight
 * @extends AbstractParser<TRight>
 */
final class SkipLeftParser extends AbstractParser
{
    /** @var ContextualParser<TLeft> */
    private readonly ContextualParser $left;

    /** @var ContextualParser<TRight> */
    private readonly ContextualParser $right;

    /**
     * {@see SkipLeftParser} constructor.
     *
     * @param Parser<TLeft> $left
     * @param Parser<TRight> $right
     */
    public function __construct(Parser $left, Parser $right)
    {
        $this->left = PicoInternal::asContextualParser($left);
        $this->right = PicoInternal::asContextualParser($right);
    }

    /**
     * @return ParserResult<TRight>
     */
    public function parseInput(ParserInput $input): ParserResult
    {
        $leftResult = $this->left->parseInput($input);
        if ($leftResult->isFailure()) {
            return failure();
        }

        $leftLength = $leftResult->consumedLength();
        $rightResult = $this->right->parseInput($input->advanced($leftLength));
        if ($rightResult->isFailure()) {
            return failure();
        }

        return success($rightResult->output(), $leftLength + $rightResult->consumedLength());
    }
}
