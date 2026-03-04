<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex;

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\Contracts\PatternInterface;
use Cline\EcmaRegex\ValueObjects\MatchResult;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

use function md5;
use function sprintf;

/**
 * Central manager for compiling and executing ECMA-262 regex patterns.
 *
 * The manager serves as the primary entry point for working with regex patterns,
 * providing a high-level API for pattern compilation and matching operations.
 * It coordinates the lexer and parser to compile patterns, implements two-tier
 * caching (memory and optional persistent) for performance, and provides
 * convenient methods for common matching operations like test, match, and matchAll.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EcmaRegexManager
{
    /**
     * In-memory cache of compiled patterns.
     *
     * Patterns are stored by their cache key (hash of source and flags) to avoid
     * recompilation overhead. This provides the fastest possible access to recently
     * used patterns within the same request or process lifecycle.
     *
     * @var array<string, PatternInterface>
     */
    private array $patterns = [];

    /**
     * Create a new manager instance with compilation and caching dependencies.
     *
     * @param LexerInterface       $lexer        The lexer implementation used for tokenizing
     *                                           regex pattern strings into token sequences.
     * @param ParserInterface      $parser       The parser implementation used for constructing
     *                                           Abstract Syntax Trees from token sequences.
     * @param null|CacheRepository $cache        Optional persistent cache repository for storing
     *                                           compiled patterns across requests. When null,
     *                                           only in-memory caching is used. Requires Laravel
     *                                           cache implementation.
     * @param bool                 $cacheEnabled Controls whether persistent caching is active.
     *                                           When false, patterns are only cached in memory.
     *                                           Defaults to true for optimal performance.
     * @param int                  $cacheTtl     Time-to-live for cached patterns in seconds.
     *                                           Determines how long compiled patterns persist
     *                                           in the cache store. Defaults to 3600 (1 hour).
     */
    public function __construct(
        private readonly LexerInterface $lexer,
        private readonly ParserInterface $parser,
        private readonly ?CacheRepository $cache = null,
        private readonly bool $cacheEnabled = true,
        private readonly int $cacheTtl = 3_600,
    ) {}

    /**
     * Compile a regex pattern from source string and flags.
     *
     * Compiles the pattern through lexical analysis and parsing, with automatic
     * two-tier caching. Checks memory cache first, then persistent cache if enabled,
     * and only performs compilation if the pattern is not cached. Compiled patterns
     * are stored in both cache layers for future use.
     *
     * @param string $pattern The ECMA-262 regex pattern source string to compile.
     *                        Must be valid regex syntax or compilation will fail.
     * @param string $flags   Optional ECMA-262 regex flags (g, i, m, s, u, y) that
     *                        modify matching behavior. Defaults to empty (no flags).
     *
     * @return PatternInterface The compiled pattern ready for matching operations
     */
    public function compile(string $pattern, string $flags = ''): PatternInterface
    {
        $cacheKey = $this->getCacheKey($pattern, $flags);

        // Check memory cache first
        if (isset($this->patterns[$cacheKey])) {
            return $this->patterns[$cacheKey];
        }

        // Check persistent cache if enabled
        if ($this->cacheEnabled && $this->cache instanceof CacheRepository) {
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof PatternInterface) {
                $this->patterns[$cacheKey] = $cached;

                return $cached;
            }
        }

        // Compile the pattern
        $compiled = Pattern::compile(
            $pattern,
            $flags,
            $this->lexer,
            $this->parser,
        );

        // Store in memory cache
        $this->patterns[$cacheKey] = $compiled;

        // Store in persistent cache if enabled
        if ($this->cacheEnabled && $this->cache instanceof CacheRepository) {
            $this->cache->put($cacheKey, $compiled, $this->cacheTtl);
        }

        return $compiled;
    }

    /**
     * Test if a pattern matches the input string.
     *
     * Convenience method that compiles the pattern (with caching) and performs
     * a boolean match test. This is the most efficient way to check if a pattern
     * matches when you don't need capture details.
     *
     * @param string $pattern The ECMA-262 regex pattern source string
     * @param string $input   The input string to test for pattern matches
     * @param string $flags   Optional ECMA-262 regex flags. Defaults to empty string.
     *
     * @return bool True if the pattern matches anywhere in the input, false otherwise
     */
    public function test(string $pattern, string $input, string $flags = ''): bool
    {
        return $this->compile($pattern, $flags)->test($input);
    }

    /**
     * Execute pattern matching and return detailed match results.
     *
     * Convenience method that compiles the pattern (with caching) and executes
     * matching to extract the first match with full details including captured
     * groups and positions. Use this when you need more than a boolean result.
     *
     * @param string $pattern The ECMA-262 regex pattern source string
     * @param string $input   The input string to search for matches
     * @param string $flags   Optional ECMA-262 regex flags. Defaults to empty string.
     *
     * @return MatchResult Object containing detailed match information including
     *                     captured groups, match index, and matched text
     */
    public function match(string $pattern, string $input, string $flags = ''): MatchResult
    {
        return $this->compile($pattern, $flags)->exec($input);
    }

    /**
     * Find all occurrences of the pattern in the input string.
     *
     * Convenience method that compiles the pattern (with caching) and performs
     * global matching to find every occurrence. Each match includes full details
     * about captured groups and positions. Equivalent to JavaScript regex with 'g' flag.
     *
     * @param string $pattern The ECMA-262 regex pattern source string
     * @param string $input   The input string to search for all matches
     * @param string $flags   Optional ECMA-262 regex flags. Defaults to empty string.
     *
     * @return array<int, MatchResult> Array of match results, one per occurrence.
     *                                 Empty array if no matches found.
     */
    public function matchAll(string $pattern, string $input, string $flags = ''): array
    {
        return $this->compile($pattern, $flags)->matchAll($input);
    }

    /**
     * Clear all cached patterns from memory and persistent storage.
     *
     * Removes all compiled patterns from both the in-memory cache and the
     * persistent cache (if configured). Use this to free memory or force
     * recompilation of all patterns, which may be necessary after updating
     * lexer or parser implementations.
     */
    public function flushCache(): void
    {
        $this->patterns = [];

        if (!$this->cache instanceof CacheRepository) {
            return;
        }

        $this->cache->clear();
    }

    /**
     * Generate a unique cache key for a pattern and its flags.
     *
     * Creates a stable, deterministic key based on the pattern source and flags.
     * The pattern is hashed using MD5 to produce a fixed-length key suitable
     * for cache storage, while preserving uniqueness for different patterns.
     *
     * @param string $pattern The regex pattern source string to hash
     * @param string $flags   The regex flags string
     *
     * @return string The cache key in format "ecma-regex:{hash}:{flags}"
     */
    private function getCacheKey(string $pattern, string $flags): string
    {
        return sprintf('ecma-regex:%s:%s', md5($pattern), $flags);
    }
}
