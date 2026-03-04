<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Facades\EcmaRegex;
use Cline\EcmaRegex\Pattern;
use Cline\EcmaRegex\ValueObjects\MatchResult;

describe('EcmaRegex Facade', function (): void {
    describe('Compile Method', function (): void {
        it('compiles pattern via facade', function (): void {
            $pattern = EcmaRegex::compile('abc');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('abc');
        });

        it('compiles pattern with flags via facade', function (): void {
            $pattern = EcmaRegex::compile('abc', 'i');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->flags())->toBe('i');
        });

        it('compiles complex pattern via facade', function (): void {
            $pattern = EcmaRegex::compile('^[a-z]+\d*$', 'gim');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('^[a-z]+\d*$')
                ->and($pattern->flags())->toBe('gim');
        });
    });

    describe('Test Method', function (): void {
        it('tests pattern match via facade', function (): void {
            $result = EcmaRegex::test('abc', 'abc');

            expect($result)->toBeTrue();
        });

        it('tests pattern no match via facade', function (): void {
            $result = EcmaRegex::test('abc', 'xyz');

            expect($result)->toBeFalse();
        });

        it('tests with flags via facade', function (): void {
            $result = EcmaRegex::test('ABC', 'abc', 'i');

            expect($result)->toBeTrue();
        });

        it('tests pattern with character class', function (): void {
            expect(EcmaRegex::test('[0-9]+', '123'))->toBeTrue()
                ->and(EcmaRegex::test('[0-9]+', 'abc'))->toBeFalse();
        });

        it('tests pattern with quantifiers', function (): void {
            expect(EcmaRegex::test('a+', 'aaa'))->toBeTrue()
                ->and(EcmaRegex::test('a+', 'b'))->toBeFalse();
        });

        it('tests pattern with anchors', function (): void {
            expect(EcmaRegex::test('^abc$', 'abc'))->toBeTrue()
                ->and(EcmaRegex::test('^abc$', 'xabc'))->toBeFalse();
        });
    });

    describe('Match Method', function (): void {
        it('matches pattern via facade', function (): void {
            $result = EcmaRegex::match('abc', 'abc');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeTrue()
                ->and($result->match)->toBe('abc');
        });

        it('returns failed result via facade', function (): void {
            $result = EcmaRegex::match('abc', 'xyz');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeFalse();
        });

        it('captures groups via facade', function (): void {
            $result = EcmaRegex::match('(a)(b)(c)', 'abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(3)
                ->and($result->captures[0])->toBe('a')
                ->and($result->captures[1])->toBe('b')
                ->and($result->captures[2])->toBe('c');
        });

        it('returns correct index via facade', function (): void {
            $result = EcmaRegex::match('abc', 'xyzabc');

            expect($result->matched)->toBeTrue()
                ->and($result->index)->toBe(3);
        });

        it('matches with flags via facade', function (): void {
            $result = EcmaRegex::match('ABC', 'abc', 'i');

            expect($result->matched)->toBeTrue();
        });
    });

    describe('MatchAll Method', function (): void {
        it('finds all matches via facade', function (): void {
            $matches = EcmaRegex::matchAll('\d+', '12 345 6789');

            expect($matches)->toBeArray()
                ->and($matches)->toHaveCount(3)
                ->and($matches[0]->match)->toBe('12')
                ->and($matches[1]->match)->toBe('345')
                ->and($matches[2]->match)->toBe('6789');
        });

        it('returns empty array when no matches via facade', function (): void {
            $matches = EcmaRegex::matchAll('\d+', 'abc');

            expect($matches)->toBeArray()
                ->and($matches)->toBeEmpty();
        });

        it('finds matches with correct indices via facade', function (): void {
            $matches = EcmaRegex::matchAll('a', 'a b a c a');

            expect($matches)->toHaveCount(3)
                ->and($matches[0]->index)->toBe(0)
                ->and($matches[1]->index)->toBe(4)
                ->and($matches[2]->index)->toBe(8);
        });

        it('finds matches with captures via facade', function (): void {
            $matches = EcmaRegex::matchAll('(\d+)', '12 34 56');

            expect($matches)->toHaveCount(3)
                ->and($matches[0]->captures[0])->toBe('12')
                ->and($matches[1]->captures[0])->toBe('34')
                ->and($matches[2]->captures[0])->toBe('56');
        });
    });

    describe('FlushCache Method', function (): void {
        it('flushes cache via facade', function (): void {
            EcmaRegex::compile('test');
            EcmaRegex::flushCache();

            // Should not throw
            expect(true)->toBeTrue();
        });

        it('clears cached patterns via facade', function (): void {
            $before = EcmaRegex::compile('test');
            EcmaRegex::flushCache();
            $after = EcmaRegex::compile('test');

            expect($before->source())->toBe($after->source())
                ->and($before)->not->toBe($after);
        });
    });

    describe('Integration with Real Patterns', function (): void {
        it('validates email addresses', function (): void {
            $pattern = '[a-z0-9]+@[a-z0-9]+\.[a-z]+';

            expect(EcmaRegex::test($pattern, 'test@example.com'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'user123@domain.org'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'invalid@'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, '@example.com'))->toBeFalse();
        });

        it('validates phone numbers', function (): void {
            $pattern = '^\d{3}-\d{3}-\d{4}$';

            expect(EcmaRegex::test($pattern, '123-456-7890'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '555-123-4567'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '123-45-6789'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, '1234567890'))->toBeFalse();
        });

        it('validates URLs', function (): void {
            $pattern = 'https?://[a-z0-9]+\.[a-z]+';

            expect(EcmaRegex::test($pattern, 'http://example.com'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'https://domain.org'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'ftp://site.net'))->toBeFalse();
        });

        it('extracts data with groups', function (): void {
            $pattern = '(\d{3})-(\d{3})-(\d{4})';
            $result = EcmaRegex::match($pattern, '123-456-7890');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(3)
                ->and($result->captures[0])->toBe('123')
                ->and($result->captures[1])->toBe('456')
                ->and($result->captures[2])->toBe('7890');
        });

        it('finds all occurrences', function (): void {
            $pattern = '\b[A-Z][a-z]+\b';
            $matches = EcmaRegex::matchAll($pattern, 'Hello World From Pest');

            expect($matches)->toHaveCount(4)
                ->and($matches[0]->match)->toBe('Hello')
                ->and($matches[1]->match)->toBe('World')
                ->and($matches[2]->match)->toBe('From')
                ->and($matches[3]->match)->toBe('Pest');
        });
    });

    describe('Static Method Access', function (): void {
        it('provides static access to compile', function (): void {
            $pattern = EcmaRegex::compile('test');

            expect($pattern)->toBeInstanceOf(Pattern::class);
        });

        it('provides static access to test', function (): void {
            $result = EcmaRegex::test('test', 'test');

            expect($result)->toBeBool();
        });

        it('provides static access to match', function (): void {
            $result = EcmaRegex::match('test', 'test');

            expect($result)->toBeInstanceOf(MatchResult::class);
        });

        it('provides static access to matchAll', function (): void {
            $result = EcmaRegex::matchAll('test', 'test');

            expect($result)->toBeArray();
        });

        it('provides static access to flushCache', function (): void {
            EcmaRegex::flushCache();

            expect(true)->toBeTrue();
        });
    });

    describe('Fluent Interface', function (): void {
        it('chains compile and test', function (): void {
            $result = EcmaRegex::compile('abc')->test('abc');

            expect($result)->toBeTrue();
        });

        it('chains compile and exec', function (): void {
            $result = EcmaRegex::compile('abc')->exec('abc');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeTrue();
        });

        it('chains compile and matchAll', function (): void {
            $matches = EcmaRegex::compile('\d+')->matchAll('12 34');

            expect($matches)->toBeArray()
                ->and($matches)->toHaveCount(2);
        });
    });

    describe('Edge Cases', function (): void {
        it('handles empty pattern via facade', function (): void {
            expect(EcmaRegex::test('', 'anything'))->toBeTrue();
        });

        it('handles Unicode via facade', function (): void {
            expect(EcmaRegex::test('café', 'café'))->toBeTrue();
        });

        it('handles complex alternation via facade', function (): void {
            $pattern = 'cat|dog|bird';

            expect(EcmaRegex::test($pattern, 'cat'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'dog'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'bird'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'fish'))->toBeFalse();
        });
    });
});
