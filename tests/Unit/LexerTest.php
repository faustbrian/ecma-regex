<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Enums\TokenType;

describe('Lexer', function (): void {
    beforeEach(function (): void {
        $this->lexer = resolve(LexerInterface::class);
    });

    describe('Literals', function (): void {
        it('tokenizes simple literals', function (): void {
            $tokens = $this->lexer->tokenize('abc');

            expect($tokens)->toHaveCount(4) // 3 literals + EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[0]->value())->toBe('a')
                ->and($tokens[1]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[1]->value())->toBe('b')
                ->and($tokens[2]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[2]->value())->toBe('c')
                ->and($tokens[3]->tokenType())->toBe(TokenType::Eof);
        });

        it('tokenizes numeric literals', function (): void {
            $tokens = $this->lexer->tokenize('123');

            expect($tokens)->toHaveCount(4)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[0]->value())->toBe('1')
                ->and($tokens[1]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[1]->value())->toBe('2')
                ->and($tokens[2]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[2]->value())->toBe('3');
        });
    });

    describe('Metacharacters', function (): void {
        it('tokenizes dot metacharacter', function (): void {
            $tokens = $this->lexer->tokenize('.');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Dot)
                ->and($tokens[0]->value())->toBe('.');
        });

        it('tokenizes caret anchor', function (): void {
            $tokens = $this->lexer->tokenize('^');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Caret)
                ->and($tokens[0]->value())->toBe('^');
        });

        it('tokenizes dollar anchor', function (): void {
            $tokens = $this->lexer->tokenize('$');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Dollar)
                ->and($tokens[0]->value())->toBe('$');
        });

        it('tokenizes pipe alternation', function (): void {
            $tokens = $this->lexer->tokenize('|');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Pipe)
                ->and($tokens[0]->value())->toBe('|');
        });
    });

    describe('Character Classes', function (): void {
        it('tokenizes simple character class', function (): void {
            $tokens = $this->lexer->tokenize('[abc]');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::CharacterClass)
                ->and($tokens[0]->value())->toBe('[abc]');
        });

        it('tokenizes negated character class', function (): void {
            $tokens = $this->lexer->tokenize('[^abc]');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::NegatedCharacterClass)
                ->and($tokens[0]->value())->toBe('[^abc]');
        });

        it('tokenizes character range', function (): void {
            $tokens = $this->lexer->tokenize('[a-z]');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::CharacterClass)
                ->and($tokens[0]->value())->toBe('[a-z]');
        });

        it('tokenizes numeric range', function (): void {
            $tokens = $this->lexer->tokenize('[0-9]');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::CharacterClass)
                ->and($tokens[0]->value())->toBe('[0-9]');
        });

        it('throws exception on unclosed character class', function (): void {
            expect(fn () => $this->lexer->tokenize('[abc'))
                ->toThrow(InvalidArgumentException::class, 'Unclosed character class');
        });

        it('handles escaped characters in character class', function (): void {
            $tokens = $this->lexer->tokenize('[a\-b]');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::CharacterClass)
                ->and($tokens[0]->value())->toBe('[a\-b]');
        });
    });

    describe('Quantifiers', function (): void {
        it('tokenizes asterisk quantifier', function (): void {
            $tokens = $this->lexer->tokenize('*');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Asterisk)
                ->and($tokens[0]->value())->toBe('*');
        });

        it('tokenizes plus quantifier', function (): void {
            $tokens = $this->lexer->tokenize('+');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Plus)
                ->and($tokens[0]->value())->toBe('+');
        });

        it('tokenizes question quantifier', function (): void {
            $tokens = $this->lexer->tokenize('?');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Question)
                ->and($tokens[0]->value())->toBe('?');
        });

        it('tokenizes exact count quantifier', function (): void {
            $tokens = $this->lexer->tokenize('{3}');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Quantifier)
                ->and($tokens[0]->value())->toBe('{3}');
        });

        it('tokenizes minimum count quantifier', function (): void {
            $tokens = $this->lexer->tokenize('{2,}');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Quantifier)
                ->and($tokens[0]->value())->toBe('{2,}');
        });

        it('tokenizes range quantifier', function (): void {
            $tokens = $this->lexer->tokenize('{2,5}');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Quantifier)
                ->and($tokens[0]->value())->toBe('{2,5}');
        });

        it('treats invalid quantifier as literal', function (): void {
            $tokens = $this->lexer->tokenize('{a}');

            expect($tokens)->toHaveCount(4)
                ->and($tokens[0]->tokenType())->toBe(TokenType::LeftBrace)
                ->and($tokens[1]->tokenType())->toBe(TokenType::Literal);
        });
    });

    describe('Groups', function (): void {
        it('tokenizes capturing group', function (): void {
            $tokens = $this->lexer->tokenize('(abc)');

            expect($tokens)->toHaveCount(6) // ( a b c ) EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::LeftParen)
                ->and($tokens[0]->value())->toBe('(')
                ->and($tokens[4]->tokenType())->toBe(TokenType::RightParen)
                ->and($tokens[4]->value())->toBe(')');
        });

        it('tokenizes non-capturing group', function (): void {
            $tokens = $this->lexer->tokenize('(?:abc)');

            expect($tokens)->toHaveCount(6) // (?: a b c ) EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::NonCapturingGroup)
                ->and($tokens[0]->value())->toBe('(?:');
        });

        it('tokenizes positive lookahead', function (): void {
            $tokens = $this->lexer->tokenize('(?=abc)');

            expect($tokens)->toHaveCount(6)
                ->and($tokens[0]->tokenType())->toBe(TokenType::PositiveLookahead)
                ->and($tokens[0]->value())->toBe('(?=');
        });

        it('tokenizes negative lookahead', function (): void {
            $tokens = $this->lexer->tokenize('(?!abc)');

            expect($tokens)->toHaveCount(6)
                ->and($tokens[0]->tokenType())->toBe(TokenType::NegativeLookahead)
                ->and($tokens[0]->value())->toBe('(?!');
        });

        it('tokenizes positive lookbehind', function (): void {
            $tokens = $this->lexer->tokenize('(?<=abc)');

            expect($tokens)->toHaveCount(6)
                ->and($tokens[0]->tokenType())->toBe(TokenType::PositiveLookbehind)
                ->and($tokens[0]->value())->toBe('(?<=');
        });

        it('tokenizes negative lookbehind', function (): void {
            $tokens = $this->lexer->tokenize('(?<!abc)');

            expect($tokens)->toHaveCount(6)
                ->and($tokens[0]->tokenType())->toBe(TokenType::NegativeLookbehind)
                ->and($tokens[0]->value())->toBe('(?<!');
        });

        it('throws exception on invalid group construct', function (): void {
            expect(fn () => $this->lexer->tokenize('(?x)'))
                ->toThrow(InvalidArgumentException::class, 'Invalid group construct');
        });

        it('throws exception on incomplete group construct', function (): void {
            expect(fn () => $this->lexer->tokenize('(?'))
                ->toThrow(InvalidArgumentException::class, 'Incomplete group construct');
        });

        it('throws exception on incomplete lookbehind construct', function (): void {
            expect(fn () => $this->lexer->tokenize('(?<'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception on invalid lookbehind construct', function (): void {
            expect(fn () => $this->lexer->tokenize('(?<x'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('tokenizes right bracket as literal', function (): void {
            $tokens = $this->lexer->tokenize(']');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::RightBracket)
                ->and($tokens[0]->value())->toBe(']');
        });
    });

    describe('Escape Sequences', function (): void {
        it('tokenizes digit escape', function (): void {
            $tokens = $this->lexer->tokenize('\d');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\d');
        });

        it('tokenizes word escape', function (): void {
            $tokens = $this->lexer->tokenize('\w');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\w');
        });

        it('tokenizes whitespace escape', function (): void {
            $tokens = $this->lexer->tokenize('\s');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\s');
        });

        it('tokenizes newline escape', function (): void {
            $tokens = $this->lexer->tokenize('\n');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\n');
        });

        it('tokenizes tab escape', function (): void {
            $tokens = $this->lexer->tokenize('\t');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\t');
        });

        it('tokenizes escaped metacharacters', function (): void {
            $tokens = $this->lexer->tokenize('\.');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\.');
        });

        it('tokenizes negated character class escapes', function (): void {
            $tokens = $this->lexer->tokenize('\D');

            expect($tokens)->toHaveCount(2)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[0]->value())->toBe('\D');
        });

        it('throws exception on incomplete escape', function (): void {
            expect(fn () => $this->lexer->tokenize('\\'))
                ->toThrow(InvalidArgumentException::class, 'Incomplete escape sequence');
        });
    });

    describe('Complex Patterns', function (): void {
        it('tokenizes pattern with multiple token types', function (): void {
            $tokens = $this->lexer->tokenize('^[a-z]+\d*$');

            expect($tokens)->toHaveCount(7) // ^ [a-z] + \d * $ EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::Caret)
                ->and($tokens[1]->tokenType())->toBe(TokenType::CharacterClass)
                ->and($tokens[2]->tokenType())->toBe(TokenType::Plus)
                ->and($tokens[3]->tokenType())->toBe(TokenType::Escape)
                ->and($tokens[4]->tokenType())->toBe(TokenType::Asterisk)
                ->and($tokens[5]->tokenType())->toBe(TokenType::Dollar)
                ->and($tokens[6]->tokenType())->toBe(TokenType::Eof);
        });

        it('tokenizes alternation pattern', function (): void {
            $tokens = $this->lexer->tokenize('a|b|c');

            expect($tokens)->toHaveCount(6) // a | b | c EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[1]->tokenType())->toBe(TokenType::Pipe)
                ->and($tokens[2]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[3]->tokenType())->toBe(TokenType::Pipe)
                ->and($tokens[4]->tokenType())->toBe(TokenType::Literal);
        });

        it('tokenizes nested groups', function (): void {
            $tokens = $this->lexer->tokenize('(a(b)c)');

            expect($tokens)->toHaveCount(8) // ( a ( b ) c ) EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::LeftParen)
                ->and($tokens[2]->tokenType())->toBe(TokenType::LeftParen)
                ->and($tokens[4]->tokenType())->toBe(TokenType::RightParen)
                ->and($tokens[6]->tokenType())->toBe(TokenType::RightParen);
        });
    });

    describe('Edge Cases', function (): void {
        it('tokenizes empty pattern', function (): void {
            $tokens = $this->lexer->tokenize('');

            expect($tokens)->toHaveCount(1)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Eof);
        });

        it('tokenizes pattern with only metacharacters', function (): void {
            $tokens = $this->lexer->tokenize('.*+?');

            expect($tokens)->toHaveCount(5)
                ->and($tokens[0]->tokenType())->toBe(TokenType::Dot)
                ->and($tokens[1]->tokenType())->toBe(TokenType::Asterisk)
                ->and($tokens[2]->tokenType())->toBe(TokenType::Plus)
                ->and($tokens[3]->tokenType())->toBe(TokenType::Question);
        });

        it('handles Unicode characters as literals', function (): void {
            $tokens = $this->lexer->tokenize('café');

            expect($tokens)->toHaveCount(5) // c a f é EOF
                ->and($tokens[0]->tokenType())->toBe(TokenType::Literal)
                ->and($tokens[3]->tokenType())->toBe(TokenType::Literal);
        });
    });
});
