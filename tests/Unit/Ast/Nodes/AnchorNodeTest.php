<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\AnchorNode;
use Cline\EcmaRegex\Enums\NodeType;

describe('AnchorNode', function (): void {
    it('creates anchor node with start kind', function (): void {
        $node = new AnchorNode('start');

        expect($node)->toBeInstanceOf(AnchorNode::class)
            ->and($node->type())->toBe(NodeType::Anchor)
            ->and($node->kind())->toBe('start')
            ->and($node->anchorType())->toBe('start');
    });

    it('creates anchor node with end kind', function (): void {
        $node = new AnchorNode('end');

        expect($node)->toBeInstanceOf(AnchorNode::class)
            ->and($node->kind())->toBe('end')
            ->and($node->anchorType())->toBe('end');
    });

    it('converts to array representation', function (): void {
        $node = new AnchorNode('start');
        $array = $node->toArray();

        expect($array)->toBe([
            'type' => 'anchor',
            'kind' => 'start',
        ]);
    });

    it('has correct type value', function (): void {
        $node = new AnchorNode('end');

        expect($node->typeValue())->toBe('anchor');
    });
});
