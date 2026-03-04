<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Exceptions\EcmaRegexException;
use Cline\EcmaRegex\Exceptions\UnexpectedTokenInCharacterClassException;
use Cline\EcmaRegex\Exceptions\UnsupportedAssertionKindException;
use Cline\EcmaRegex\Exceptions\UnsupportedNodeTypeException;

describe('UnexpectedTokenInCharacterClassException', function (): void {
    it('creates exception with position', function (): void {
        $exception = UnexpectedTokenInCharacterClassException::atPosition(5);

        expect($exception)->toBeInstanceOf(UnexpectedTokenInCharacterClassException::class)
            ->and($exception)->toBeInstanceOf(RuntimeException::class)
            ->and($exception)->toBeInstanceOf(EcmaRegexException::class)
            ->and($exception->getMessage())->toBe('Unexpected token in character class at position 5');
    });

    it('creates exception with different positions', function (): void {
        $exception1 = UnexpectedTokenInCharacterClassException::atPosition(0);
        $exception2 = UnexpectedTokenInCharacterClassException::atPosition(42);
        $exception3 = UnexpectedTokenInCharacterClassException::atPosition(1_000);

        expect($exception1->getMessage())->toBe('Unexpected token in character class at position 0')
            ->and($exception2->getMessage())->toBe('Unexpected token in character class at position 42')
            ->and($exception3->getMessage())->toBe('Unexpected token in character class at position 1000');
    });
});

describe('UnsupportedAssertionKindException', function (): void {
    it('creates exception with assertion kind', function (): void {
        $exception = UnsupportedAssertionKindException::forKind('unknown');

        expect($exception)->toBeInstanceOf(UnsupportedAssertionKindException::class)
            ->and($exception)->toBeInstanceOf(RuntimeException::class)
            ->and($exception)->toBeInstanceOf(EcmaRegexException::class)
            ->and($exception->getMessage())->toBe('Unsupported assertion kind: unknown');
    });

    it('creates exception with different assertion kinds', function (): void {
        $exception1 = UnsupportedAssertionKindException::forKind('invalid');
        $exception2 = UnsupportedAssertionKindException::forKind('future_feature');
        $exception3 = UnsupportedAssertionKindException::forKind('xyz');

        expect($exception1->getMessage())->toBe('Unsupported assertion kind: invalid')
            ->and($exception2->getMessage())->toBe('Unsupported assertion kind: future_feature')
            ->and($exception3->getMessage())->toBe('Unsupported assertion kind: xyz');
    });
});

describe('UnsupportedNodeTypeException', function (): void {
    it('creates exception with node type', function (): void {
        $exception = UnsupportedNodeTypeException::forType('unknown_node');

        expect($exception)->toBeInstanceOf(UnsupportedNodeTypeException::class)
            ->and($exception)->toBeInstanceOf(RuntimeException::class)
            ->and($exception)->toBeInstanceOf(EcmaRegexException::class)
            ->and($exception->getMessage())->toBe('Unsupported node type: unknown_node');
    });

    it('creates exception with different node types', function (): void {
        $exception1 = UnsupportedNodeTypeException::forType('invalid');
        $exception2 = UnsupportedNodeTypeException::forType('future_node');
        $exception3 = UnsupportedNodeTypeException::forType('xyz');

        expect($exception1->getMessage())->toBe('Unsupported node type: invalid')
            ->and($exception2->getMessage())->toBe('Unsupported node type: future_node')
            ->and($exception3->getMessage())->toBe('Unsupported node type: xyz');
    });
});
