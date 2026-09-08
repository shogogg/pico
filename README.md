# Pico

Pico is a parser-combinator library for PHP.

## Requirements

- PHP 8.5 or later
- `ext-ctype`
- `ext-mbstring`

## Installation

```bash
composer require shogogg/pico
```

## Quick example

```php
use Pico\Pico;

$parser = Pico::char('A')
    ->then(Pico::digit())
    ->join();

$result = $parser->parse('A1');

if ($result->isSuccess()) {
    echo $result->output(); // A1
}
```

## Core Parsers

All factories are static methods on `Pico\Pico`.

### `Pico::anyChar()`
Matches any single UTF-8 character.

```php
Pico::anyChar()->parse('🍣寿司'); // Success('🍣', 1)
```

### `Pico::ascii()`
Matches one ASCII character.

```php
Pico::ascii()->parse('A'); // Success('A', 1)
```

### `Pico::alpha()`
Matches one ASCII alphabetic character.

```php
Pico::alpha()->parse('Z9'); // Success('Z', 1)
```

### `Pico::digit()`
Matches one ASCII decimal digit.

```php
Pico::digit()->parse('42'); // Success('4', 1)
```

### `Pico::alphaNum()`
Matches one ASCII letter or digit.

```php
Pico::alphaNum()->parse('7!'); // Success('7', 1)
```

### `Pico::whitespace()`
Matches one ASCII whitespace character.

```php
Pico::whitespace()->parse("\tword"); // Success("\\t", 1)
```

### `Pico::whitespaces()`
Matches one or more consecutive ASCII whitespace characters.

```php
Pico::whitespaces()->parse(" \n\tword"); // Success(" \\n\\t", 3)
```

### `Pico::char()`
Matches exactly a specified UTF-8 character.

```php
Pico::char('あ')->parse('あいう'); // Success('あ', 1)
```

### `Pico::charWhere()`
Matches one character when its predicate returns `true`.

```php
Pico::charWhere(ctype_xdigit(...))->parse('f0'); // Success('f', 1)
```

### `Pico::oneOf()`
Matches one character from a UTF-8 character set.

```php
Pico::oneOf('+-*/')->parse('* 3'); // Success('*', 1)
```

### `Pico::range()`
Matches one UTF-8 character in the inclusive Unicode code-point range.

```php
Pico::range('あ', 'お')->parse('え'); // Success('え', 1)
```

### `Pico::string()`
Matches the specified string.

```php
Pico::string('hello')->parse('hello!'); // Success('hello', 5)
```

### `Pico::regexp()`
Matches a UTF-8 regular-expression pattern body at the current input position.

```php
Pico::regexp('[A-Z]+')->parse('ABC123'); // Success('ABC', 3)
```

### `Pico::eof()`
Succeeds only at the end of input, returning `''` without consumption.

```php
Pico::eof()->parse(''); // Success('', 0)
```

## Combinators

Each combinator accepts one or more parsers and returns a new parser that composes them.

### `Pico::anyOf()`
Returns a parser that evaluates candidates from left to right and returns the first successful result.

```php
Pico::anyOf(Pico::string('yes'), Pico::string('no'))->parse('nope'); // Success('no', 2)
```

### `Pico::seq()`
Returns a parser that consumes its parsers from left to right and returns their outputs as a list.

```php
Pico::seq(Pico::alpha(), Pico::digit(), Pico::digit())->parse('A12'); // Success(['A', '1', '2'], 3)
```

### `Pico::pair()`
Returns a parser that produces `[left, right]`. Its optional `sep` is consumed and discarded.

```php
Pico::pair(Pico::string('key'), Pico::string('value'), sep: Pico::char(':'))
    ->parse('key:value'); // Success(['key', 'value'], 9)
```

### `Pico::triple()`
Returns a parser that consumes three parsers and produces `[first, second, third]`.

```php
Pico::triple(Pico::char('a'), Pico::char('b'), Pico::char('c'))
    ->parse('abc'); // Success(['a', 'b', 'c'], 3)
```

### `Pico::sepBy()`
Returns a parser that parses values with `content`, separated by `separator`.
It consumes the separators but returns an array containing only the values parsed by `content`.

`min` is the minimum required number of values and defaults to `0`; when no first value matches, the result is `[]`, and a trailing separator remains unconsumed.

```php
Pico::sepBy(Pico::regexp('\\d+'), Pico::char(','))
    ->parse('1,22,333,'); // Success(['1', '22', '333'], 8)
```

### `Pico::between()`
Returns a parser that consumes `open`, `content`, and `close`, producing only the content.

