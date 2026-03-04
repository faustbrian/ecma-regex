<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\BackreferenceNode;
use Cline\EcmaRegex\Enums\NodeType;

describe('BackreferenceNode', function (): void {
    it('creates backreference node with group number', function (): void {
        $node = new BackreferenceNode(1);

        expect($node)->toBeInstanceOf(BackreferenceNode::class)
            ->and($node->type())->toBe(NodeType::Backreference)
            ->and($node->groupNumber())->toBe(1);
    });

    it('creates backreference node with different group numbers', function (): void {
        $node1 = new BackreferenceNode(1);
        $node2 = new BackreferenceNode(5);
        $node3 = new BackreferenceNode(10);

        expect($node1->groupNumber())->toBe(1)
            ->and($node2->groupNumber())->toBe(5)
            ->and($node3->groupNumber())->toBe(10);
    });

    it('converts to array representation', function (): void {
        $node = new BackreferenceNode(3);
        $array = $node->toArray();

        expect($array)->toBe([
            'type' => 'backreference',
            'groupNumber' => 3,
        ]);
    });

    it('has correct type value', function (): void {
        $node = new BackreferenceNode(1);

        expect($node->typeValue())->toBe('backreference');
    });
});
