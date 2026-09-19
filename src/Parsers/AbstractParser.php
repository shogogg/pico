<?php
/*
 * Copyright (c) 2026 shogogg <shogo@studiofly.net>.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
declare(strict_types=1);

namespace Pico\Parsers;

use Closure;
use Pico\Contracts\Parser;
use Pico\Contracts\ParserResult;
use Pico\Exceptions\ParserException;
use Pico\Internal\PicoInternal;

/**
 * Base class for parsers that operate on a ParserInput.
 *
 * @template T
 * @implements Parser<T>
 * @implements ContextualParser<T>
 */
abstract class AbstractParser implements Parser, ContextualParser
{
    /** {@inheritDoc} */
    abstract public function parseInput(ParserInput $input): ParserResult;

    /** {@inheritDoc} */
    final public function parse(string $input): ParserResult
    {
        return $this->parseInput(ParserInput::of($input));
    }

    /** {@inheritDoc} */
    final public function complete(): Parser
    {
        return self::create(function (ParserInput $input): ParserResult {
            $result = $this->parseInput($input);
            return $result->isFailure() || $input->advanced($result->consumedLength())->isAtEnd()
                ? $result
                : failure();
        });
    }

    /** {@inheritDoc} */
    final public function concat(): Parser
    {
        return self::create(
            fn (ParserInput $input): ParserResult => $this->parseInput($input)->concat(),
        );
    }

    /**
     * @template TExcept
     * @param Parser<TExcept> $except
     * @return Parser<T>
     */
    final public function except(Parser $except): Parser
    {
        assert($except instanceof ContextualParser);
        return self::create(function (ParserInput $input) use ($except): ParserResult {
            return $except->parseInput($input)->isSuccess()
                ? failure()
                : $this->parseInput($input);
        });
    }

    /** {@inheritDoc} */
    final public function join(string $separator = ''): Parser
    {
        return self::create(
            fn (ParserInput $input): ParserResult => $this->parseInput($input)->join($separator),
        );
    }

    /**
     * @template U
     * @param Closure(T): U $fn
     * @return Parser<U>
     */
    final public function map(Closure $fn): Parser
    {
        return self::create(
            fn (ParserInput $input): ParserResult => $this->parseInput($input)->map($fn),
        );
    }

    /** {@inheritDoc} */
    final public function optional(): Parser
    {
        return self::create(function (ParserInput $input): ParserResult {
            $result = $this->parseInput($input);
            return $result->isSuccess() ? $result : success('', 0);
        });
    }

    /**
     * @template U
     * @param Closure(): U $fallback
     * @return Parser<T|U>
     */
    final public function orElse(Closure $fallback): Parser
    {
        return self::create(function (ParserInput $input) use ($fallback): ParserResult {
            $result = $this->parseInput($input);
            return $result->isSuccess() ? $result : success($fallback(), 0);
        });
    }

    /** {@inheritDoc} */
    final public function repeat(int $min = 0, int $max = PHP_INT_MAX): Parser
    {
        if ($min < 0) {
            throw new ParserException('The minimum repetition count must not be negative.');
        }
        if ($max < $min) {
            throw new ParserException('The maximum repetition count must be at least the minimum repetition count.');
        }

        return self::create(function (ParserInput $input) use ($min, $max): ParserResult {
            /** @var list<T> $outputs */
            $outputs = [];
            $consumedLength = 0;
            $currentInput = $input;

            for ($count = 0; $count < $max; ++$count) {
                /** @var ParserResult<T> $result */
                $result = $this->parseInput($currentInput);
                if ($result->isFailure()) {
                    break;
                }

                $length = $result->consumedLength();
                if ($length === 0) {
                    break;
                }

                $outputs[] = $result->output();
                $consumedLength += $length;
                $currentInput = $currentInput->advanced($length);
            }

            if ($count < $min) {
                return failure();
            }

            /** @var ParserResult<list<T>> */
            return success($outputs, $consumedLength);
        });
    }

    /** {@inheritDoc} */
    final public function skip(): Parser
    {
        return $this->map(static fn (): string => '');
    }

    /**
     * @template U
     * @param Parser<U> $parser
     * @return Parser<array{T, U}>
     */
    final public function then(Parser $parser): Parser
    {
        $right = PicoInternal::asContextualParser($parser);

        return self::create(function (ParserInput $input) use ($right): ParserResult {
            $leftResult = $this->parseInput($input);
            if ($leftResult->isFailure()) {
                return failure();
            }

            $leftLength = $leftResult->consumedLength();
            $rightResult = $right->parseInput($input->advanced($leftLength));
            if ($rightResult->isFailure()) {
                return failure();
            }

            return success(
                [$leftResult->output(), $rightResult->output()],
                $leftLength + $rightResult->consumedLength(),
            );
        });
    }

    /**
     * @param Closure(T): bool $predicate
     * @return Parser<T>
     */
    final public function where(Closure $predicate): Parser
    {
        return self::create(function (ParserInput $input) use ($predicate): ParserResult {
            $result = $this->parseInput($input);
            return $result->isSuccess() && $predicate($result->output())
                ? $result
                : failure();
        });
    }

    /**
     * @template U
     * @param Closure(ParserInput): ParserResult<U> $parse
     * @return self<U>
     */
    private static function create(Closure $parse): self
    {
        /** @extends AbstractParser<T> */
        return new class ($parse) extends AbstractParser {
            /** @param Closure(ParserInput): ParserResult<T> $parse */
            public function __construct(private readonly Closure $parse)
            {
            }

            /** @return ParserResult<T> */
            public function parseInput(ParserInput $input): ParserResult
            {
                return ($this->parse)($input);
            }
        };
    }
}
