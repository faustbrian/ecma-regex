<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\ValueObjects\MatchResult;

describe('MatchResult', function (): void {
    describe('Successful Match', function (): void {
        it('creates successful match result', function (): void {
            $result = MatchResult::success(
                index: 5,
                match: 'test',
                captures: ['cap1', 'cap2'],
                input: 'hello test world',
            );

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeTrue()
                ->and($result->index)->toBe(5)
                ->and($result->match)->toBe('test')
                ->and($result->captures)->toBe(['cap1', 'cap2'])
                ->and($result->input)->toBe('hello test world');
        });

        it('creates successful match with minimal parameters', function (): void {
            $result = MatchResult::success(
                index: 0,
                match: 'abc',
            );

            expect($result->matched)->toBeTrue()
                ->and($result->index)->toBe(0)
                ->and($result->match)->toBe('abc')
                ->and($result->captures)->toBe([])
                ->and($result->input)->toBeNull();
        });

        it('creates successful match with empty captures', function (): void {
            $result = MatchResult::success(
                index: 0,
                match: 'test',
                captures: [],
            );

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toBeArray()
                ->and($result->captures)->toBeEmpty();
        });

        it('creates successful match with null capture values', function (): void {
            $result = MatchResult::success(
                index: 0,
                match: 'test',
                captures: ['cap1', null, 'cap3'],
            );

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(3)
                ->and($result->captures[0])->toBe('cap1')
                ->and($result->captures[1])->toBeNull()
                ->and($result->captures[2])->toBe('cap3');
        });
    });

    describe('Failed Match', function (): void {
        it('creates failed match result', function (): void {
            $result = MatchResult::failed();

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeFalse()
                ->and($result->index)->toBe(-1)
                ->and($result->match)->toBe('')
                ->and($result->captures)->toBe([])
                ->and($result->input)->toBeNull();
        });

        it('failed result has empty match string', function (): void {
            $result = MatchResult::failed();

            expect($result->match)->toBe('')
                ->and($result->match)->toBeString();
        });

        it('failed result has negative index', function (): void {
            $result = MatchResult::failed();

            expect($result->index)->toBe(-1)
                ->and($result->index)->toBeLessThan(0);
        });
    });

    describe('Immutability', function (): void {
        it('is readonly and cannot be modified', function (): void {
            $result = MatchResult::success(
                index: 0,
                match: 'test',
                captures: ['cap1'],
                input: 'test input',
            );

            // Verify values remain constant across multiple accesses
            expect($result->matched)->toBeTrue();
            expect($result->matched)->toBeTrue();

            expect($result->index)->toBe(0);
            expect($result->index)->toBe(0);

            expect($result->match)->toBe('test');
            expect($result->match)->toBe('test');
        });
    });

    describe('Property Access', function (): void {
        it('allows direct property access for matched', function (): void {
            $success = MatchResult::success(0, 'test');
            $failed = MatchResult::failed();

            expect($success->matched)->toBeTrue()
                ->and($failed->matched)->toBeFalse();
        });

        it('allows direct property access for index', function (): void {
            $result = MatchResult::success(index: 42, match: 'test');

            expect($result->index)->toBe(42)
                ->and($result->index)->toBeInt();
        });

        it('allows direct property access for match', function (): void {
            $result = MatchResult::success(index: 0, match: 'matched text');

            expect($result->match)->toBe('matched text')
                ->and($result->match)->toBeString();
        });

        it('allows direct property access for captures', function (): void {
            $captures = ['group1', 'group2', 'group3'];
            $result = MatchResult::success(index: 0, match: 'test', captures: $captures);

            expect($result->captures)->toBe($captures)
                ->and($result->captures)->toBeArray();
        });

        it('allows direct property access for input', function (): void {
            $result = MatchResult::success(index: 0, match: 'test', input: 'full input string');

            expect($result->input)->toBe('full input string')
                ->and($result->input)->toBeString();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero index', function (): void {
            $result = MatchResult::success(index: 0, match: 'test');

            expect($result->index)->toBe(0)
                ->and($result->matched)->toBeTrue();
        });

        it('handles empty match string', function (): void {
            $result = MatchResult::success(index: 0, match: '');

            expect($result->match)->toBe('')
                ->and($result->matched)->toBeTrue();
        });

        it('handles large index values', function (): void {
            $result = MatchResult::success(index: 999_999, match: 'test');

            expect($result->index)->toBe(999_999);
        });

        it('handles many captures', function (): void {
            $captures = array_fill(0, 100, 'capture');
            $result = MatchResult::success(index: 0, match: 'test', captures: $captures);

            expect($result->captures)->toHaveCount(100);
        });

        it('handles Unicode in match string', function (): void {
            $result = MatchResult::success(index: 0, match: 'café ☕');

            expect($result->match)->toBe('café ☕');
        });

        it('handles Unicode in input string', function (): void {
            $result = MatchResult::success(index: 0, match: 'test', input: '你好世界');

            expect($result->input)->toBe('你好世界');
        });

        it('handles null input', function (): void {
            $result = MatchResult::success(index: 0, match: 'test', input: null);

            expect($result->input)->toBeNull();
        });
    });

    describe('Type Safety', function (): void {
        it('matched is always boolean', function (): void {
            $success = MatchResult::success(0, 'test');
            $failed = MatchResult::failed();

            expect($success->matched)->toBeBool()
                ->and($failed->matched)->toBeBool();
        });

        it('index is always integer', function (): void {
            $result = MatchResult::success(index: 5, match: 'test');

            expect($result->index)->toBeInt();
        });

        it('match is always string', function (): void {
            $result = MatchResult::success(index: 0, match: 'test');

            expect($result->match)->toBeString();
        });

        it('captures is always array', function (): void {
            $result = MatchResult::success(index: 0, match: 'test', captures: []);

            expect($result->captures)->toBeArray();
        });

        it('input is string or null', function (): void {
            $withInput = MatchResult::success(index: 0, match: 'test', input: 'input');
            $withoutInput = MatchResult::success(index: 0, match: 'test');

            expect($withInput->input)->toBeString();
            expect($withoutInput->input)->toBeNull();
        });
    });

    describe('Factory Methods', function (): void {
        it('success factory creates valid result', function (): void {
            $result = MatchResult::success(10, 'abc', ['cap1']);

            expect($result->matched)->toBeTrue()
                ->and($result->index)->toBe(10)
                ->and($result->match)->toBe('abc')
                ->and($result->captures)->toBe(['cap1']);
        });

        it('failed factory creates valid result', function (): void {
            $result = MatchResult::failed();

            expect($result->matched)->toBeFalse()
                ->and($result->index)->toBe(-1)
                ->and($result->match)->toBe('')
                ->and($result->captures)->toBe([]);
        });
    });
});
