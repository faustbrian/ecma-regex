<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Matcher\BacktrackPoint;
use Cline\EcmaRegex\Matcher\BacktrackStack;
use Cline\EcmaRegex\Matcher\MatchContext;

describe('BacktrackStack', function (): void {
    it('initializes as empty stack', function (): void {
        $stack = new BacktrackStack();

        expect($stack->isEmpty())->toBeTrue()
            ->and($stack->count())->toBe(0)
            ->and($stack->pop())->toBeNull();
    });

    it('pushes backtrack point onto stack', function (): void {
        $stack = new BacktrackStack();
        $node = new LiteralNode('test');
        $context = new MatchContext();

        $stack->push($node, $context);

        expect($stack->isEmpty())->toBeFalse()
            ->and($stack->count())->toBe(1);
    });

    it('pushes multiple backtrack points', function (): void {
        $stack = new BacktrackStack();
        $node1 = new LiteralNode('a');
        $node2 = new LiteralNode('b');
        $node3 = new LiteralNode('c');
        $context = new MatchContext();

        $stack->push($node1, $context);
        $stack->push($node2, $context);
        $stack->push($node3, $context);

        expect($stack->count())->toBe(3)
            ->and($stack->isEmpty())->toBeFalse();
    });

    it('pops backtrack point from stack in LIFO order', function (): void {
        $stack = new BacktrackStack();
        $node1 = new LiteralNode('a');
        $node2 = new LiteralNode('b');
        $node3 = new LiteralNode('c');
        $context = new MatchContext();

        $stack->push($node1, $context);
        $stack->push($node2, $context);
        $stack->push($node3, $context);

        $point3 = $stack->pop();
        expect($point3)->toBeInstanceOf(BacktrackPoint::class)
            ->and($point3->node)->toBe($node3)
            ->and($stack->count())->toBe(2);

        $point2 = $stack->pop();
        expect($point2->node)->toBe($node2)
            ->and($stack->count())->toBe(1);

        $point1 = $stack->pop();
        expect($point1->node)->toBe($node1)
            ->and($stack->count())->toBe(0);

        expect($stack->pop())->toBeNull()
            ->and($stack->isEmpty())->toBeTrue();
    });

    it('clears all backtrack points', function (): void {
        $stack = new BacktrackStack();
        $node = new LiteralNode('test');
        $context = new MatchContext();

        $stack->push($node, $context);
        $stack->push($node, $context);
        $stack->push($node, $context);

        expect($stack->count())->toBe(3);

        $stack->clear();

        expect($stack->isEmpty())->toBeTrue()
            ->and($stack->count())->toBe(0)
            ->and($stack->pop())->toBeNull();
    });

    it('pushes backtrack point with quantifier count', function (): void {
        $stack = new BacktrackStack();
        $node = new LiteralNode('test');
        $context = new MatchContext();

        $stack->push($node, $context, quantifierCount: 5);

        $point = $stack->pop();
        expect($point->quantifierCount)->toBe(5);
    });

    it('pushes backtrack point with alternatives', function (): void {
        $stack = new BacktrackStack();
        $node = new LiteralNode('test');
        $context = new MatchContext();
        $alt1 = new MatchContext(position: 1);
        $alt2 = new MatchContext(position: 2);
        $alternatives = [$alt1, $alt2];

        $stack->push($node, $context, alternatives: $alternatives);

        $point = $stack->pop();
        expect($point->alternatives)->toBe($alternatives)
            ->and($point->alternatives)->toHaveCount(2);
    });

    it('clones context when pushing', function (): void {
        $stack = new BacktrackStack();
        $node = new LiteralNode('test');
        $context = new MatchContext(position: 5);

        $stack->push($node, $context);

        // Modify original context
        $context->position = 10;

        // Popped context should have original value (5), not modified value (10)
        $point = $stack->pop();
        expect($point->context->position)->toBe(5);
    });

    it('returns correct count after mixed operations', function (): void {
        $stack = new BacktrackStack();
        $node = new LiteralNode('test');
        $context = new MatchContext();

        expect($stack->count())->toBe(0);

        $stack->push($node, $context);
        $stack->push($node, $context);

        expect($stack->count())->toBe(2);

        $stack->pop();
        expect($stack->count())->toBe(1);

        $stack->push($node, $context);
        $stack->push($node, $context);

        expect($stack->count())->toBe(3);

        $stack->clear();
        expect($stack->count())->toBe(0);
    });
});

describe('BacktrackPoint', function (): void {
    it('creates backtrack point with required properties', function (): void {
        $node = new LiteralNode('test');
        $context = new MatchContext(position: 5);

        $point = new BacktrackPoint($node, $context);

        expect($point)->toBeInstanceOf(BacktrackPoint::class)
            ->and($point->node)->toBe($node)
            ->and($point->context)->toBe($context)
            ->and($point->quantifierCount)->toBe(0)
            ->and($point->alternatives)->toBe([]);
    });

    it('creates backtrack point with all properties', function (): void {
        $node = new LiteralNode('test');
        $context = new MatchContext(position: 5);
        $alt1 = new MatchContext(position: 1);
        $alt2 = new MatchContext(position: 2);
        $alternatives = [$alt1, $alt2];

        $point = new BacktrackPoint(
            node: $node,
            context: $context,
            quantifierCount: 3,
            alternatives: $alternatives,
        );

        expect($point->node)->toBe($node)
            ->and($point->context)->toBe($context)
            ->and($point->quantifierCount)->toBe(3)
            ->and($point->alternatives)->toBe($alternatives);
    });

    it('is readonly and immutable', function (): void {
        $node = new LiteralNode('test');
        $context = new MatchContext();
        $point = new BacktrackPoint($node, $context);

        expect(fn (): LiteralNode => $point->node = new LiteralNode('new'))
            ->toThrow(Error::class);
    });
});
