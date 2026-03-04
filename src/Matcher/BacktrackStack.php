<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Matcher;

use Cline\EcmaRegex\Contracts\NodeInterface;

use function array_pop;
use function count;

/**
 * Manages backtracking points during regex matching operations.
 *
 * Implements a stack-based backtracking mechanism essential for regex matching.
 * When the matcher encounters choice points (alternations, quantifiers, etc.),
 * it saves backtrack points to enable exploring alternative paths if the current
 * match attempt fails. This enables the matcher to implement non-deterministic
 * finite automaton (NFA) semantics as required by ECMA-262.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class BacktrackStack
{
    /**
     * The stack of backtracking points.
     *
     * @var array<int, BacktrackPoint>
     */
    private array $stack = [];

    /**
     * Push a backtracking point onto the stack.
     *
     * Saves the current matching state as a backtrack point that can be restored
     * later if the current match path fails. The context is cloned to preserve
     * the exact state at this decision point.
     *
     * @param NodeInterface            $node            The AST node to retry when backtracking to this point
     * @param MatchContext             $context         The current match context (will be cloned for preservation)
     * @param int                      $quantifierCount Current iteration count for quantified expressions
     * @param array<int, MatchContext> $alternatives    Alternative match contexts for choice points
     */
    public function push(NodeInterface $node, MatchContext $context, int $quantifierCount = 0, array $alternatives = []): void
    {
        $this->stack[] = new BacktrackPoint(
            node: $node,
            context: $context->clone(),
            quantifierCount: $quantifierCount,
            alternatives: $alternatives,
        );
    }

    /**
     * Pop a backtracking point from the stack.
     *
     * Removes and returns the most recently pushed backtrack point, allowing
     * the matcher to restore a previous state and try an alternative path.
     * Returns null if the stack is empty (no more alternatives to try).
     *
     * @return null|BacktrackPoint The backtrack point to restore, or null if stack is empty
     */
    public function pop(): ?BacktrackPoint
    {
        if ($this->stack === []) {
            return null;
        }

        return array_pop($this->stack);
    }

    /**
     * Check if the stack is empty.
     *
     * An empty stack indicates no remaining backtrack points, meaning the
     * matcher has exhausted all possible alternatives and the match has failed.
     *
     * @return bool True if the stack has no backtrack points, false otherwise
     */
    public function isEmpty(): bool
    {
        return $this->stack === [];
    }

    /**
     * Clear all backtracking points from the stack.
     *
     * Resets the stack to an empty state, typically used when starting a fresh
     * match attempt or when a complete match has been found and backtracking
     * is no longer needed.
     */
    public function clear(): void
    {
        $this->stack = [];
    }

    /**
     * Get the number of backtracking points currently on the stack.
     *
     * Useful for debugging and monitoring the depth of backtracking during
     * complex pattern matching operations.
     *
     * @return int The count of backtrack points on the stack
     */
    public function count(): int
    {
        return count($this->stack);
    }
}

/**
 * Represents a single backtracking point in the matching process.
 *
 * Captures a snapshot of the matching state at a decision point where multiple
 * paths are possible. This immutable value object preserves the AST node to retry,
 * the match context state, and any additional data needed to explore alternatives.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 * @psalm-immutable
 */
final readonly class BacktrackPoint
{
    /**
     * Create a new backtracking point.
     *
     * @param NodeInterface            $node            The AST node to retry when backtracking to this point
     * @param MatchContext             $context         The saved match context state at this decision point
     * @param int                      $quantifierCount The iteration count for quantified expressions at this point
     * @param array<int, MatchContext> $alternatives    Alternative match contexts for choice points (e.g., alternations)
     */
    public function __construct(
        public NodeInterface $node,
        public MatchContext $context,
        public int $quantifierCount = 0,
        public array $alternatives = [],
    ) {}
}
