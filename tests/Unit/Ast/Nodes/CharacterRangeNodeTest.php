<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\CharacterRangeNode;
use Cline\EcmaRegex\Enums\NodeType;

describe('CharacterRangeNode', function (): void {
    it('creates character range node with start and end', function (): void {
        $node = new CharacterRangeNode('a', 'z');

        expect($node)->toBeInstanceOf(CharacterRangeNode::class)
            ->and($node->type())->toBe(NodeType::CharacterRange)
            ->and($node->start())->toBe('a')
            ->and($node->end())->toBe('z');
    });

    it('creates numeric character range', function (): void {
        $node = new CharacterRangeNode('0', '9');

        expect($node->start())->toBe('0')
            ->and($node->end())->toBe('9');
    });

    it('creates uppercase character range', function (): void {
        $node = new CharacterRangeNode('A', 'Z');

        expect($node->start())->toBe('A')
            ->and($node->end())->toBe('Z');
    });

    it('converts to array representation', function (): void {
        $node = new CharacterRangeNode('a', 'z');
        $array = $node->toArray();

        expect($array)->toBe([
            'type' => 'character_range',
            'start' => 'a',
            'end' => 'z',
        ]);
    });

    it('has correct type value', function (): void {
        $node = new CharacterRangeNode('a', 'z');

        expect($node->typeValue())->toBe('character_range');
    });
});
