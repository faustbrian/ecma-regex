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
 * Represents the alternation operator (|) in regex patterns.
 *
 * Alternation allows matching one of several alternative patterns, similar to
 * a logical OR operation. For example, in the pattern `cat|dog`, this node
 * contains two alternatives: "cat" and "dog", matching either string.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AlternationNode extends AbstractNode
{
    /**
     * Create a new alternation node with multiple alternatives.
     *
     * @param array<int, NodeInterface> $alternatives Array of AST nodes representing
     *                                                the alternative patterns to match.
     *                                                Each alternative is tried in sequence
     *                                                from left to right during matching.
     *                                                Must contain at least two alternatives.
     */
    public function __construct(
        private readonly array $alternatives,
    ) {
        parent::__construct(NodeType::Alternation);
    }

    /**
     * Get the array of alternative pattern nodes.
     *
     * @return array<int, NodeInterface> The alternative patterns in left-to-right order
     */
    public function alternatives(): array
    {
        return $this->alternatives;
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
            'alternatives' => array_map(
                fn (NodeInterface $node): array => $node->toArray(),
                $this->alternatives,
            ),
        ];
    }
}
