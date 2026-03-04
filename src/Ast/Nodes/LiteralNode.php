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
 * Represents a literal character or string in a regex pattern.
 *
 * Literal nodes represent plain text characters that should be matched exactly,
 * without any special regex meaning. For example, in the pattern "hello", each
 * character would be represented as a separate LiteralNode.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LiteralNode extends AbstractNode
{
    /**
     * Create a new literal node instance.
     *
     * @param string $value The literal character or string value to match exactly.
     *                      This value is treated as plain text without special
     *                      regex interpretation. Must not be empty.
     */
    public function __construct(
        private readonly string $value,
    ) {
        parent::__construct(NodeType::Literal);
    }

    /**
     * Get the literal value to be matched.
     *
     * @return string The raw literal character or string that this node represents
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert node to array representation for serialization.
     *
     * @return array<string, mixed> Associative array containing the node type
     *                              and the literal value
     */
    public function toArray(): array
    {
        return [
            'type' => $this->typeValue(),
            'value' => $this->value,
        ];
    }
}
