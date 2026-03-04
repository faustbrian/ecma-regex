<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Contracts;

use Cline\EcmaRegex\Enums\NodeType;

/**
 * Defines the contract for nodes in the regex Abstract Syntax Tree.
 *
 * The AST is a hierarchical representation of a compiled regex pattern, where
 * each node represents a distinct pattern element such as literals, quantifiers,
 * groups, character classes, or alternations. Nodes form a tree structure that
 * captures the pattern's semantic meaning and can be traversed for matching,
 * analysis, or transformation operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface NodeInterface
{
    /**
     * Get the type of this AST node.
     *
     * The node type identifies what kind of regex element this node represents,
     * such as a literal character, quantifier, group, or other pattern construct.
     * This is essential for AST traversal and pattern matching algorithms.
     *
     * @return NodeType The enum value representing this node's type
     */
    public function type(): NodeType;

    /**
     * Convert this node to an array representation.
     *
     * Serializes the node and its properties into a plain PHP array structure.
     * This is useful for debugging, logging, caching compiled patterns, or
     * transmitting AST structures across process boundaries. The array format
     * provides a human-readable view of the node's structure and properties.
     *
     * @return array<string, mixed> Associative array containing the node's type
     *                              and type-specific properties
     */
    public function toArray(): array;
}
