<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Ast;

use Cline\EcmaRegex\Contracts\NodeInterface;
use Cline\EcmaRegex\Enums\NodeType;

/**
 * Base class for all AST nodes in the ECMAScript regex parser.
 *
 * Provides common functionality for representing nodes in the Abstract Syntax Tree,
 * including type identification and serialization. All concrete node implementations
 * must extend this class and implement the toArray() method for tree representation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractNode implements NodeInterface
{
    /**
     * Create a new AST node instance.
     *
     * @param NodeType $nodeType The type of this node in the AST, used to identify
     *                           the node's role and structure during parsing and traversal.
     *                           Each concrete node class sets its specific type during
     *                           instantiation.
     */
    public function __construct(
        protected readonly NodeType $nodeType,
    ) {}

    /**
     * Get the node type as an enum instance.
     *
     * @return NodeType The enum representing this node's type
     */
    public function type(): NodeType
    {
        return $this->nodeType;
    }

    /**
     * Get the node type as a string value.
     *
     * @return string The string representation of the node type
     */
    public function typeValue(): string
    {
        return $this->nodeType->value;
    }

    /**
     * Convert node to array representation.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
