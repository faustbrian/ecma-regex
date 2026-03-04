<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Matcher;

/**
 * Tracks the mutable state of a regex match operation.
 *
 * Maintains the current position in the input string, captured groups, and other
 * state information necessary for executing a regex match. This context is passed
 * through the matching process and updated as the matcher progresses through the
 * input. The context can be cloned to support backtracking operations.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class MatchContext
{
    /**
     * Create a new match context.
     *
     * @param int                     $position   Current zero-based position in the input string being matched
     * @param array<int, null|string> $captures   Captured group values indexed by capture group number (0 = full match)
     * @param int                     $startIndex Starting position of the current match attempt in the input string
     */
    public function __construct(
        public int $position = 0,
        public array $captures = [],
        public int $startIndex = 0,
    ) {}

    /**
     * Set a capture group value.
     *
     * Records the captured text for a specific capture group. Index 0 represents
     * the entire match, while indices 1+ represent parenthesized subexpressions
     * in the order they appear in the pattern.
     *
     * @param int         $index Zero for full match, 1+ for capture groups
     * @param null|string $value The captured text, or null if the group didn't match
     */
    public function setCapture(int $index, ?string $value): void
    {
        $this->captures[$index] = $value;
    }

    /**
     * Get a capture group value.
     *
     * Retrieves the captured text for a specific capture group. Returns null
     * if the capture group hasn't been set or if the group didn't participate
     * in the match (e.g., optional groups that didn't match).
     *
     * @param  int         $index Zero for full match, 1+ for capture groups
     * @return null|string The captured text, or null if not captured
     */
    public function getCapture(int $index): ?string
    {
        return $this->captures[$index] ?? null;
    }

    /**
     * Clone the context for backtracking.
     *
     * Creates a deep copy of the current context state, preserving position,
     * captures, and start index. This allows the matcher to save a snapshot
     * before attempting a match path, enabling restoration if the path fails.
     *
     * @return self A new MatchContext instance with identical state
     */
    public function clone(): self
    {
        return new self(
            position: $this->position,
            captures: $this->captures,
            startIndex: $this->startIndex,
        );
    }

    /**
     * Reset the context to initial state for a new match attempt.
     *
     * Clears all captured groups and resets the position to the specified
     * starting point. Used when beginning a fresh match attempt at a different
     * position in the input string.
     *
     * @param int $position The zero-based position to reset to (defaults to 0)
     */
    public function reset(int $position = 0): void
    {
        $this->position = $position;
        $this->captures = [];
        $this->startIndex = $position;
    }
}
