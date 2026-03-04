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
 * Represents the dot wildcard metacharacter in regex patterns.
 *
 * The dot (.) matches any single character except line terminators (newline
 * characters). In dotAll/single-line mode, it matches any character including
 * newlines. This is one of the most commonly used regex metacharacters for
 * matching arbitrary characters.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DotNode extends AbstractNode
{
    /**
     * Create a new dot wildcard node.
     *
     * Represents the . metacharacter which matches any single character
     * (excluding newlines in default mode, including newlines in dotAll mode).
     */
    public function __construct()
    {
        parent::__construct(NodeType::Dot);
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
        ];
    }
}
