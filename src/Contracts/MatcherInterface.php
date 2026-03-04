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
 * Defines the contract for executing regex pattern matching operations.
 *
 * The matcher is responsible for evaluating compiled regex patterns (represented
 * as AST nodes) against input strings. It provides various matching operations
 * including simple boolean tests, detailed match extraction, and global matching.
 * The matcher implements the ECMA-262 regex execution semantics and handles
 * regex flags like case-insensitive, multiline, and global matching.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface MatcherInterface
{
    /**
     * Test if the pattern matches the input string.
     *
     * Performs a simple boolean test to determine if the pattern matches anywhere
     * in the input. This is the most efficient matching operation when you only
     * need to know if a match exists, without capturing match details.
     *
     * @param NodeInterface $ast   The compiled pattern AST to match against the input
     * @param string        $input The input string to test for pattern matches
     * @param string        $flags Optional ECMA-262 regex flags (g, i, m, s, u, y) that
     *                             modify matching behavior such as case sensitivity and
     *                             multiline mode. Defaults to empty string (no flags).
     *
     * @return bool True if the pattern matches the input, false otherwise
     */
    public function test(NodeInterface $ast, string $input, string $flags = ''): bool;

    /**
     * Execute pattern matching and return detailed match results.
     *
     * Performs pattern matching and returns comprehensive information about the
     * first match, including captured groups, match position, and matched text.
     * Use this when you need detailed information about the match beyond a
     * simple boolean result.
     *
     * @param NodeInterface $ast   The compiled pattern AST to match against the input
     * @param string        $input The input string to search for pattern matches
     * @param string        $flags Optional ECMA-262 regex flags (g, i, m, s, u, y) that
     *                             modify matching behavior. Defaults to empty string.
     *
     * @return MatchResult Object containing match details including captured groups,
     *                     match index, and the matched substring
     */
    public function exec(NodeInterface $ast, string $input, string $flags = ''): MatchResult;

    /**
     * Find all pattern matches in the input string.
     *
     * Performs global pattern matching to find every occurrence of the pattern
     * in the input. Returns an array of match results, each containing detailed
     * information about a single match. This is equivalent to using the 'g' flag
     * in JavaScript regex.
     *
     * @param NodeInterface $ast   The compiled pattern AST to match against the input
     * @param string        $input The input string to search for all pattern matches
     * @param string        $flags Optional ECMA-262 regex flags (g, i, m, s, u, y) that
     *                             modify matching behavior. The 'g' flag is implied.
     *
     * @return array<int, MatchResult> Array of match results, one for each occurrence
     *                                 of the pattern in the input. Empty array if no matches.
     */
    public function matchAll(NodeInterface $ast, string $input, string $flags = ''): array;
}
