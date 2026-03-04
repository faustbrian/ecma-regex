<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Contracts;

use Cline\EcmaRegex\ValueObjects\MatchResult;

/**
 * Defines the contract for compiled ECMA-262 regex patterns.
 *
 * A compiled pattern is an immutable, executable representation of a regex
 * that has been parsed and validated. It encapsulates both the pattern source
 * and its associated flags, providing methods to perform matching operations
 * against input strings. Compiled patterns can be cached and reused for
 * efficient repeated matching without recompilation overhead.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface PatternInterface
{
    /**
     * Test if the pattern matches the input string.
     *
     * Performs a simple boolean test to determine if the pattern matches anywhere
     * in the input. This is the most efficient matching operation when you only
     * need to know if a match exists, without capturing match details or positions.
     *
     * @param string $input The input string to test against the pattern
     *
     * @return bool True if the pattern matches the input, false otherwise
     */
    public function test(string $input): bool;

    /**
     * Execute pattern matching and return detailed match results.
     *
     * Performs pattern matching and returns comprehensive information about the
     * first match, including captured groups, match position, and matched text.
     * This provides full details about how and where the pattern matched.
     *
     * @param string $input The input string to search for pattern matches
     *
     * @return MatchResult Object containing detailed match information including
     *                     captured groups, match index, and matched substring
     */
    public function exec(string $input): MatchResult;

    /**
     * Find all occurrences of the pattern in the input string.
     *
     * Performs global pattern matching to find every occurrence of the pattern
     * in the input. Each match includes full details about captured groups and
     * position. This is equivalent to JavaScript regex with the 'g' flag.
     *
     * @param string $input The input string to search for all pattern matches
     *
     * @return array<int, MatchResult> Array of match results, one for each occurrence.
     *                                 Returns empty array if no matches found.
     */
    public function matchAll(string $input): array;

    /**
     * Get the original regex pattern source string.
     *
     * Returns the raw pattern string that was used to compile this pattern,
     * without delimiters or flags. This is useful for debugging, logging,
     * or reconstructing the full regex syntax.
     *
     * @return string The original pattern source string
     */
    public function source(): string;

    /**
     * Get the regex flags associated with this pattern.
     *
     * Returns the ECMA-262 flags (g, i, m, s, u, y) that were specified when
     * compiling this pattern. Flags modify matching behavior such as case
     * sensitivity, multiline mode, and unicode handling.
     *
     * @return string String containing all flags, or empty string if no flags set
     */
    public function flags(): string;
}
