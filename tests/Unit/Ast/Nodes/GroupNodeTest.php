<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\GroupNode;
use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Enums\NodeType;

describe('GroupNode', function (): void {
    it('creates capturing group node', function (): void {
        $child = new LiteralNode('a');
        $node = new GroupNode($child, true, 1);

        expect($node)->toBeInstanceOf(GroupNode::class)
            ->and($node->type())->toBe(NodeType::Group)
            ->and($node->child())->toBe($child)
            ->and($node->capturing())->toBeTrue()
            ->and($node->isCapturing())->toBeTrue()
            ->and($node->groupNumber())->toBe(1)
            ->and($node->groupIndex())->toBe(1);
    });

    it('creates non-capturing group node', function (): void {
        $child = new LiteralNode('b');
        $node = new GroupNode($child, false);

        expect($node)->toBeInstanceOf(GroupNode::class)
            ->and($node->child())->toBe($child)
            ->and($node->capturing())->toBeFalse()
            ->and($node->isCapturing())->toBeFalse()
            ->and($node->groupNumber())->toBeNull()
            ->and($node->groupIndex())->toBeNull();
    });

    it('creates capturing group with different group numbers', function (): void {
        $child1 = new LiteralNode('x');
        $child2 = new LiteralNode('y');
        $child3 = new LiteralNode('z');

        $node1 = new GroupNode($child1, true, 1);
        $node2 = new GroupNode($child2, true, 2);
        $node3 = new GroupNode($child3, true, 5);

        expect($node1->groupNumber())->toBe(1)
            ->and($node1->groupIndex())->toBe(1)
            ->and($node2->groupNumber())->toBe(2)
            ->and($node2->groupIndex())->toBe(2)
            ->and($node3->groupNumber())->toBe(5)
            ->and($node3->groupIndex())->toBe(5);
    });

    it('converts capturing group to array representation', function (): void {
        $child = new LiteralNode('a');
        $node = new GroupNode($child, true, 3);
        $array = $node->toArray();

        expect($array)->toHaveKey('type')
            ->and($array)->toHaveKey('capturing')
            ->and($array)->toHaveKey('child')
            ->and($array)->toHaveKey('groupNumber')
            ->and($array['type'])->toBe('group')
            ->and($array['capturing'])->toBeTrue()
            ->and($array['groupNumber'])->toBe(3)
            ->and($array['child'])->toBe($child->toArray());
    });

    it('converts non-capturing group to array without groupNumber', function (): void {
        $child = new LiteralNode('b');
        $node = new GroupNode($child, false);
        $array = $node->toArray();

        expect($array)->toHaveKey('type')
            ->and($array)->toHaveKey('capturing')
            ->and($array)->toHaveKey('child')
            ->and($array)->not->toHaveKey('groupNumber')
            ->and($array['type'])->toBe('group')
            ->and($array['capturing'])->toBeFalse()
            ->and($array['child'])->toBe($child->toArray());
    });

    it('has correct type value', function (): void {
        $child = new LiteralNode('c');
        $node = new GroupNode($child, true, 1);

        expect($node->typeValue())->toBe('group');
    });
});
