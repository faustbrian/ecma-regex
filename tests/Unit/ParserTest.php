<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Ast\Nodes\AlternationNode;
use Cline\EcmaRegex\Ast\Nodes\AnchorNode;
use Cline\EcmaRegex\Ast\Nodes\AssertionNode;
use Cline\EcmaRegex\Ast\Nodes\CharacterClassNode;
use Cline\EcmaRegex\Ast\Nodes\ConcatenationNode;
use Cline\EcmaRegex\Ast\Nodes\DotNode;
use Cline\EcmaRegex\Ast\Nodes\GroupNode;
use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Ast\Nodes\QuantifierNode;
use Cline\EcmaRegex\Ast\Nodes\RootNode;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\Enums\NodeType;
use Cline\EcmaRegex\Enums\TokenType;
use Cline\EcmaRegex\Exceptions\UnexpectedTokenException;
use Cline\EcmaRegex\Support\Token;

describe('Parser', function (): void {
    beforeEach(function (): void {
        $this->parser = resolve(ParserInterface::class);
    });

    describe('Literals', function (): void {
        it('parses single literal', function (): void {
            $tokens = tokenize('a');
            $ast = $this->parser->parse($tokens);

            expect($ast)->toBeInstanceOf(RootNode::class)
                ->and($ast->type())->toBe(NodeType::Root);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(LiteralNode::class)
                ->and($child->value())->toBe('a');
        });

        it('parses multiple literals as concatenation', function (): void {
            $tokens = tokenize('abc');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);

            $children = $child->children();
            expect($children)->toHaveCount(3)
                ->and($children[0])->toBeInstanceOf(LiteralNode::class)
                ->and($children[1])->toBeInstanceOf(LiteralNode::class)
                ->and($children[2])->toBeInstanceOf(LiteralNode::class);
        });
    });

    describe('Metacharacters', function (): void {
        it('parses dot node', function (): void {
            $tokens = tokenize('.');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(DotNode::class)
                ->and($child->type())->toBe(NodeType::Dot);
        });

        it('parses caret anchor', function (): void {
            $tokens = tokenize('^a');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);

            $children = $child->children();
            expect($children[0])->toBeInstanceOf(AnchorNode::class)
                ->and($children[0]->anchorType())->toBe('start');
        });

        it('parses dollar anchor', function (): void {
            $tokens = tokenize('a$');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);

            $children = $child->children();
            expect($children[1])->toBeInstanceOf(AnchorNode::class)
                ->and($children[1]->anchorType())->toBe('end');
        });
    });

    describe('Character Classes', function (): void {
        it('parses simple character class', function (): void {
            $tokens = tokenize('[abc]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class)
                ->and($child->isNegated())->toBeFalse();
        });

        it('parses negated character class', function (): void {
            $tokens = tokenize('[^abc]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class)
                ->and($child->isNegated())->toBeTrue();
        });

        it('parses character range', function (): void {
            $tokens = tokenize('[a-z]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);
        });
    });

    describe('Quantifiers', function (): void {
        it('parses asterisk quantifier', function (): void {
            $tokens = tokenize('a*');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->min())->toBe(0)
                ->and($child->max())->toBeNull()
                ->and($child->greedy())->toBeTrue();
        });

        it('parses plus quantifier', function (): void {
            $tokens = tokenize('a+');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->min())->toBe(1)
                ->and($child->max())->toBeNull()
                ->and($child->greedy())->toBeTrue();
        });

        it('parses question quantifier', function (): void {
            $tokens = tokenize('a?');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->min())->toBe(0)
                ->and($child->max())->toBe(1)
                ->and($child->greedy())->toBeTrue();
        });

        it('parses exact count quantifier', function (): void {
            $tokens = tokenize('a{3}');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->min())->toBe(3)
                ->and($child->max())->toBe(3);
        });

        it('parses minimum count quantifier', function (): void {
            $tokens = tokenize('a{2,}');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->min())->toBe(2)
                ->and($child->max())->toBeNull();
        });

        it('parses range quantifier', function (): void {
            $tokens = tokenize('a{2,5}');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->min())->toBe(2)
                ->and($child->max())->toBe(5);
        });

        it('parses non-greedy quantifier', function (): void {
            $tokens = tokenize('a*?');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class)
                ->and($child->greedy())->toBeFalse();
        });
    });

    describe('Groups', function (): void {
        it('parses capturing group', function (): void {
            $tokens = tokenize('(abc)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(GroupNode::class)
                ->and($child->isCapturing())->toBeTrue()
                ->and($child->groupIndex())->toBe(1);
        });

        it('parses non-capturing group', function (): void {
            $tokens = tokenize('(?:abc)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(GroupNode::class)
                ->and($child->isCapturing())->toBeFalse();
        });

        it('parses positive lookahead', function (): void {
            $tokens = tokenize('(?=abc)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(AssertionNode::class)
                ->and($child->assertionType())->toBe('lookahead')
                ->and($child->isPositive())->toBeTrue();
        });

        it('parses negative lookahead', function (): void {
            $tokens = tokenize('(?!abc)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(AssertionNode::class)
                ->and($child->assertionType())->toBe('lookahead')
                ->and($child->isPositive())->toBeFalse();
        });

        it('parses nested groups', function (): void {
            $tokens = tokenize('(a(b)c)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(GroupNode::class)
                ->and($child->groupIndex())->toBe(1);
        });

        it('increments group indices correctly', function (): void {
            $tokens = tokenize('(a)(b)(c)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);

            $children = $child->children();
            expect($children[0]->groupIndex())->toBe(1)
                ->and($children[1]->groupIndex())->toBe(2)
                ->and($children[2]->groupIndex())->toBe(3);
        });
    });

    describe('Alternation', function (): void {
        it('parses simple alternation', function (): void {
            $tokens = tokenize('a|b');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(AlternationNode::class);

            $alternatives = $child->alternatives();
            expect($alternatives)->toHaveCount(2);
        });

        it('parses multiple alternations', function (): void {
            $tokens = tokenize('a|b|c');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(AlternationNode::class);

            $alternatives = $child->alternatives();
            expect($alternatives)->toHaveCount(3);
        });

        it('parses alternation with groups', function (): void {
            $tokens = tokenize('(a|b)');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(GroupNode::class);

            $groupChild = $child->child();
            expect($groupChild)->toBeInstanceOf(AlternationNode::class);
        });
    });

    describe('Complex Patterns', function (): void {
        it('parses pattern with multiple constructs', function (): void {
            $tokens = tokenize('^[a-z]+\d*$');
            $ast = $this->parser->parse($tokens);

            expect($ast)->toBeInstanceOf(RootNode::class);
            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);
        });

        it('parses email-like pattern', function (): void {
            $tokens = tokenize('[a-z]+@[a-z]+\.[a-z]+');
            $ast = $this->parser->parse($tokens);

            expect($ast)->toBeInstanceOf(RootNode::class);
        });

        it('parses pattern with quantified groups', function (): void {
            $tokens = tokenize('(ab)+');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class);

            $quantifiedChild = $child->child();
            expect($quantifiedChild)->toBeInstanceOf(GroupNode::class);
        });
    });

    describe('Edge Cases', function (): void {
        it('parses empty pattern', function (): void {
            $tokens = tokenize('');
            $ast = $this->parser->parse($tokens);

            expect($ast)->toBeInstanceOf(RootNode::class);
        });

        it('parses pattern with only anchors', function (): void {
            $tokens = tokenize('^$');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);
        });

        it('throws on unmatched parenthesis', function (): void {
            $tokens = tokenize('(abc');

            expect(fn () => $this->parser->parse($tokens))
                ->toThrow(RuntimeException::class);
        });

        it('throws on unexpected closing parenthesis', function (): void {
            $tokens = tokenize('abc)');

            expect(fn () => $this->parser->parse($tokens))
                ->toThrow(RuntimeException::class);
        });
    });

    describe('Operator Precedence', function (): void {
        it('respects precedence: quantifiers before concatenation', function (): void {
            $tokens = tokenize('ab+');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);

            $children = $child->children();
            expect($children[0])->toBeInstanceOf(LiteralNode::class)
                ->and($children[1])->toBeInstanceOf(QuantifierNode::class);
        });

        it('respects precedence: concatenation before alternation', function (): void {
            $tokens = tokenize('ab|cd');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(AlternationNode::class);

            $alternatives = $child->alternatives();
            expect($alternatives)->toHaveCount(2)
                ->and($alternatives[0])->toBeInstanceOf(ConcatenationNode::class)
                ->and($alternatives[1])->toBeInstanceOf(ConcatenationNode::class);
        });

        it('groups override precedence', function (): void {
            $tokens = tokenize('(a|b)c');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(ConcatenationNode::class);

            $children = $child->children();
            expect($children[0])->toBeInstanceOf(GroupNode::class)
                ->and($children[1])->toBeInstanceOf(LiteralNode::class);
        });
    });

    describe('Character Class Escapes', function (): void {
        it('parses digit escape in character class', function (): void {
            $tokens = tokenize('[a\d]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);

            $elements = $child->elements();
            expect($elements)->toHaveCount(2); // 'a' literal + digit range (0-9)
        });

        it('parses word escape in character class', function (): void {
            $tokens = tokenize('[\w]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);

            $elements = $child->elements();
            expect($elements)->toHaveCount(4); // a-z, A-Z, 0-9, _
        });

        it('parses whitespace escape in character class', function (): void {
            $tokens = tokenize('[\s]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);

            $elements = $child->elements();
            expect($elements)->toHaveCount(4); // space, tab, newline, carriage return
        });

        it('parses negated digit escape in character class', function (): void {
            $tokens = tokenize('[\D]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);
        });

        it('parses negated word escape in character class', function (): void {
            $tokens = tokenize('[\W]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);
        });

        it('parses negated whitespace escape in character class', function (): void {
            $tokens = tokenize('[\S]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);
        });
    });

    describe('Quantifier Variations', function (): void {
        it('parses non-greedy plus quantifier', function (): void {
            $tokens = tokenize('a+?');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(QuantifierNode::class);

            expect($child->min())->toBe(1)
                ->and($child->max())->toBeNull()
                ->and($child->greedy())->toBeFalse();
        });
    });

    describe('Character Range Edge Cases', function (): void {
        it('parses character range with hyphen at end', function (): void {
            $tokens = tokenize('[a-]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);

            // Should have 'a' and '-' as separate literals
            $elements = $child->elements();
            expect($elements)->toHaveCount(2);
        });

        it('parses hyphen followed by escape', function (): void {
            $tokens = tokenize('[a\-\d]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);

            // Should have 'a', '-', and digit range
            $elements = $child->elements();
            expect($elements)->toHaveCount(3);
        });

        it('handles invalid character range where end < start', function (): void {
            $tokens = tokenize('[z-a]');
            $ast = $this->parser->parse($tokens);

            $child = $ast->child();
            expect($child)->toBeInstanceOf(CharacterClassNode::class);

            // Invalid range gets parsed as literals: 'z', '-', 'a'
            $elements = $child->elements();
            expect($elements)->toHaveCount(3);
        });
    });

    describe('Error Handling', function (): void {
        it('throws UnexpectedTokenException for unexpected tokens in atom position', function (): void {
            // Create a token stream with RightParen where an atom is expected
            $tokens = [
                new Token(TokenType::RightParen, ')', 0),
                new Token(TokenType::Eof, '', 1),
            ];

            expect(fn () => $this->parser->parse($tokens))
                ->toThrow(UnexpectedTokenException::class);
        });

        it('throws UnexpectedTokenException for quantifier in atom position', function (): void {
            // Create a token stream with Asterisk where an atom is expected
            // This will force parseAtom to fall through to the UnexpectedTokenException at lines 244-247
            $tokens = [
                new Token(TokenType::Asterisk, '*', 0),
                new Token(TokenType::Eof, '', 1),
            ];

            expect(fn () => $this->parser->parse($tokens))
                ->toThrow(UnexpectedTokenException::class);
        });

        it('throws UnexpectedTokenException for right bracket in atom position', function (): void {
            // Create a token stream with RightBracket where an atom is expected
            // RightBracket is only valid inside character classes, so this triggers parseAtom exception
            $tokens = [
                new Token(TokenType::RightBracket, ']', 0),
                new Token(TokenType::Eof, '', 1),
            ];

            expect(fn () => $this->parser->parse($tokens))
                ->toThrow(UnexpectedTokenException::class);
        });
    });
});
