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
 * Represents a quantifier in a regex pattern that controls repetition.
 *
 * Quantifiers specify how many times the preceding element should be matched.
 * Common quantifiers include * (0 or more), + (1 or more), ? (0 or 1),
 * and {n,m} (between n and m times). Quantifiers can be greedy or lazy,
 * affecting how the regex engine consumes characters during matching.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class QuantifierNode extends AbstractNode
{
    /**
     * Create a new quantifier node instance.
     *
     * @param NodeInterface $child  The child node that this quantifier applies to.
     *                              This is the pattern element being repeated.
     * @param int           $min    The minimum number of repetitions required. Must be
     *                              non-negative. A value of 0 means the element is optional.
     * @param null|int      $max    The maximum number of repetitions allowed. A null value
     *                              indicates unbounded repetition (e.g., * or + quantifiers).
     *                              When set, must be greater than or equal to min.
     * @param bool          $greedy Whether this quantifier uses greedy matching. Greedy
     *                              quantifiers match as much text as possible, while lazy
     *                              quantifiers match as little as possible. Defaults to true.
     */
    public function __construct(
        private readonly NodeInterface $child,
        private readonly int $min,
        private readonly ?int $max = null,
        private readonly bool $greedy = true,
    ) {
        parent::__construct(NodeType::Quantifier);
    }

    /**
     * Get the child node being quantified.
     *
     * @return NodeInterface The pattern element that this quantifier repeats
     */
    public function child(): NodeInterface
    {
        return $this->child;
    }

    /**
     * Get the minimum number of required repetitions.
     *
     * @return int The minimum repetition count (0 or greater)
     */
    public function min(): int
    {
        return $this->min;
    }

    /**
     * Get the maximum number of allowed repetitions.
     *
     * @return null|int The maximum repetition count, or null for unlimited repetitions
     */
    public function max(): ?int
    {
        return $this->max;
    }

    /**
     * Check if the quantifier uses greedy matching.
     *
     * @return bool True for greedy matching (match as much as possible), false
     *              for lazy matching (match as little as possible)
     */
    public function greedy(): bool
    {
        return $this->greedy;
    }

    /**
     * Convert node to array representation for serialization.
     *
     * @return array<string, mixed> Associative array containing the node type,
     *                              repetition bounds, greedy flag, and serialized child node
     */
    public function toArray(): array
    {
        return [
            'type' => $this->typeValue(),
            'min' => $this->min,
            'max' => $this->max,
            'greedy' => $this->greedy,
            'child' => $this->child->toArray(),
        ];
    }
}
