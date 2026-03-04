<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Matcher\MatchContext;

describe('MatchContext', function (): void {
    it('creates context with default values', function (): void {
        $context = new MatchContext();

        expect($context->position)->toBe(0)
            ->and($context->captures)->toBe([])
            ->and($context->startIndex)->toBe(0);
    });

    it('creates context with custom values', function (): void {
        $context = new MatchContext(
            position: 5,
            captures: ['match', 'group1', 'group2'],
            startIndex: 3,
        );

        expect($context->position)->toBe(5)
            ->and($context->captures)->toBe(['match', 'group1', 'group2'])
            ->and($context->startIndex)->toBe(3);
    });

    it('sets capture group value', function (): void {
        $context = new MatchContext();

        $context->setCapture(0, 'full match');
        $context->setCapture(1, 'group 1');
        $context->setCapture(2, 'group 2');

        expect($context->captures)->toBe([
            0 => 'full match',
            1 => 'group 1',
            2 => 'group 2',
        ]);
    });

    it('sets capture group to null', function (): void {
        $context = new MatchContext();

        $context->setCapture(0, 'match');
        $context->setCapture(1, null);

        expect($context->getCapture(0))->toBe('match')
            ->and($context->getCapture(1))->toBeNull();
    });

    it('gets capture group value', function (): void {
        $context = new MatchContext(
            captures: [0 => 'match', 1 => 'first', 2 => 'second'],
        );

        expect($context->getCapture(0))->toBe('match')
            ->and($context->getCapture(1))->toBe('first')
            ->and($context->getCapture(2))->toBe('second');
    });

    it('returns null for non-existent capture group', function (): void {
        $context = new MatchContext();

        expect($context->getCapture(0))->toBeNull()
            ->and($context->getCapture(1))->toBeNull()
            ->and($context->getCapture(999))->toBeNull();
    });

    it('clones context with identical state', function (): void {
        $original = new MatchContext(
            position: 10,
            captures: ['match', 'group1'],
            startIndex: 5,
        );

        $clone = $original->clone();

        expect($clone)->toBeInstanceOf(MatchContext::class)
            ->and($clone->position)->toBe(10)
            ->and($clone->captures)->toBe(['match', 'group1'])
            ->and($clone->startIndex)->toBe(5)
            ->and($clone)->not->toBe($original);
    });

    it('clones context independently', function (): void {
        $original = new MatchContext(position: 5);
        $clone = $original->clone();

        // Modify original
        $original->position = 10;
        $original->setCapture(0, 'new value');

        // Clone should remain unchanged
        expect($clone->position)->toBe(5)
            ->and($clone->getCapture(0))->toBeNull();
    });

    it('resets context to default state', function (): void {
        $context = new MatchContext(
            position: 10,
            captures: ['match', 'group1', 'group2'],
            startIndex: 5,
        );

        $context->reset();

        expect($context->position)->toBe(0)
            ->and($context->captures)->toBe([])
            ->and($context->startIndex)->toBe(0);
    });

    it('resets context to specified position', function (): void {
        $context = new MatchContext(
            position: 10,
            captures: ['match', 'group1'],
            startIndex: 5,
        );

        $context->reset(7);

        expect($context->position)->toBe(7)
            ->and($context->captures)->toBe([])
            ->and($context->startIndex)->toBe(7);
    });

    it('modifies position directly', function (): void {
        $context = new MatchContext(position: 0);

        $context->position = 5;

        expect($context->position)->toBe(5);

        $context->position += 3;
        expect($context->position)->toBe(8);
    });

    it('modifies startIndex directly', function (): void {
        $context = new MatchContext(startIndex: 0);

        $context->startIndex = 10;

        expect($context->startIndex)->toBe(10);
    });

    it('overwrites existing capture values', function (): void {
        $context = new MatchContext();

        $context->setCapture(1, 'first value');

        expect($context->getCapture(1))->toBe('first value');

        $context->setCapture(1, 'second value');
        expect($context->getCapture(1))->toBe('second value');
    });

    it('handles multiple captures with mixed null and string values', function (): void {
        $context = new MatchContext();

        $context->setCapture(0, 'match');
        $context->setCapture(1, null);
        $context->setCapture(2, 'captured');
        $context->setCapture(3, null);
        $context->setCapture(4, 'another');

        expect($context->getCapture(0))->toBe('match')
            ->and($context->getCapture(1))->toBeNull()
            ->and($context->getCapture(2))->toBe('captured')
            ->and($context->getCapture(3))->toBeNull()
            ->and($context->getCapture(4))->toBe('another');
    });
});
