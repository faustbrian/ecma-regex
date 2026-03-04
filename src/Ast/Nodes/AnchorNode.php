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
 * Represents position anchors in regex patterns.
 *
 * Anchors match positions in the input string rather than actual characters.
 * The two primary anchors are ^ (start of line) and $ (end of line), which
 * are used to constrain matches to specific positions within the text.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AnchorNode extends AbstractNode
{
    /**
     * Create a new anchor node for position matching.
     *
     * @param string $kind The anchor type identifier. Typically "start" for ^
     *                     (beginning of line) or "end" for $ (end of line).
     *                     Used to determine which position constraint to apply
     *                     during pattern matching.
     */
    public function __construct(
        private readonly string $kind,
    ) {
        parent::__construct(NodeType::Anchor);
    }

    /**
     * Get the anchor kind identifier.
     *
     * @return string The anchor type ("start" or "end")
     */
    public function kind(): string
    {
        return $this->kind;
    }

    /**
     * Get the anchor type (alias for backward compatibility).
     *
     * @return string The anchor type identifier
     */
    public function anchorType(): string
    {
        return $this->kind;
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
        ];
    }
}
