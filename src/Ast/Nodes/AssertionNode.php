<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Ast\Nodes;

use Cline\EcmaRegex\Ast\AbstractNode;
use Cline\EcmaRegex\Contracts\NodeInterface;
use Cline\EcmaRegex\Enums\NodeType;

/**
 * Represents lookahead and lookbehind assertions in regex patterns.
 *
 * Assertions test whether a pattern matches at a specific position without
 * consuming characters. Lookaheads check what follows the current position,
 * while lookbehinds check what precedes it. Both can be positive (must match)
 * or negative (must not match).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AssertionNode extends AbstractNode
{
    /**
     * Create a new assertion node for zero-width pattern matching.
     *
     * @param NodeInterface $child    The pattern to test at the assertion position.
     *                                This pattern is evaluated but does not advance
     *                                the match cursor if the assertion succeeds.
     * @param string        $kind     The assertion type identifier such as "lookahead",
     *                                "lookbehind", "negative_lookahead", or "negative_lookbehind".
     *                                Determines the direction and match requirement of the assertion.
     * @param bool          $positive Whether this is a positive assertion (pattern must match)
     *                                or negative assertion (pattern must not match). Defaults to true.
     */
    public function __construct(
        private readonly NodeInterface $child,
        private readonly string $kind,
        private readonly bool $positive = true,
    ) {
        parent::__construct(NodeType::Assertion);
    }

    /**
     * Get the child pattern node being tested.
     *
     * @return NodeInterface The pattern evaluated at the assertion position
     */
    public function child(): NodeInterface
    {
        return $this->child;
    }

    /**
     * Get the assertion kind identifier.
     *
     * @return string The assertion type (e.g., "lookahead", "negative_lookahead")
     */
    public function kind(): string
    {
        return $this->kind;
    }

    /**
     * Get the assertion type (alias for backward compatibility).
     *
     * @return string The assertion kind identifier
     */
    public function assertionType(): string
    {
        return $this->kind;
    }

    /**
     * Check if this is a positive assertion.
     *
     * @return bool True if the pattern must match, false if it must not match
     */
    public function isPositive(): bool
    {
        return $this->positive;
    }

    /**
     * Convert node to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->typeValue(),
            'kind' => $this->kind,
            'positive' => $this->positive,
            'child' => $this->child->toArray(),
        ];
    }
}
