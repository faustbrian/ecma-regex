<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\AssertionNode;
use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Enums\NodeType;

describe('AssertionNode', function (): void {
    it('creates assertion node with lookahead', function (): void {
        $child = new LiteralNode('a');
        $node = new AssertionNode($child, 'lookahead', true);

        expect($node)->toBeInstanceOf(AssertionNode::class)
            ->and($node->type())->toBe(NodeType::Assertion)
            ->and($node->child())->toBe($child)
            ->and($node->kind())->toBe('lookahead')
            ->and($node->assertionType())->toBe('lookahead')
            ->and($node->isPositive())->toBeTrue();
    });

    it('creates assertion node with negative lookahead', function (): void {
        $child = new LiteralNode('b');
        $node = new AssertionNode($child, 'negative_lookahead', false);

        expect($node)->toBeInstanceOf(AssertionNode::class)
            ->and($node->child())->toBe($child)
            ->and($node->kind())->toBe('negative_lookahead')
            ->and($node->assertionType())->toBe('negative_lookahead')
            ->and($node->isPositive())->toBeFalse();
    });

    it('creates assertion node with lookbehind', function (): void {
        $child = new LiteralNode('c');
        $node = new AssertionNode($child, 'lookbehind', true);

        expect($node)->toBeInstanceOf(AssertionNode::class)
            ->and($node->child())->toBe($child)
            ->and($node->kind())->toBe('lookbehind')
            ->and($node->assertionType())->toBe('lookbehind')
            ->and($node->isPositive())->toBeTrue();
    });

    it('creates assertion node with negative lookbehind', function (): void {
        $child = new LiteralNode('d');
        $node = new AssertionNode($child, 'negative_lookbehind', false);

        expect($node)->toBeInstanceOf(AssertionNode::class)
            ->and($node->child())->toBe($child)
            ->and($node->kind())->toBe('negative_lookbehind')
            ->and($node->assertionType())->toBe('negative_lookbehind')
            ->and($node->isPositive())->toBeFalse();
    });

    it('converts to array representation', function (): void {
        $child = new LiteralNode('x');
        $node = new AssertionNode($child, 'lookahead', true);
        $array = $node->toArray();

        expect($array)->toHaveKey('type')
            ->and($array)->toHaveKey('kind')
            ->and($array)->toHaveKey('positive')
            ->and($array)->toHaveKey('child')
            ->and($array['type'])->toBe('assertion')
            ->and($array['kind'])->toBe('lookahead')
            ->and($array['positive'])->toBeTrue()
            ->and($array['child'])->toBe($child->toArray());
    });

    it('has correct type value', function (): void {
        $child = new LiteralNode('y');
        $node = new AssertionNode($child, 'lookahead');

        expect($node->typeValue())->toBe('assertion');
    });
});
