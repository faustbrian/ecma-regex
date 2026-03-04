<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\ValueObjects;

/**
 * Represents the result of a regex match operation.
 *
 * This immutable value object encapsulates all information about a single
 * regex match, including the matched text, its position, captured groups,
 * and the original input. Similar to JavaScript's RegExp.exec() return value.
 *
 * The class uses named constructor methods (failed() and success()) to
 * provide clear intent and ensure valid state combinations.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class MatchResult
{
    /**
     * Create a new match result instance.
     *
     * @param bool                    $matched  Whether a match was found
     * @param int                     $index    The zero-based position in the input where the match starts,
     *                                          or -1 if no match was found
     * @param string                  $match    The matched substring, or empty string if no match
     * @param array<int, null|string> $captures Array of captured group values, indexed by group number.
     *                                          Null values indicate unmatched optional groups. Index 0
     *                                          is reserved for the full match in ECMA-262 compatibility.
     * @param null|string             $input    The original input string that was matched against,
     *                                          useful for context when processing multiple results
     */
    public function __construct(
        public bool $matched,
        public int $index,
        public string $match,
        public array $captures = [],
        public ?string $input = null,
    ) {}

    /**
     * Create a failed match result.
     *
     * Returns a result indicating no match was found. The index is set to -1
     * as a sentinel value, and the match string and captures array are empty.
     *
     * ```php
     * $result = MatchResult::failed();
     * assert($result->matched === false);
     * assert($result->index === -1);
     * ```
     *
     * @return self A failed match result with default empty values
     */
    public static function failed(): self
    {
        return new self(
            matched: false,
            index: -1,
            match: '',
            captures: [],
        );
    }

    /**
     * Create a successful match result.
     *
     * Returns a result indicating a match was found at the specified position.
     * The captures array should contain all captured groups from the match,
     * with null values for unmatched optional groups.
     *
     * ```php
     * $result = MatchResult::success(
     *     index: 6,
     *     match: 'world',
     *     captures: ['world', 'wor'],
     *     input: 'hello world'
     * );
     * assert($result->matched === true);
     * assert($result->captures[1] === 'wor');
     * ```
     *
     * @param int                     $index    The zero-based position where the match starts
     * @param string                  $match    The matched substring
     * @param array<int, null|string> $captures Array of captured group values (optional)
     * @param null|string             $input    The original input string (optional)
     *
     * @return self A successful match result with the provided values
     */
    public static function success(
        int $index,
        string $match,
        array $captures = [],
        ?string $input = null,
    ): self {
        return new self(
            matched: true,
            index: $index,
            match: $match,
            captures: $captures,
            input: $input,
        );
    }
}
