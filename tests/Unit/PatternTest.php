<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Pattern;
use Cline\EcmaRegex\ValueObjects\MatchResult;

describe('Pattern', function (): void {
    describe('Compilation', function (): void {
        it('compiles a simple pattern', function (): void {
            $pattern = createPattern('abc');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('abc')
                ->and($pattern->flags())->toBe('');
        });

        it('compiles pattern with flags', function (): void {
            $pattern = createPattern('abc', 'i');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('abc')
                ->and($pattern->flags())->toBe('i');
        });

        it('compiles complex pattern', function (): void {
            $pattern = createPattern('^[a-z]+\d*$', 'gim');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('^[a-z]+\d*$')
                ->and($pattern->flags())->toBe('gim');
        });
    });

    describe('Test Method', function (): void {
        it('returns true when pattern matches', function (): void {
            $pattern = createPattern('abc');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('xyzabc'))->toBeTrue();
        });

        it('returns false when pattern does not match', function (): void {
            $pattern = createPattern('abc');

            expect($pattern->test('xyz'))->toBeFalse()
                ->and($pattern->test('ab'))->toBeFalse();
        });

        it('tests with anchors', function (): void {
            $pattern = createPattern('^abc$');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('abcd'))->toBeFalse()
                ->and($pattern->test('xyzabc'))->toBeFalse();
        });

        it('tests with character classes', function (): void {
            $pattern = createPattern('[0-9]+');

            expect($pattern->test('123'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeFalse();
        });

        it('tests with quantifiers', function (): void {
            $pattern = createPattern('a+');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('aaa'))->toBeTrue()
                ->and($pattern->test('b'))->toBeFalse();
        });
    });

    describe('Exec Method', function (): void {
        it('returns match result on success', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('abc');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeTrue()
                ->and($result->match)->toBe('abc')
                ->and($result->index)->toBe(0);
        });

        it('returns match with correct index', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('xyzabc');

            expect($result->matched)->toBeTrue()
                ->and($result->match)->toBe('abc')
                ->and($result->index)->toBe(3);
        });

        it('returns failed result when no match', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('xyz');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeFalse()
                ->and($result->index)->toBe(-1);
        });

        it('captures groups', function (): void {
            $pattern = createPattern('(a)(b)(c)');
            $result = $pattern->exec('abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(3)
                ->and($result->captures[0])->toBe('a')
                ->and($result->captures[1])->toBe('b')
                ->and($result->captures[2])->toBe('c');
        });

        it('includes input in result', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('abc');

            expect($result->input)->toBe('abc');
        });
    });

    describe('MatchAll Method', function (): void {
        it('finds all matches', function (): void {
            $pattern = createPattern('\d+');
            $matches = $pattern->matchAll('12 345 6789');

            expect($matches)->toBeArray()
                ->and($matches)->toHaveCount(3)
                ->and($matches[0]->match)->toBe('12')
                ->and($matches[1]->match)->toBe('345')
                ->and($matches[2]->match)->toBe('6789');
        });

        it('returns empty array when no matches', function (): void {
            $pattern = createPattern('\d+');
            $matches = $pattern->matchAll('abc');

            expect($matches)->toBeArray()
                ->and($matches)->toBeEmpty();
        });

        it('finds matches with correct indices', function (): void {
            $pattern = createPattern('a');
            $matches = $pattern->matchAll('a b a');

            expect($matches)->toHaveCount(2)
                ->and($matches[0]->index)->toBe(0)
                ->and($matches[1]->index)->toBe(4);
        });

        it('finds all matches with groups', function (): void {
            $pattern = createPattern('(\d+)');
            $matches = $pattern->matchAll('12 34');

            expect($matches)->toHaveCount(2)
                ->and($matches[0]->captures[0])->toBe('12')
                ->and($matches[1]->captures[0])->toBe('34');
        });
    });

    describe('Source and Flags Accessors', function (): void {
        it('returns source pattern', function (): void {
            $pattern = createPattern('test.*pattern');

            expect($pattern->source())->toBe('test.*pattern');
        });

        it('returns empty flags when not provided', function (): void {
            $pattern = createPattern('test');

            expect($pattern->flags())->toBe('');
        });

        it('returns flags when provided', function (): void {
            $pattern = createPattern('test', 'gim');

            expect($pattern->flags())->toBe('gim');
        });
    });

    describe('Pattern Immutability', function (): void {
        it('is readonly and cannot be modified', function (): void {
            $pattern = createPattern('abc', 'i');

            expect($pattern->source())->toBe('abc')
                ->and($pattern->flags())->toBe('i');

            // Verify multiple calls return same values
            expect($pattern->source())->toBe('abc')
                ->and($pattern->flags())->toBe('i');
        });
    });

    describe('Complex Patterns', function (): void {
        it('handles email pattern', function (): void {
            $pattern = createPattern('[a-z]+@[a-z]+\.[a-z]+');

            expect($pattern->test('test@example.com'))->toBeTrue()
                ->and($pattern->test('invalid@'))->toBeFalse();
        });

        it('handles URL pattern', function (): void {
            $pattern = createPattern('https?://[a-z]+\.[a-z]+');

            expect($pattern->test('http://example.com'))->toBeTrue()
                ->and($pattern->test('https://domain.org'))->toBeTrue()
                ->and($pattern->test('ftp://site.net'))->toBeFalse();
        });

        it('handles phone number pattern', function (): void {
            $pattern = createPattern('^\d{3}-\d{3}-\d{4}$');

            expect($pattern->test('123-456-7890'))->toBeTrue()
                ->and($pattern->test('123-45-6789'))->toBeFalse();
        });

        it('handles nested groups', function (): void {
            $pattern = createPattern('(a(b)(c))');
            $result = $pattern->exec('abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->not->toBeEmpty();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles empty pattern', function (): void {
            $pattern = createPattern('');

            expect($pattern->test(''))->toBeTrue()
                ->and($pattern->test('a'))->toBeTrue();
        });

        it('handles pattern with only metacharacters', function (): void {
            $pattern = createPattern('.*');

            expect($pattern->test('anything'))->toBeTrue()
                ->and($pattern->test(''))->toBeTrue();
        });

        it('handles Unicode input', function (): void {
            $pattern = createPattern('café');

            expect($pattern->test('café'))->toBeTrue();
        });

        it('handles special characters in pattern', function (): void {
            $pattern = createPattern('\.');

            expect($pattern->test('.'))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });
    });
});
