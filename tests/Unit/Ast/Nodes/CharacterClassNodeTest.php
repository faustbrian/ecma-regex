<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\CharacterClassNode;
use Cline\EcmaRegex\Ast\Nodes\CharacterRangeNode;
use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Enums\NodeType;

describe('CharacterClassNode', function (): void {
    it('creates character class node with elements', function (): void {
        $elements = [new LiteralNode('a'), new LiteralNode('b')];
        $node = new CharacterClassNode($elements, false);

        expect($node)->toBeInstanceOf(CharacterClassNode::class)
            ->and($node->type())->toBe(NodeType::CharacterClass)
            ->and($node->elements())->toBe($elements)
            ->and($node->negated())->toBeFalse()
            ->and($node->isNegated())->toBeFalse();
    });

    it('creates negated character class node', function (): void {
        $elements = [new LiteralNode('x'), new LiteralNode('y')];
        $node = new CharacterClassNode($elements, true);

        expect($node)->toBeInstanceOf(CharacterClassNode::class)
            ->and($node->elements())->toBe($elements)
            ->and($node->negated())->toBeTrue()
            ->and($node->isNegated())->toBeTrue();
    });

    it('creates character class with range elements', function (): void {
        $elements = [
            new CharacterRangeNode('a', 'z'),
            new CharacterRangeNode('0', '9'),
        ];
        $node = new CharacterClassNode($elements, false);

        expect($node->elements())->toHaveCount(2)
            ->and($node->elements()[0])->toBeInstanceOf(CharacterRangeNode::class)
            ->and($node->elements()[1])->toBeInstanceOf(CharacterRangeNode::class)
            ->and($node->negated())->toBeFalse()
            ->and($node->isNegated())->toBeFalse();
    });

    it('converts to array representation', function (): void {
        $elements = [new LiteralNode('a'), new LiteralNode('b')];
        $node = new CharacterClassNode($elements, false);
        $array = $node->toArray();

        expect($array)->toHaveKey('type')
            ->and($array)->toHaveKey('negated')
            ->and($array)->toHaveKey('elements')
            ->and($array['type'])->toBe('character_class')
            ->and($array['negated'])->toBeFalse()
            ->and($array['elements'])->toBeArray()
            ->and($array['elements'])->toHaveCount(2);
    });

    it('converts negated character class to array', function (): void {
        $elements = [new LiteralNode('x')];
        $node = new CharacterClassNode($elements, true);
        $array = $node->toArray();

        expect($array['negated'])->toBeTrue();
    });

    it('has correct type value', function (): void {
        $node = new CharacterClassNode([], false);

        expect($node->typeValue())->toBe('character_class');
    });
});
