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

use function array_map;

/**
 * Represents sequential pattern matching (concatenation).
 *
 * Concatenation is the implicit operation when regex patterns appear adjacent
 * to each other. For example, in the pattern `abc`, this node contains three
 * children representing 'a', 'b', and 'c' that must match in sequence. This is
 * the default composition mechanism for building complex patterns.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ConcatenationNode extends AbstractNode
{
    /**
     * Create a new concatenation node for sequential matching.
     *
     * @param array<int, NodeInterface> $children Array of AST nodes representing patterns
     *                                            that must match in left-to-right order.
     *                                            Each child pattern is attempted in sequence,
     *                                            and all must succeed for the concatenation
     *                                            to match.
     */
    public function __construct(
        private readonly array $children,
    ) {
        parent::__construct(NodeType::Concatenation);
    }

    /**
     * Get the array of child pattern nodes.
     *
     * @return array<int, NodeInterface> The sequential patterns in match order
     */
    public function children(): array
    {
        return $this->children;
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
            'children' => array_map(
                fn (NodeInterface $node): array => $node->toArray(),
                $this->children,
            ),
        ];
    }
}
