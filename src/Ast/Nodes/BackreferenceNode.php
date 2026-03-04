<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Ast\Nodes;

use Cline\EcmaRegex\Ast\AbstractNode;
use Cline\EcmaRegex\Enums\NodeType;

/**
 * Represents backreferences to previously captured groups.
 *
 * Backreferences match the same text that was previously matched by a capturing
 * group, rather than matching the pattern itself. For example, in the pattern
 * `(cat)\1`, the backreference \1 matches the literal text "cat" that was
 * captured by the first group, not the pattern (cat).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class BackreferenceNode extends AbstractNode
{
    /**
     * Create a new backreference node.
     *
     * @param int $groupNumber The one-based index of the capturing group to reference.
     *                         For example, \1 references the first capturing group,
     *                         \2 references the second, and so on. The referenced group
     *                         must exist and appear earlier in the pattern.
     */
    public function __construct(
        private readonly int $groupNumber,
    ) {
        parent::__construct(NodeType::Backreference);
    }

    /**
     * Get the referenced capturing group number.
     *
     * @return int The one-based group index being referenced
     */
    public function groupNumber(): int
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
        return [
            'type' => $this->typeValue(),
            'groupNumber' => $this->groupNumber,
        ];
    }
}