```php
Pico::between(Pico::char('['), Pico::regexp('\\d+'), Pico::char(']'))
    ->parse('[42]'); // Success('42', 4)
```

### `Pico::join()`
Returns a parser that consumes its parsers in sequence and joins their outputs.

```php
Pico::join(Pico::char('A'), Pico::digit())->parse('A1'); // Success('A1', 2)
```

### `Pico::skip()`
Returns a parser that consumes its parsers while discarding every output. It produces `''`.

```php
Pico::skip(Pico::string('//'), Pico::whitespaces())->parse('// comment'); // Success('', 3)
```

### `Pico::skipLeft()`
Returns a parser that consumes the left parser and then produces the right output.

```php
Pico::skipLeft(Pico::char(':'), Pico::digit())->parse(':1'); // Success('1', 2)
```

### `Pico::skipRight()`
Returns a parser that consumes the right parser after the left parser and produces the left output.

```php
Pico::skipRight(Pico::digit(), Pico::char(';'))->parse('1;'); // Success('1', 2)
```

### `Pico::lazy()`
Returns a parser that defers constructing its inner parser until parsing begins, then caches the factory result.

```php
Pico::lazy(static fn (): Parser => Pico::char('A'))->parse('ABC'); // Success('A', 1)
```

## `Parser` operations

Every factory returns a `Pico\Contracts\Parser`.

### `Parser::parse()`
Parses a valid UTF-8 input string from its beginning and returns a `ParserResult`.

```php
Pico::digit()->parse('7x'); // Success('7', 1)
```

### `Parser::complete()`
Requires a successful parser to consume the entire input.

```php
Pico::digit()->complete()->parse('7x'); // Failure
```

### `Parser::except()`
Fails when the exclusion parser succeeds at the current position; otherwise it evaluates the original parser.

```php
Pico::digit()->except(Pico::char('0'))->parse('0'); // Failure
```

### `Parser::join()`
Stringifies a successful output. Arrays are joined with the optional separator; unsupported values raise `ParserException`.

```php
Pico::digit()->repeat(min: 2)->join('-')->parse('12'); // Success('1-2', 2)
```

### `Parser::map()`
Transforms a successful output while preserving its consumed length.

```php
Pico::digit()->map(static fn (string $value): int => (int) $value * 2)
    ->parse('4'); // Success(8, 1)
```

### `Parser::optional()`
Turns a failure into a zero-consumption success with `''`.

```php
Pico::char('-')->optional()->then(Pico::digit())->join()->parse('5'); // Success('5', 1)
```

### `Parser::orElse()`
Turns a failure into a zero-consumption success with the fallback closure's value.

```php
Pico::char('-')->orElse(static fn (): string => '+')->parse('5'); // Success('+', 0)
```

### `Parser::repeat()`
Parses consecutive occurrences into a list. It stops at failure, `max`, or a zero-consumption success. Invalid bounds raise `ParserException`.

```php
Pico::digit()->repeat(min: 2, max: 4)->parse('12345'); // Success(['1', '2', '3', '4'], 4)
```

### `Parser::skip()`
Discards a successful output while preserving consumption.

```php
Pico::string('comment')->skip()->parse('comment'); // Success('', 7)
```

### `Parser::then()`
Consumes this parser followed by another and returns `[left, right]`.

```php
Pico::char('A')->then(Pico::digit())->parse('A1'); // Success(['A', '1'], 2)
```

### `Parser::where()`
Keeps a successful result only when its predicate returns `true`.

```php
Pico::digit()->where(static fn (string $value): bool => $value !== '0')->parse('0'); // Failure
```

## `ParserResult` operations

`Parser::parse()` returns a `Pico\Contracts\ParserResult`.

### `ParserResult::isSuccess()`
Returns whether parsing succeeded.

### `ParserResult::isFailure()`
Returns whether parsing failed.

### `ParserResult::consumedLength()`
Returns the number of UTF-8 characters consumed. A failure consumes zero characters.

```php
Pico::string('あい')->parse('あいう')->consumedLength(); // 2
```

### `ParserResult::output()`
Returns the successful output. Calling it on a failure throws `LogicException`.

```php
Pico::string('ok')->parse('ok!')->output(); // 'ok'
```

### `ParserResult::map()`
Transforms a successful output while preserving the consumed length.

```php
Pico::digit()
    ->parse('4')
    ->map(static fn (string $value): int => intval($value) * 2)
    ->output(); // 8
```

### `ParserResult::join()`
Joins a successful array output with the optional separator while preserving consumption. Unsupported values raise `ParserException`.

```php
Pico::seq(Pico::char('a'), Pico::char('b'))->parse('ab')->join('-')->output(); // 'a-b'
```

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
