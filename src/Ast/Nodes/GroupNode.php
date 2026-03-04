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
 * Represents grouping constructs in regex patterns.
 *
 * Groups serve multiple purposes: capturing matched text for later reference,
 * controlling operator precedence, and applying quantifiers to sub-patterns.
 * Capturing groups (...) store their matches for backreferences and extraction,
 * while non-capturing groups (?:...) only affect precedence without storing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class GroupNode extends AbstractNode
{
    /**
     * Create a new group node for pattern grouping and capturing.
     *
     * @param NodeInterface $child       The pattern contained within the group parentheses.
     *                                   This sub-pattern is evaluated as a unit and can be
     *                                   quantified, alternated, or captured as needed.
     * @param bool          $capturing   Whether this group captures its match for later reference.
     *                                   Capturing groups (...) store matches for backreferences,
     *                                   while non-capturing groups (?:...) do not. Defaults to true.
     * @param null|int      $groupNumber The one-based index assigned to this capturing group.
     *                                   Used for backreferences (\1, \2) and match extraction.
     *                                   Null for non-capturing groups.
     */
    public function __construct(
        private readonly NodeInterface $child,
        private readonly bool $capturing = true,
        private readonly ?int $groupNumber = null,
    ) {
        parent::__construct(NodeType::Group);
    }

    /**
     * Get the child pattern node contained in this group.
     *
     * @return NodeInterface The grouped sub-pattern
     */
    public function child(): NodeInterface
    {
        return $this->child;
    }

    /**
     * Check if this group captures its match.
     *
     * @return bool True for capturing groups (...), false for non-capturing (?:...)
     */
    public function capturing(): bool
    {
        return $this->capturing;
    }

    /**
     * Check if the group is capturing (alias for backward compatibility).
     *
     * @return bool True if this group stores its match for later reference
     */
    public function isCapturing(): bool
    {
        return $this->capturing;
    }

    /**
     * Get the capturing group number if applicable.
     *
     * @return null|int The one-based group index, or null for non-capturing groups
     */
    public function groupNumber(): ?int
    {
        return $this->groupNumber;
    }

    /**
     * Get the group index (alias for backward compatibility).
     *
     * @return null|int The capturing group number
     */
    public function groupIndex(): ?int
    {
        return $this->groupNumber;
    }

    /**
     * Convert node to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'type' => $this->typeValue(),
            'capturing' => $this->capturing,
            'child' => $this->child->toArray(),
        ];

        if ($this->groupNumber !== null) {
            $result['groupNumber'] = $this->groupNumber;
        }

        return $result;
    }
}
