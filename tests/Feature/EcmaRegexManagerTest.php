<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\EcmaRegexManager;
use Cline\EcmaRegex\Pattern;
use Cline\EcmaRegex\ValueObjects\MatchResult;
use Illuminate\Cache\Repository;

describe('EcmaRegexManager', function (): void {
    beforeEach(function (): void {
        $this->manager = resolve(EcmaRegexManager::class);
    });

    describe('Pattern Compilation', function (): void {
        it('compiles a simple pattern', function (): void {
            $pattern = $this->manager->compile('abc');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('abc')
                ->and($pattern->flags())->toBe('');
        });

        it('compiles pattern with flags', function (): void {
            $pattern = $this->manager->compile('abc', 'i');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->flags())->toBe('i');
        });

        it('compiles complex pattern', function (): void {
            $pattern = $this->manager->compile('^[a-z]+\d*$', 'gim');

            expect($pattern)->toBeInstanceOf(Pattern::class)
                ->and($pattern->source())->toBe('^[a-z]+\d*$')
                ->and($pattern->flags())->toBe('gim');
        });
    });

    describe('Pattern Caching', function (): void {
        it('caches compiled patterns', function (): void {
            $pattern1 = $this->manager->compile('test');
            $pattern2 = $this->manager->compile('test');

            expect($pattern1)->toBe($pattern2);
        });

        it('caches patterns with different flags separately', function (): void {
            $pattern1 = $this->manager->compile('test', 'i');
            $pattern2 = $this->manager->compile('test', 'g');

            expect($pattern1)->not->toBe($pattern2)
                ->and($pattern1->flags())->toBe('i')
                ->and($pattern2->flags())->toBe('g');
        });

        it('caches different patterns separately', function (): void {
            $pattern1 = $this->manager->compile('abc');
            $pattern2 = $this->manager->compile('xyz');

            expect($pattern1)->not->toBe($pattern2)
                ->and($pattern1->source())->toBe('abc')
                ->and($pattern2->source())->toBe('xyz');
        });

        it('clears cache on flush', function (): void {
            $pattern1 = $this->manager->compile('test');
            $this->manager->flushCache();
            $pattern2 = $this->manager->compile('test');

            // After flush, should get new instance
            expect($pattern1)->not->toBe($pattern2)
                ->and($pattern1->source())->toBe($pattern2->source());
        });

        it('uses persistent cache when enabled', function (): void {
            $cache = resolve(Repository::class);
            $lexer = resolve(LexerInterface::class);
            $parser = resolve(ParserInterface::class);

            $manager = new EcmaRegexManager($lexer, $parser, $cache, true, 3_600);

            $pattern1 = $manager->compile('cached-pattern');

            // Compile again - should retrieve from persistent cache
            $pattern2 = $manager->compile('cached-pattern');

            expect($pattern1)->toBe($pattern2);
        });

        it('stores patterns in persistent cache', function (): void {
            $cache = resolve(Repository::class);
            $lexer = resolve(LexerInterface::class);
            $parser = resolve(ParserInterface::class);

            $manager = new EcmaRegexManager($lexer, $parser, $cache, true, 3_600);

            // Clear any existing cache
            $cache->clear();

            // Compile pattern - should store in persistent cache
            $pattern = $manager->compile('persistent-test');

            // Verify it was stored in cache (format: ecma-regex:{hash}:{flags})
            $cacheKey = sprintf('ecma-regex:%s:', md5('persistent-test'));
            expect($cache->has($cacheKey))->toBeTrue();
        });

        it('retrieves patterns from persistent cache', function (): void {
            $cache = resolve(Repository::class);
            $lexer = resolve(LexerInterface::class);
            $parser = resolve(ParserInterface::class);

            // Clear cache first
            $cache->clear();

            // Create first manager instance and compile pattern
            $manager1 = new EcmaRegexManager($lexer, $parser, $cache, true, 3_600);
            $pattern1 = $manager1->compile('persistent-retrieve-test');

            // Create second manager instance with fresh memory cache
            $manager2 = new EcmaRegexManager($lexer, $parser, $cache, true, 3_600);

            // This should retrieve from persistent cache (lines 100-102)
            $pattern2 = $manager2->compile('persistent-retrieve-test');

            // Both patterns should be equivalent
            expect($pattern2->source())->toBe($pattern1->source());
            expect($pattern2->flags())->toBe($pattern1->flags());
        });

        it('clears persistent cache on flush', function (): void {
            $cache = resolve(Repository::class);
            $lexer = resolve(LexerInterface::class);
            $parser = resolve(ParserInterface::class);

            $manager = new EcmaRegexManager($lexer, $parser, $cache, true, 3_600);

            // Clear cache first
            $cache->clear();

            // Compile and cache pattern
            $manager->compile('flush-test');

            // Flush cache
            $manager->flushCache();

            // Verify cache was cleared
            $cacheKey = sprintf('ecma-regex:%s:', md5('flush-test'));
            expect($cache->has($cacheKey))->toBeFalse();
        });
    });

    describe('Test Method', function (): void {
        it('tests pattern match', function (): void {
            $result = $this->manager->test('abc', 'abc');

            expect($result)->toBeTrue();
        });

        it('tests pattern no match', function (): void {
            $result = $this->manager->test('abc', 'xyz');

            expect($result)->toBeFalse();
        });

        it('tests with flags', function (): void {
            $result = $this->manager->test('ABC', 'abc', 'i');

            expect($result)->toBeTrue();
        });

        it('tests pattern with metacharacters', function (): void {
            expect($this->manager->test('\d+', '123'))->toBeTrue()
                ->and($this->manager->test('\d+', 'abc'))->toBeFalse();
        });

        it('tests pattern with anchors', function (): void {
            expect($this->manager->test('^abc$', 'abc'))->toBeTrue()
                ->and($this->manager->test('^abc$', 'xabc'))->toBeFalse();
        });
    });

    describe('Match Method', function (): void {
        it('returns match result', function (): void {
            $result = $this->manager->match('abc', 'abc');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeTrue()
                ->and($result->match)->toBe('abc');
        });

        it('returns failed result when no match', function (): void {
            $result = $this->manager->match('abc', 'xyz');

            expect($result)->toBeInstanceOf(MatchResult::class)
                ->and($result->matched)->toBeFalse();
        });

        it('matches with captures', function (): void {
            $result = $this->manager->match('(a)(b)(c)', 'abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(3)
                ->and($result->captures[0])->toBe('a');
        });

        it('returns correct match index', function (): void {
            $result = $this->manager->match('abc', 'xyzabc');

            expect($result->matched)->toBeTrue()
                ->and($result->index)->toBe(3);
        });

        it('matches with flags', function (): void {
            $result = $this->manager->match('ABC', 'abc', 'i');

            expect($result->matched)->toBeTrue();
        });
    });

    describe('MatchAll Method', function (): void {
        it('finds all matches', function (): void {
            $matches = $this->manager->matchAll('\d+', '12 345 6789');

            expect($matches)->toBeArray()
                ->and($matches)->toHaveCount(3)
                ->and($matches[0]->match)->toBe('12')
                ->and($matches[1]->match)->toBe('345')
                ->and($matches[2]->match)->toBe('6789');
        });

        it('returns empty array when no matches', function (): void {
            $matches = $this->manager->matchAll('\d+', 'abc');

            expect($matches)->toBeArray()
                ->and($matches)->toBeEmpty();
        });

        it('finds all matches with correct indices', function (): void {
            $matches = $this->manager->matchAll('a', 'a b a c a');

            expect($matches)->toHaveCount(3)
                ->and($matches[0]->index)->toBe(0)
                ->and($matches[1]->index)->toBe(4)
                ->and($matches[2]->index)->toBe(8);
        });

        it('finds matches with captures', function (): void {
            $matches = $this->manager->matchAll('(\d+)', '12 34 56');

            expect($matches)->toHaveCount(3)
                ->and($matches[0]->captures[0])->toBe('12')
                ->and($matches[1]->captures[0])->toBe('34')
                ->and($matches[2]->captures[0])->toBe('56');
        });
    });

    describe('Integration Tests', function (): void {
        it('compiles and uses pattern multiple times', function (): void {
            $pattern = $this->manager->compile('[a-z]+');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('xyz'))->toBeTrue()
                ->and($pattern->test('123'))->toBeFalse();
        });

        it('handles multiple patterns concurrently', function (): void {
            $pattern1 = $this->manager->compile('\d+');
            $pattern2 = $this->manager->compile('[a-z]+');

            expect($pattern1->test('123'))->toBeTrue()
                ->and($pattern2->test('abc'))->toBeTrue()
                ->and($pattern1->test('abc'))->toBeFalse()
                ->and($pattern2->test('123'))->toBeFalse();
        });

        it('works with email pattern', function (): void {
            $pattern = '[a-z]+@[a-z]+\.[a-z]+';

            expect($this->manager->test($pattern, 'test@example.com'))->toBeTrue()
                ->and($this->manager->test($pattern, 'invalid@'))->toBeFalse();
        });

        it('works with URL pattern', function (): void {
            $pattern = 'https?://[a-z]+\.[a-z]+';

            expect($this->manager->test($pattern, 'http://example.com'))->toBeTrue()
                ->and($this->manager->test($pattern, 'https://domain.org'))->toBeTrue();
        });

        it('works with phone number pattern', function (): void {
            $pattern = '^\d{3}-\d{3}-\d{4}$';

            expect($this->manager->test($pattern, '123-456-7890'))->toBeTrue()
                ->and($this->manager->test($pattern, '123-45-6789'))->toBeFalse();
        });
    });

    describe('Cache Management', function (): void {
        it('maintains separate memory cache', function (): void {
            $pattern1 = $this->manager->compile('test');
            $pattern2 = $this->manager->compile('test');

            expect($pattern1)->toBe($pattern2);
        });

        it('flush clears all cached patterns', function (): void {
            $this->manager->compile('pattern1');
            $this->manager->compile('pattern2');
            $this->manager->compile('pattern3');

            $this->manager->flushCache();

            // After flush, should recompile
            $pattern = $this->manager->compile('pattern1');
            expect($pattern)->toBeInstanceOf(Pattern::class);
        });

        it('recompiles after cache flush', function (): void {
            $before = $this->manager->compile('test');
            $this->manager->flushCache();
            $after = $this->manager->compile('test');

            expect($before->source())->toBe($after->source())
                ->and($before)->not->toBe($after);
        });
    });

    describe('Error Handling', function (): void {
        it('throws on invalid pattern syntax', function (): void {
            expect(fn () => $this->manager->compile('(unclosed'))
                ->toThrow(RuntimeException::class);
        });

        it('throws on invalid character class', function (): void {
            expect(fn () => $this->manager->compile('[unclosed'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws on invalid group construct', function (): void {
            expect(fn () => $this->manager->compile('(?x)'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Edge Cases', function (): void {
        it('handles empty pattern', function (): void {
            $pattern = $this->manager->compile('');

            expect($pattern->test(''))->toBeTrue()
                ->and($pattern->test('anything'))->toBeTrue();
        });

        it('handles pattern with only metacharacters', function (): void {
            $pattern = $this->manager->compile('.*');

            expect($pattern->test('anything'))->toBeTrue();
        });

        it('handles Unicode patterns', function (): void {
            $pattern = $this->manager->compile('café');

            expect($pattern->test('café'))->toBeTrue();
        });

        it('handles very long patterns', function (): void {
            $longPattern = str_repeat('a|', 100).'a';
            $pattern = $this->manager->compile($longPattern);

            expect($pattern->test('a'))->toBeTrue();
        });
    });
});
