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
 * Represents character classes in regex patterns.
 *
 * Character classes define a set of characters to match at a single position.
 * A normal class [abc] matches any one character from the set. A negated class
 * [^abc] matches any character NOT in the set. Elements can include individual
 * characters, ranges (a-z), or character class escapes (\d, \w, \s).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CharacterClassNode extends AbstractNode
{
    /**
     * Create a new character class node.
     *
     * @param array<int, NodeInterface> $elements Array of nodes representing the characters,
     *                                            ranges, or character classes that define this
     *                                            set. Each element can be a literal character,
     *                                            a CharacterRangeNode (a-z), or a character
     *                                            class escape node (\d, \w, \s).
     * @param bool                      $negated  Whether this is a negated character class [^...].
     *                                            When true, matches any character NOT in the set.
     *                                            Defaults to false for normal character classes.
     */
    public function __construct(
        private readonly array $elements,
        private readonly bool $negated = false,
    ) {
        parent::__construct(NodeType::CharacterClass);
    }

    /**
     * Get the array of character class element nodes.
     *
     * @return array<int, NodeInterface> The characters, ranges, or escapes in this class
     */
    public function elements(): array
    {
        return $this->elements;
    }

    /**
     * Check if this is a negated character class.
     *
     * @return bool True if this matches characters NOT in the set
     */
    public function negated(): bool
    {
        return $this->negated;
    }

    /**
     * Check if the character class is negated (alias for backward compatibility).
     *
     * @return bool True if this is a negated character class [^...]
     */
    public function isNegated(): bool
    {
        return $this->negated;
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
            'negated' => $this->negated,
            'elements' => array_map(
                fn (NodeInterface $node): array => $node->toArray(),
                $this->elements,
            ),
        ];
    }
}
