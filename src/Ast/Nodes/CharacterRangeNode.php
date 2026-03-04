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
 * Represents character ranges within character classes.
 *
 * Character ranges provide a shorthand for matching consecutive characters
 * based on their Unicode code points. For example, [a-z] matches any lowercase
 * letter by defining a range from 'a' to 'z'. Ranges are only valid within
 * character classes and must have start <= end in the Unicode sequence.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CharacterRangeNode extends AbstractNode
{
    /**
     * Create a new character range node.
     *
     * @param string $start The first character in the range. Must have a Unicode
     *                      code point less than or equal to the end character.
     *                      For example, 'a' in the range [a-z].
     * @param string $end   The last character in the range. Must have a Unicode
     *                      code point greater than or equal to the start character.
     *                      For example, 'z' in the range [a-z].
     */
    public function __construct(
        private readonly string $start,
        private readonly string $end,
    ) {
        parent::__construct(NodeType::CharacterRange);
    }

    /**
     * Get the starting character of the range.
     *
     * @return string The first character in the Unicode sequence
     */
    public function start(): string
    {
        return $this->start;
    }

    /**
     * Get the ending character of the range.
     *
     * @return string The last character in the Unicode sequence
     */
    public function end(): string
    {
        return $this->end;
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
            'start' => $this->start,
            'end' => $this->end,
        ];
    }
}
