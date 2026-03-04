<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex;

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Contracts\MatcherInterface;
use Cline\EcmaRegex\Contracts\NodeInterface;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\Contracts\PatternInterface;
use Cline\EcmaRegex\ValueObjects\MatchResult;

use function resolve;

/**
 * Immutable compiled ECMA-262 regex pattern.
 *
 * Represents a fully compiled regex pattern ready for matching operations.
 * This class is immutable and thread-safe, holding the original source,
 * flags, and the compiled AST representation. Similar to JavaScript's
 * RegExp object.
 *
 * Instances are created via the static compile() method, which performs
 * lexical analysis and parsing to produce the internal AST. Once compiled,
 * the pattern can be used repeatedly for matching operations without
 * recompilation overhead.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Pattern implements PatternInterface
{
    /**
     * Create a new pattern instance.
     *
     * @param string        $source The original regex pattern source string
     * @param string        $flags  The regex flags (g for global, i for case-insensitive,
     *                              m for multiline, s for dotall, u for unicode, y for sticky)
     * @param NodeInterface $ast    The compiled Abstract Syntax Tree representing the pattern
     */
    private function __construct(
        private string $source,
        private string $flags,
        private NodeInterface $ast,
    ) {}

    /**
     * Compile a regex pattern from source string.
     *
     * Performs two-phase compilation:
     * 1. Lexical analysis: tokenizes the source into a stream of tokens
     * 2. Parsing: builds an Abstract Syntax Tree from the tokens
     *
     * The resulting pattern is immutable and can be reused for multiple
     * matching operations without recompilation.
     *
     * ```php
     * $pattern = Pattern::compile('/hello/', 'i', $lexer, $parser);
     * ```
     *
     * @param string          $source The regex pattern source string
     * @param string          $flags  The regex flags (g, i, m, s, u, y)
     * @param LexerInterface  $lexer  The lexer for tokenization
     * @param ParserInterface $parser The parser for AST generation
     *
     * @return self The compiled immutable pattern instance
     */
    public static function compile(
        string $source,
        string $flags,
        LexerInterface $lexer,
        ParserInterface $parser,
    ): self {
        $tokens = $lexer->tokenize($source);
        $ast = $parser->parse($tokens);

        return new self($source, $flags, $ast);
    }

    /**
     * Test if the pattern matches the input string.
     *
     * Returns true if any match is found, false otherwise. This is a
     * lightweight operation that stops at the first match. Equivalent
     * to JavaScript's RegExp.test() method.
     *
     * ```php
     * if ($pattern->test('hello world')) {
     *     echo 'Match found!';
     * }
     * ```
     *
     * @param string $input The string to test against
     *
     * @return bool True if the pattern matches, false otherwise
     */
    public function test(string $input): bool
    {
        /** @var MatcherInterface $matcher */
        $matcher = resolve(MatcherInterface::class);

        return $matcher->test($this->ast, $input, $this->flags);
    }

    /**
     * Execute the pattern match and return detailed results.
     *
     * Returns a MatchResult containing the match position, matched text,
     * and all captured groups. This is the primary method for extracting
     * information from matches. Equivalent to JavaScript's RegExp.exec().
     *
     * ```php
     * $result = $pattern->exec('hello world');
     * echo $result->match; // 'hello'
     * echo $result->index; // 0
     * ```
     *
     * @param string $input The string to match against
     *
     * @return MatchResult The detailed match result with captures
     */
    public function exec(string $input): MatchResult
    {
        /** @var MatcherInterface $matcher */
        $matcher = resolve(MatcherInterface::class);

        return $matcher->exec($this->ast, $input, $this->flags);
    }

    /**
     * Find all non-overlapping matches in the input string.
     *
     * Returns an array of all matches found, scanning from left to right.
     * Similar to JavaScript's String.matchAll() or using the g flag with
     * RegExp. Each match includes its position and captured groups.
     *
     * ```php
     * $results = $pattern->matchAll('hello world hello');
     * foreach ($results as $result) {
     *     echo "{$result->match} at {$result->index}\n";
     * }
     * ```
     *
     * @param string $input The string to search
     *
     * @return array<int, MatchResult> Array of all matches with their captures
     */
    public function matchAll(string $input): array
    {
        /** @var MatcherInterface $matcher */
        $matcher = resolve(MatcherInterface::class);

        return $matcher->matchAll($this->ast, $input, $this->flags);
    }

    /**
     * Get the original pattern source string.
     *
     * Returns the raw regex pattern as originally provided to compile(),
     * without delimiters or flags.
     *
     * @return string The original pattern source
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * Get the pattern flags.
     *
     * Returns the flags string (e.g., 'gi' for global and case-insensitive).
     * Common flags: g (global), i (case-insensitive), m (multiline),
     * s (dotall), u (unicode), y (sticky).
     *
     * @return string The pattern flags
     */
    public function flags(): string
    {
        return $this->flags;
    }
}
