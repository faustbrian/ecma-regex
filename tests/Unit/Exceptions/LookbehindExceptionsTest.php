<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Exceptions\EcmaRegexException;
use Cline\EcmaRegex\Exceptions\IncompleteLookbehindConstructException;
use Cline\EcmaRegex\Exceptions\InvalidLookbehindConstructException;

describe('IncompleteLookbehindConstructException', function (): void {
    it('creates exception with position', function (): void {
        $exception = IncompleteLookbehindConstructException::atPosition(5);

        expect($exception)->toBeInstanceOf(IncompleteLookbehindConstructException::class)
            ->and($exception)->toBeInstanceOf(InvalidArgumentException::class)
            ->and($exception)->toBeInstanceOf(EcmaRegexException::class)
            ->and($exception->getMessage())->toBe('Incomplete lookbehind construct "(?<" at position 5');
    });

    it('creates exception with different positions', function (): void {
        $exception1 = IncompleteLookbehindConstructException::atPosition(0);
        $exception2 = IncompleteLookbehindConstructException::atPosition(10);
        $exception3 = IncompleteLookbehindConstructException::atPosition(999);

        expect($exception1->getMessage())->toContain('position 0')
            ->and($exception2->getMessage())->toContain('position 10')
            ->and($exception3->getMessage())->toContain('position 999');
    });
});

describe('InvalidLookbehindConstructException', function (): void {
    it('creates exception with assertion and position', function (): void {
        $exception = InvalidLookbehindConstructException::atPosition('x', 5);

        expect($exception)->toBeInstanceOf(InvalidLookbehindConstructException::class)
            ->and($exception)->toBeInstanceOf(InvalidArgumentException::class)
            ->and($exception)->toBeInstanceOf(EcmaRegexException::class)
            ->and($exception->getMessage())->toBe('Invalid lookbehind construct "(?<x" at position 5');
    });

    it('creates exception with different assertions and positions', function (): void {
        $exception1 = InvalidLookbehindConstructException::atPosition('?', 0);
        $exception2 = InvalidLookbehindConstructException::atPosition('*', 15);
        $exception3 = InvalidLookbehindConstructException::atPosition('+', 100);

        expect($exception1->getMessage())->toBe('Invalid lookbehind construct "(?<?" at position 0')
            ->and($exception2->getMessage())->toBe('Invalid lookbehind construct "(?<*" at position 15')
            ->and($exception3->getMessage())->toBe('Invalid lookbehind construct "(?<+" at position 100');
    });
});
