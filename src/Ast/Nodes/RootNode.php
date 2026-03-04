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
 * Represents the root node of the Abstract Syntax Tree for a regex pattern.
 *
 * The root node serves as the entry point to the AST, encapsulating the entire
 * regex pattern structure. Every parsed pattern begins with a RootNode that
 * contains the complete pattern tree as its child. This provides a consistent
 * starting point for AST traversal and manipulation operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RootNode extends AbstractNode
{
    /**
     * Create a new root node instance.
     *
     * @param NodeInterface $child The complete pattern tree representing the entire regex.
     *                             This is the top-level node structure that contains all
     *                             pattern elements, quantifiers, groups, and alternations.
     */
    public function __construct(
        private readonly NodeInterface $child,
    ) {
        parent::__construct(NodeType::Root);
    }

    /**
     * Get the complete pattern tree.
     *
     * @return NodeInterface The root of the pattern tree containing all regex elements
     */
    public function child(): NodeInterface
    {
        return $this->child;
    }

    /**
     * Convert node to array representation for serialization.
     *
     * @return array<string, mixed> Associative array containing the node type
     *                              and the serialized child pattern tree
     */
    public function toArray(): array
    {
        return [
            'type' => $this->typeValue(),
            'child' => $this->child->toArray(),
        ];
    }
}
