<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Contracts\MatcherInterface;
use Cline\EcmaRegex\Exceptions\UnsupportedAssertionKindException;
use Cline\EcmaRegex\Matcher\MatchContext;

describe('Matcher', function (): void {
    beforeEach(function (): void {
        $this->matcher = resolve(MatcherInterface::class);
    });

    describe('Literal Matching', function (): void {
        it('matches simple literal', function (): void {
            $pattern = createPattern('abc');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('def'))->toBeFalse();
        });

        it('matches literal within larger string', function (): void {
            $pattern = createPattern('abc');

            expect($pattern->test('xyzabcdef'))->toBeTrue();
        });

        it('matches numeric literals', function (): void {
            $pattern = createPattern('123');

            expect($pattern->test('123'))->toBeTrue()
                ->and($pattern->test('abc123def'))->toBeTrue()
                ->and($pattern->test('456'))->toBeFalse();
        });
    });

    describe('Dot Metacharacter', function (): void {
        it('matches any character with dot', function (): void {
            $pattern = createPattern('a.c');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('axc'))->toBeTrue()
                ->and($pattern->test('a1c'))->toBeTrue()
                ->and($pattern->test('ac'))->toBeFalse();
        });

        it('matches multiple dots', function (): void {
            $pattern = createPattern('a..c');

            expect($pattern->test('abxc'))->toBeTrue()
                ->and($pattern->test('a12c'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeFalse();
        });
    });

    describe('Anchors', function (): void {
        it('matches start anchor', function (): void {
            $pattern = createPattern('^abc');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('abcdef'))->toBeTrue()
                ->and($pattern->test('xyzabc'))->toBeFalse();
        });

        it('matches end anchor', function (): void {
            $pattern = createPattern('abc$');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('xyzabc'))->toBeTrue()
                ->and($pattern->test('abcdef'))->toBeFalse();
        });

        it('matches exact string with both anchors', function (): void {
            $pattern = createPattern('^abc$');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('abcd'))->toBeFalse()
                ->and($pattern->test('xabc'))->toBeFalse();
        });

        it('matches empty string with anchors only', function (): void {
            $pattern = createPattern('^$');

            expect($pattern->test(''))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });
    });

    describe('Character Classes', function (): void {
        it('matches character class', function (): void {
            $pattern = createPattern('[abc]');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('b'))->toBeTrue()
                ->and($pattern->test('c'))->toBeTrue()
                ->and($pattern->test('d'))->toBeFalse();
        });

        it('matches negated character class', function (): void {
            $pattern = createPattern('[^abc]');

            expect($pattern->test('d'))->toBeTrue()
                ->and($pattern->test('x'))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse()
                ->and($pattern->test('b'))->toBeFalse();
        });

        it('matches character range', function (): void {
            $pattern = createPattern('[a-z]');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('m'))->toBeTrue()
                ->and($pattern->test('z'))->toBeTrue()
                ->and($pattern->test('A'))->toBeFalse()
                ->and($pattern->test('0'))->toBeFalse();
        });

        it('matches digit range', function (): void {
            $pattern = createPattern('[0-9]');

            expect($pattern->test('0'))->toBeTrue()
                ->and($pattern->test('5'))->toBeTrue()
                ->and($pattern->test('9'))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });

        it('matches multiple ranges', function (): void {
            $pattern = createPattern('[a-zA-Z]');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('Z'))->toBeTrue()
                ->and($pattern->test('0'))->toBeFalse();
        });
    });

    describe('Quantifiers', function (): void {
        it('matches asterisk quantifier zero times', function (): void {
            $pattern = createPattern('ab*c');

            expect($pattern->test('ac'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('abbbc'))->toBeTrue();
        });

        it('matches plus quantifier one or more times', function (): void {
            $pattern = createPattern('ab+c');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('abbbc'))->toBeTrue()
                ->and($pattern->test('ac'))->toBeFalse();
        });

        it('matches question quantifier zero or one time', function (): void {
            $pattern = createPattern('ab?c');

            expect($pattern->test('ac'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('abbc'))->toBeFalse();
        });

        it('matches exact count quantifier', function (): void {
            $pattern = createPattern('ab{3}c');

            expect($pattern->test('abbbc'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeFalse()
                ->and($pattern->test('abbc'))->toBeFalse()
                ->and($pattern->test('abbbbc'))->toBeFalse();
        });

        it('matches minimum count quantifier', function (): void {
            $pattern = createPattern('ab{2,}c');

            expect($pattern->test('abbc'))->toBeTrue()
                ->and($pattern->test('abbbc'))->toBeTrue()
                ->and($pattern->test('abbbbc'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeFalse();
        });

        it('matches range quantifier', function (): void {
            $pattern = createPattern('ab{2,4}c');

            expect($pattern->test('abbc'))->toBeTrue()
                ->and($pattern->test('abbbc'))->toBeTrue()
                ->and($pattern->test('abbbbc'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeFalse()
                ->and($pattern->test('abbbbbc'))->toBeFalse();
        });

        it('matches quantified character class', function (): void {
            $pattern = createPattern('[a-z]+');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('xyz'))->toBeTrue()
                ->and($pattern->test('123'))->toBeFalse();
        });
    });

    describe('Groups', function (): void {
        it('matches capturing group', function (): void {
            $pattern = createPattern('(abc)');

            expect($pattern->test('abc'))->toBeTrue();
        });

        it('captures group content', function (): void {
            $pattern = createPattern('(abc)');
            $result = $pattern->exec('abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(1)
                ->and($result->captures[0])->toBe('abc');
        });

        it('captures multiple groups', function (): void {
            $pattern = createPattern('(a)(b)(c)');
            $result = $pattern->exec('abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(3)
                ->and($result->captures[0])->toBe('a')
                ->and($result->captures[1])->toBe('b')
                ->and($result->captures[2])->toBe('c');
        });

        it('matches non-capturing group without capturing', function (): void {
            $pattern = createPattern('(?:abc)');
            $result = $pattern->exec('abc');

            expect($result->matched)->toBeTrue()
                ->and($result->captures)->toHaveCount(0);
        });
    });

    describe('Alternation', function (): void {
        it('matches first alternative', function (): void {
            $pattern = createPattern('a|b');

            expect($pattern->test('a'))->toBeTrue();
        });

        it('matches second alternative', function (): void {
            $pattern = createPattern('a|b');

            expect($pattern->test('b'))->toBeTrue();
        });

        it('matches multiple alternatives', function (): void {
            $pattern = createPattern('cat|dog|bird');

            expect($pattern->test('cat'))->toBeTrue()
                ->and($pattern->test('dog'))->toBeTrue()
                ->and($pattern->test('bird'))->toBeTrue()
                ->and($pattern->test('fish'))->toBeFalse();
        });

        it('matches alternation in groups', function (): void {
            $pattern = createPattern('(a|b)c');

            expect($pattern->test('ac'))->toBeTrue()
                ->and($pattern->test('bc'))->toBeTrue()
                ->and($pattern->test('cc'))->toBeFalse();
        });
    });

    describe('Escape Sequences', function (): void {
        it('matches digit escape', function (): void {
            $pattern = createPattern('\d');

            expect($pattern->test('0'))->toBeTrue()
                ->and($pattern->test('5'))->toBeTrue()
                ->and($pattern->test('9'))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });

        it('matches word character escape', function (): void {
            $pattern = createPattern('\w');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('Z'))->toBeTrue()
                ->and($pattern->test('0'))->toBeTrue()
                ->and($pattern->test('_'))->toBeTrue()
                ->and($pattern->test('-'))->toBeFalse();
        });

        it('matches whitespace escape', function (): void {
            $pattern = createPattern('\s');

            expect($pattern->test(' '))->toBeTrue()
                ->and($pattern->test("\t"))->toBeTrue()
                ->and($pattern->test("\n"))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });

        it('matches negated digit escape', function (): void {
            $pattern = createPattern('\D');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('0'))->toBeFalse();
        });

        it('matches negated word escape', function (): void {
            $pattern = createPattern('\W');

            expect($pattern->test('-'))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });

        it('matches negated whitespace escape', function (): void {
            $pattern = createPattern('\S');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test(' '))->toBeFalse();
        });

        it('matches escaped metacharacters as literals', function (): void {
            $pattern = createPattern('\.');

            expect($pattern->test('.'))->toBeTrue()
                ->and($pattern->test('a'))->toBeFalse();
        });
    });

    describe('Complex Patterns', function (): void {
        it('matches email-like pattern', function (): void {
            $pattern = createPattern('[a-z]+@[a-z]+\.[a-z]+');

            expect($pattern->test('test@example.com'))->toBeTrue()
                ->and($pattern->test('user@domain.org'))->toBeTrue()
                ->and($pattern->test('invalid@'))->toBeFalse();
        });

        it('matches phone number pattern', function (): void {
            $pattern = createPattern('\d{3}-\d{3}-\d{4}');

            expect($pattern->test('123-456-7890'))->toBeTrue()
                ->and($pattern->test('555-123-4567'))->toBeTrue()
                ->and($pattern->test('123-45-6789'))->toBeFalse();
        });

        it('matches URL pattern', function (): void {
            $pattern = createPattern('https?://[a-z]+\.[a-z]+');

            expect($pattern->test('http://example.com'))->toBeTrue()
                ->and($pattern->test('https://domain.org'))->toBeTrue()
                ->and($pattern->test('ftp://site.net'))->toBeFalse();
        });
    });

    describe('Match Results', function (): void {
        it('returns match index', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('xyzabc');

            expect($result->matched)->toBeTrue()
                ->and($result->index)->toBe(3);
        });

        it('returns matched string', function (): void {
            $pattern = createPattern('a+');
            $result = $pattern->exec('aaa');

            expect($result->matched)->toBeTrue()
                ->and($result->match)->toBe('aaa');
        });

        it('returns input string', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('abc');

            expect($result->matched)->toBeTrue()
                ->and($result->input)->toBe('abc');
        });

        it('returns failed result when no match', function (): void {
            $pattern = createPattern('abc');
            $result = $pattern->exec('xyz');

            expect($result->matched)->toBeFalse()
                ->and($result->index)->toBe(-1);
        });
    });

    describe('Match All', function (): void {
        it('finds all matches', function (): void {
            $pattern = createPattern('a+');
            $matches = $pattern->matchAll('a aa aaa');

            expect($matches)->toHaveCount(3)
                ->and($matches[0]->match)->toBe('a')
                ->and($matches[1]->match)->toBe('aa')
                ->and($matches[2]->match)->toBe('aaa');
        });

        it('finds no matches when pattern does not match', function (): void {
            $pattern = createPattern('abc');
            $matches = $pattern->matchAll('xyz');

            expect($matches)->toBeEmpty();
        });

        it('finds overlapping matches correctly', function (): void {
            $pattern = createPattern('\d+');
            $matches = $pattern->matchAll('12 345 6789');

            expect($matches)->toHaveCount(3)
                ->and($matches[0]->match)->toBe('12')
                ->and($matches[1]->match)->toBe('345')
                ->and($matches[2]->match)->toBe('6789');
        });
    });

    describe('Assertions', function (): void {
        it('matches positive lookbehind', function (): void {
            $pattern = createPattern('(?<=@)\w+');

            expect($pattern->test('@test'))->toBeTrue()
                ->and($pattern->exec('@test')->match)->toBe('test')
                ->and($pattern->test('test'))->toBeFalse();
        });

        it('matches negative lookbehind', function (): void {
            $pattern = createPattern('(?<!@)\w+');

            expect($pattern->test('test'))->toBeTrue()
                ->and($pattern->test('@test'))->toBeTrue() // Matches 'est' at position 2
                ->and($pattern->exec('@test')->match)->toBe('est')
                ->and($pattern->exec('@test')->index)->toBe(2);
        });

        it('matches lookbehind with literal pattern', function (): void {
            $pattern = createPattern('(?<=foo)bar');

            expect($pattern->test('foobar'))->toBeTrue()
                ->and($pattern->exec('foobar')->match)->toBe('bar')
                ->and($pattern->test('bar'))->toBeFalse();
        });

        it('matches lookbehind with character class', function (): void {
            $pattern = createPattern('(?<=[0-9])[a-z]+');

            expect($pattern->test('1abc'))->toBeTrue()
                ->and($pattern->exec('1abc')->match)->toBe('abc')
                ->and($pattern->test('abc'))->toBeFalse();
        });

        it('matches negative lookbehind with pattern', function (): void {
            $pattern = createPattern('(?<!foo)bar');

            expect($pattern->test('bar'))->toBeTrue()
                ->and($pattern->test('foobar'))->toBeFalse();
        });

        it('matches lookbehind at different positions', function (): void {
            $pattern = createPattern('(?<=a)b');

            $result1 = $pattern->exec('ab');
            $result2 = $pattern->exec('xxab');

            expect($result1->matched)->toBeTrue()
                ->and($result1->match)->toBe('b')
                ->and($result1->index)->toBe(1)
                ->and($result2->matched)->toBeTrue()
                ->and($result2->match)->toBe('b')
                ->and($result2->index)->toBe(3);
        });
    });

    describe('Edge Cases', function (): void {
        it('matches empty pattern', function (): void {
            $pattern = createPattern('');

            expect($pattern->test(''))->toBeTrue()
                ->and($pattern->test('a'))->toBeTrue();
        });

        it('handles Unicode characters', function (): void {
            $pattern = createPattern('café');

            expect($pattern->test('café'))->toBeTrue()
                ->and($pattern->test('cafe'))->toBeFalse();
        });

        it('handles greedy vs non-greedy quantifiers', function (): void {
            $pattern = createPattern('a+');
            $result = $pattern->exec('aaaa');

            expect($result->match)->toBe('aaaa');
        });

        it('handles backtracking in complex patterns', function (): void {
            $pattern = createPattern('(a|ab)+');

            expect($pattern->test('ab'))->toBeTrue()
                ->and($pattern->test('aab'))->toBeTrue();
        });

        it('handles word boundary at start', function (): void {
            $pattern = createPattern('\bword');

            expect($pattern->test('word'))->toBeTrue()
                ->and($pattern->test('sword'))->toBeFalse();
        });

        it('handles word boundary at end', function (): void {
            $pattern = createPattern('word\b');

            expect($pattern->test('word'))->toBeTrue()
                ->and($pattern->test('words'))->toBeFalse();
        });

        it('handles non-word boundary', function (): void {
            $pattern = createPattern('\Btest');

            expect($pattern->test('atest'))->toBeTrue()
                ->and($pattern->test('test'))->toBeFalse();
        });

        it('matches backreferences', function (): void {
            $pattern = createPattern('(a)\1');

            expect($pattern->test('aa'))->toBeTrue()
                ->and($pattern->test('ab'))->toBeFalse();
        });

        it('matches backreferences with multiple groups', function (): void {
            $pattern = createPattern('(a)(b)\1');

            expect($pattern->test('aba'))->toBeTrue()
                ->and($pattern->test('abb'))->toBeFalse();
        });

        it('handles nested quantifiers', function (): void {
            $pattern = createPattern('(a+)+');

            expect($pattern->test('aaa'))->toBeTrue()
                ->and($pattern->test('b'))->toBeFalse();
        });

        it('handles complex alternation with groups', function (): void {
            $pattern = createPattern('(foo|bar)(baz|qux)');

            expect($pattern->test('foobaz'))->toBeTrue()
                ->and($pattern->test('barqux'))->toBeTrue()
                ->and($pattern->test('fooqux'))->toBeTrue()
                ->and($pattern->test('foobar'))->toBeFalse();
        });

        it('matches with case-insensitive flag', function (): void {
            $pattern = createPattern('ABC', 'i');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('ABC'))->toBeTrue()
                ->and($pattern->test('AbC'))->toBeTrue();
        });

        it('handles global flag with matchAll', function (): void {
            $pattern = createPattern('a', 'g');
            $results = $pattern->matchAll('aaa');

            expect($results)->toHaveCount(3);
        });

        it('handles complex negative character class', function (): void {
            $pattern = createPattern('[^a-z]+');

            expect($pattern->test('123'))->toBeTrue()
                ->and($pattern->test('ABC'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeFalse();
        });

        it('handles escaped characters in character class', function (): void {
            $pattern = createPattern('[a\-z]');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('-'))->toBeTrue()
                ->and($pattern->test('z'))->toBeTrue()
                ->and($pattern->test('b'))->toBeFalse();
        });

        it('handles lookahead and lookbehind combinations', function (): void {
            $pattern = createPattern('(?<=a)b(?=c)');

            expect($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('bc'))->toBeFalse()
                ->and($pattern->test('ab'))->toBeFalse();
        });

        it('handles negative lookahead', function (): void {
            $pattern = createPattern('a(?!b)');

            expect($pattern->test('ac'))->toBeTrue()
                ->and($pattern->test('ab'))->toBeFalse();
        });

        it('handles negative lookbehind', function (): void {
            $pattern = createPattern('(?<!a)b');

            expect($pattern->test('cb'))->toBeTrue()
                ->and($pattern->test('ab'))->toBeFalse();
        });

        it('handles zero-width assertions', function (): void {
            $pattern = createPattern('a(?=b)');

            expect($pattern->test('ab'))->toBeTrue()
                ->and($pattern->test('ac'))->toBeFalse();
        });

        it('handles complex backreference patterns', function (): void {
            $pattern = createPattern('(a|b)\1+');

            expect($pattern->test('aa'))->toBeTrue()
                ->and($pattern->test('bb'))->toBeTrue()
                ->and($pattern->test('ab'))->toBeFalse();
        });

        it('handles quantifier edge cases', function (): void {
            $pattern = createPattern('a{0}');

            expect($pattern->test(''))->toBeTrue();
        });

        it('handles alternation within groups', function (): void {
            $pattern = createPattern('(a|b|c)');

            expect($pattern->test('a'))->toBeTrue()
                ->and($pattern->test('b'))->toBeTrue()
                ->and($pattern->test('c'))->toBeTrue()
                ->and($pattern->test('d'))->toBeFalse();
        });

        it('handles mixed quantifiers', function (): void {
            $pattern = createPattern('a*b+c?');

            expect($pattern->test('b'))->toBeTrue()
                ->and($pattern->test('abc'))->toBeTrue()
                ->and($pattern->test('aaabbc'))->toBeTrue();
        });
    });

    describe('Edge Cases', function (): void {
        it('matches dot with Unicode line separators', function (): void {
            $pattern = createPattern('a.b');

            // Dot should NOT match line terminators
            expect($pattern->test("a\nb"))->toBeFalse()
                ->and($pattern->test("a\rb"))->toBeFalse()
                ->and($pattern->test("a\u{2028}b"))->toBeFalse()
                ->and($pattern->test("a\u{2029}b"))->toBeFalse();
        });

        it('handles non-greedy quantifiers minimal matching', function (): void {
            $pattern = createPattern('a+?');

            // Non-greedy should match minimum required (1 occurrence)
            $result = $pattern->exec('aaa');
            expect($result->matched)->toBeTrue()
                ->and($result->match)->toBe('a');
        });

        it('handles non-greedy quantifiers with range', function (): void {
            $pattern = createPattern('a{2,4}?');

            // Non-greedy should match minimum (2 occurrences)
            $result = $pattern->exec('aaaaa');
            expect($result->matched)->toBeTrue()
                ->and($result->match)->toBe('aa');
        });

        it('fails non-greedy quantifier when minimum not met', function (): void {
            $pattern = createPattern('a{3,}?');

            // Only 2 'a's, but minimum is 3
            expect($pattern->test('aa'))->toBeFalse();
        });

        it('fails non-greedy quantifier with backtracking when minimum not met', function (): void {
            $pattern = createPattern('a{2,}?b');

            // Only 1 'a', but minimum is 2
            expect($pattern->test('ab'))->toBeFalse();
        });

        it('prevents infinite loops on zero-width matches in greedy quantifiers', function (): void {
            // Lookhead assertions are zero-width
            $pattern = createPattern('(?=a)*b');

            expect($pattern->test('ab'))->toBeTrue();
        });

        it('prevents infinite loops on zero-width matches in backtracking quantifiers', function (): void {
            // Test with word boundaries which are zero-width
            $pattern = createPattern('\b+test');

            expect($pattern->test('test'))->toBeTrue();
        });

        it('handles empty pattern', function (): void {
            $pattern = createPattern('');

            expect($pattern->test(''))->toBeTrue()
                ->and($pattern->test('anything'))->toBeTrue();
        });

        it('handles lookbehind assertions at position', function (): void {
            $pattern = createPattern('(?<=a)b');

            expect($pattern->test('ab'))->toBeTrue()
                ->and($pattern->test('xb'))->toBeFalse()
                ->and($pattern->test('b'))->toBeFalse();
        });

        it('handles negative lookbehind assertions', function (): void {
            $pattern = createPattern('(?<!a)b');

            expect($pattern->test('xb'))->toBeTrue()
                ->and($pattern->test('ab'))->toBeFalse();
        });
    });

    describe('Defensive Edge Cases', function (): void {
        it('handles empty root node', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchRoot');

            $context = new MatchContext(0);
            $emptyRoot = ['type' => 'root']; // Missing 'child' key

            expect($method->invoke($matcher, $emptyRoot, $context))->toBeTrue();
        });

        it('handles malformed alternation node', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchAlternation');

            $context = new MatchContext(0);
            $malformedAlternation = ['type' => 'alternation']; // Missing 'alternatives' key

            expect($method->invoke($matcher, $malformedAlternation, $context))->toBeFalse();
        });

        it('handles malformed alternation with non-array alternatives', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchAlternation');

            $context = new MatchContext(0);
            $malformedAlternation = ['type' => 'alternation', 'alternatives' => 'not-an-array'];

            expect($method->invoke($matcher, $malformedAlternation, $context))->toBeFalse();
        });

        it('handles empty concatenation node', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchConcatenation');

            $context = new MatchContext(0);
            $emptyConcatenation = ['type' => 'concatenation']; // Missing 'children' key

            expect($method->invoke($matcher, $emptyConcatenation, $context))->toBeTrue();
        });

        it('handles concatenation with non-array children', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchConcatenation');

            $context = new MatchContext(0);
            $malformedConcatenation = ['type' => 'concatenation', 'children' => 'not-an-array'];

            expect($method->invoke($matcher, $malformedConcatenation, $context))->toBeTrue();
        });

        it('handles empty group node', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchGroup');

            $context = new MatchContext(0);
            $emptyGroup = ['type' => 'group', 'capturing' => false]; // Missing 'child' key

            expect($method->invoke($matcher, $emptyGroup, $context))->toBeTrue();
        });

        it('handles quantifier with null child', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchQuantifier');

            $context = new MatchContext(0);
            $quantifierWithNullChild = ['type' => 'quantifier', 'min' => 0, 'max' => null, 'child' => null];

            expect($method->invoke($matcher, $quantifierWithNullChild, $context))->toBeTrue();
        });

        it('handles unknown character class element type', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchCharacterClassElement');

            $unknownElement = ['type' => 'unknown-type'];

            expect($method->invoke($matcher, $unknownElement, 'a'))->toBeFalse();
        });

        it('handles zero-width match prevention in greedy quantifier', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchQuantifier');

            // Set input on matcher via reflection
            $inputProperty = $reflection->getProperty('input');
            $inputProperty->setValue($matcher, 'test');

            $inputLengthProperty = $reflection->getProperty('inputLength');
            $inputLengthProperty->setValue($matcher, 4);

            $context = new MatchContext(0);

            // Create a quantifier with a zero-width lookahead child
            $quantifierNode = [
                'type' => 'quantifier',
                'min' => 0,
                'max' => null,
                'greedy' => true,
                'child' => [
                    'type' => 'assertion',
                    'kind' => 'lookahead',
                    'positive' => true,
                    'child' => ['type' => 'literal', 'value' => 't'],
                ],
            ];

            // This should trigger the zero-width match prevention at line 612
            expect($method->invoke($matcher, $quantifierNode, $context))->toBeTrue();
        });

        it('handles quantifier with backtracking and null child', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchQuantifierWithBacktracking');

            $context = new MatchContext(0);
            $alternatives = [];
            $quantifierWithNullChild = ['type' => 'quantifier', 'min' => 0, 'max' => null, 'greedy' => true, 'child' => null];

            $result = $method->invokeArgs($matcher, [$quantifierWithNullChild, $context, &$alternatives]);

            expect($result)->toBeTrue();
        });

        it('handles non-greedy quantifier with minimum matches', function (): void {
            // This will test the non-greedy path with minimum > 0
            $pattern = createPattern('a{2,}?b');

            expect($pattern->test('aaab'))->toBeTrue();
        });

        it('handles assertion with null child', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchAssertion');

            $context = new MatchContext(0);
            $assertionWithNullChild = ['type' => 'assertion', 'kind' => 'lookahead', 'child' => null];

            expect($method->invoke($matcher, $assertionWithNullChild, $context))->toBeFalse();
        });

        it('handles lookbehind match from position zero', function (): void {
            // Pattern that requires lookbehind matching from position 0
            // The lookbehind (?<=abc) will match from position 0 to position 3
            $pattern = createPattern('(?<=abc)d');

            expect($pattern->test('abcd'))->toBeTrue()
                ->and($pattern->test('xabcd'))->toBeTrue() // matches because lookbehind finds 'abc' before 'd'
                ->and($pattern->test('abd'))->toBeFalse(); // no 'abc' before 'd'
        });

        it('handles lookbehind that matches entire prefix from position zero', function (): void {
            // This pattern forces the lookbehind to match from position 0 to current position
            // When matching at position 3, lookbehind (?<=xyz) must check if 'xyz' exists before it
            // This triggers the "match from position 0" branch in Matcher line 781
            $pattern = createPattern('(?<=xyz)a');

            expect($pattern->test('xyza'))->toBeTrue() // lookbehind matches 'xyz' from pos 0-3, then 'a' at pos 3
                ->and($pattern->test('abcxyza'))->toBeTrue() // also matches when 'xyz' appears later
                ->and($pattern->test('abca'))->toBeFalse(); // no 'xyz' before 'a'
        });

        it('handles lookbehind with very long prefix matching from position zero', function (): void {
            // Create a pattern with lookbehind > 100 characters to bypass the for loop
            // The for loop only checks back 100 positions, so this forces matching from position 0
            // This specifically triggers Matcher line 781
            $prefix = str_repeat('a', 105);
            $pattern = createPattern('(?<='.$prefix.')b');
            $input = $prefix.'b';

            expect($pattern->test($input))->toBeTrue();
        });

        it('throws exception for unsupported assertion kind', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchAssertion');

            $context = new MatchContext(0);
            $unsupportedAssertion = ['type' => 'assertion', 'kind' => 'unsupported-kind', 'child' => ['type' => 'literal', 'value' => 'a']];

            expect(fn (): mixed => $method->invoke($matcher, $unsupportedAssertion, $context))
                ->toThrow(UnsupportedAssertionKindException::class);
        });

        it('handles NodeInterface passed to matchNodeFromArray', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchNodeFromArray');

            // Set input on matcher via reflection
            $inputProperty = $reflection->getProperty('input');
            $inputProperty->setValue($matcher, 'test');

            $inputLengthProperty = $reflection->getProperty('inputLength');
            $inputLengthProperty->setValue($matcher, 4);

            $context = new MatchContext(0);
            $literalNode = new LiteralNode('t');

            expect($method->invoke($matcher, $literalNode, $context))->toBeTrue();
        });

        it('handles NodeInterface child in concatenation', function (): void {
            $matcher = resolve(MatcherInterface::class);
            $reflection = new ReflectionClass($matcher);
            $method = $reflection->getMethod('matchConcatenationWithBacktracking');

            // Set input on matcher via reflection
            $inputProperty = $reflection->getProperty('input');
            $inputProperty->setValue($matcher, 'test');

            $inputLengthProperty = $reflection->getProperty('inputLength');
            $inputLengthProperty->setValue($matcher, 4);

            $context = new MatchContext(0);
            $literalNode = new LiteralNode('t');
            $children = [$literalNode];

            expect($method->invoke($matcher, $children, $context, 0))->toBeTrue();
        });
    });
});
