<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Parser;

use Cline\EcmaRegex\Ast\Nodes\AlternationNode;
use Cline\EcmaRegex\Ast\Nodes\AnchorNode;
use Cline\EcmaRegex\Ast\Nodes\AssertionNode;
use Cline\EcmaRegex\Ast\Nodes\BackreferenceNode;
use Cline\EcmaRegex\Ast\Nodes\CharacterClassNode;
use Cline\EcmaRegex\Ast\Nodes\CharacterRangeNode;
use Cline\EcmaRegex\Ast\Nodes\ConcatenationNode;
use Cline\EcmaRegex\Ast\Nodes\DotNode;
use Cline\EcmaRegex\Ast\Nodes\GroupNode;
use Cline\EcmaRegex\Ast\Nodes\LiteralNode;
use Cline\EcmaRegex\Ast\Nodes\QuantifierNode;
use Cline\EcmaRegex\Ast\Nodes\RootNode;
use Cline\EcmaRegex\Contracts\NodeInterface;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\Contracts\TokenInterface;
use Cline\EcmaRegex\Enums\TokenType;
use Cline\EcmaRegex\Exceptions\MissingTokenException;
use Cline\EcmaRegex\Exceptions\UnexpectedTokenException;

use function count;
use function explode;
use function is_numeric;
use function mb_ord;
use function mb_strlen;
use function mb_substr;
use function mb_trim;
use function str_contains;

/**
 * Parses ECMA-262 regex tokens into an Abstract Syntax Tree.
 *
 * Implements a recursive descent parser that converts a stream of tokens
 * from the lexer into a hierarchical AST structure. The parser handles:
 * - Alternation (|) with lowest precedence
 * - Concatenation with middle precedence
 * - Atomic expressions (literals, groups, classes) with highest precedence
 * - Quantifiers (*, +, ?, {n,m}) applied to atoms
 * - Lookahead and lookbehind assertions
 * - Capturing and non-capturing groups
 * - Character classes with ranges and escapes
 *
 * Uses operator precedence to correctly parse complex patterns without
 * ambiguity. Tracks capturing group numbers for backreferences.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Parser implements ParserInterface
{
    /**
     * The array of tokens to parse.
     *
     * @var array<int, TokenInterface>
     */
    private array $tokens;

    /**
     * The current position in the token stream.
     */
    private int $position = 0;

    /**
     * The number of capturing groups encountered so far.
     *
     * Used to assign sequential numbers to capturing groups for
     * backreference resolution.
     */
    private int $groupCount = 0;

    /**
     * Parse tokens into an Abstract Syntax Tree.
     *
     * Converts a flat array of tokens into a hierarchical tree structure
     * representing the regex pattern. The root node contains the entire
     * pattern, with child nodes for alternations, concatenations, and
     * atomic expressions.
     *
     * @param array<int, TokenInterface> $tokens The tokens to parse
     *
     * @throws UnexpectedTokenException If tokens remain after parsing completes
     * @return NodeInterface            The root node of the AST
     */
    public function parse(array $tokens): NodeInterface
    {
        $this->tokens = $tokens;
        $this->position = 0;
        $this->groupCount = 0;

        $root = $this->parseAlternation();

        if (!$this->isAtEnd()) {
            throw UnexpectedTokenException::atPosition(
                $this->current()->type(),
                $this->current()->position(),
            );
        }

        return new RootNode($root);
    }

    /**
     * Parse alternation (lowest precedence: a|b|c).
     *
     * Alternation has the lowest precedence in regex patterns. This method
     * parses one or more alternatives separated by the pipe (|) operator.
     * If only one alternative is present, returns that node directly rather
     * than wrapping it in an AlternationNode.
     *
     * @return NodeInterface The alternation node or single alternative
     */
    private function parseAlternation(): NodeInterface
    {
        $alternatives = [$this->parseConcatenation()];

        while ($this->match(TokenType::Pipe)) {
            $alternatives[] = $this->parseConcatenation();
        }

        if (count($alternatives) === 1) {
            return $alternatives[0];
        }

        return new AlternationNode($alternatives);
    }

    /**
     * Parse concatenation (middle precedence: abc).
     *
     * Concatenation has middle precedence, higher than alternation but lower
     * than atoms. Parses a sequence of atoms (optionally quantified) that
     * must match consecutively. Returns empty literal for empty alternatives.
     *
     * @return NodeInterface The concatenation node, single atom, or empty literal
     */
    private function parseConcatenation(): NodeInterface
    {
        $children = [];

        while (!$this->isAtEnd() && !$this->check(TokenType::Pipe) && !$this->check(TokenType::RightParen)) {
            $atom = $this->parseAtom();
            $quantified = $this->parseQuantifier($atom);
            $children[] = $quantified;
        }

        if ($children === []) {
            // Empty alternative (e.g., in "a|")
            return new LiteralNode('');
        }

        if (count($children) === 1) {
            return $children[0];
        }

        return new ConcatenationNode($children);
    }

    /**
     * Parse atomic expressions (highest precedence).
     *
     * Atoms are the basic building blocks of regex patterns: literals,
     * character classes, groups, assertions, and special characters like
     * dot and anchors. This method dispatches to specialized parsers based
     * on the current token type.
     *
     * @throws UnexpectedTokenException If the current token cannot start an atom
     * @return NodeInterface            The parsed atomic node
     */
    private function parseAtom(): NodeInterface
    {
        // Dot wildcard
        if ($this->match(TokenType::Dot)) {
            return new DotNode();
        }

        // Anchors
        if ($this->match(TokenType::Caret)) {
            return new AnchorNode('start');
        }

        if ($this->match(TokenType::Dollar)) {
            return new AnchorNode('end');
        }

        // Character classes (from Lexer)
        if ($this->match(TokenType::CharacterClass)) {
            return $this->parseCharacterClassFromToken(false);
        }

        if ($this->match(TokenType::NegatedCharacterClass)) {
            return $this->parseCharacterClassFromToken(true);
        }

        // Groups and assertions
        if ($this->match(TokenType::LeftParen)) {
            return $this->parseGroup();
        }

        // Positive lookahead
        if ($this->match(TokenType::PositiveLookahead)) {
            return $this->parseAssertion('lookahead', true);
        }

        // Negative lookahead
        if ($this->match(TokenType::NegativeLookahead)) {
            return $this->parseAssertion('lookahead', false);
        }

        // Positive lookbehind
        if ($this->match(TokenType::PositiveLookbehind)) {
            return $this->parseAssertion('lookbehind', true);
        }

        // Negative lookbehind
        if ($this->match(TokenType::NegativeLookbehind)) {
            return $this->parseAssertion('lookbehind', false);
        }

        // Non-capturing group
        if ($this->match(TokenType::NonCapturingGroup)) {
            return $this->parseNonCapturingGroup();
        }

        // Escape sequences (backreferences and escaped chars)
        if ($this->match(TokenType::Escape)) {
            return $this->parseEscape();
        }

        // Literal character
        if ($this->match(TokenType::Literal)) {
            return new LiteralNode($this->previous()->value());
        }

        throw UnexpectedTokenException::atPosition(
            $this->current()->type(),
            $this->current()->position(),
        );
    }

    /**
     * Parse quantifiers (*, +, ?, {n,m}) and apply them to a node.
     *
     * Quantifiers specify how many times the preceding atom should match:
     * - * matches 0 or more times
     * - + matches 1 or more times
     * - ? matches 0 or 1 time
     * - {n,m} matches between n and m times
     *
     * By default, quantifiers are greedy (match as much as possible).
     * Appending ? makes them non-greedy (match as little as possible).
     *
     * @param NodeInterface $node The node to apply the quantifier to
     *
     * @return NodeInterface The quantified node, or the original node if no quantifier
     */
    private function parseQuantifier(NodeInterface $node): NodeInterface
    {
        $greedy = true;

        // * quantifier (0 or more)
        if ($this->match(TokenType::Asterisk)) {
            if ($this->match(TokenType::Question)) {
                $greedy = false;
            }

            return new QuantifierNode($node, 0, null, $greedy);
        }

        // + quantifier (1 or more)
        if ($this->match(TokenType::Plus)) {
            if ($this->match(TokenType::Question)) {
                $greedy = false;
            }

            return new QuantifierNode($node, 1, null, $greedy);
        }

        // ? quantifier (0 or 1)
        if ($this->match(TokenType::Question)) {
            /**
             * Check for non-greedy ?? pattern
             * @phpstan-ignore booleanNot.alwaysFalse
             */
            $greedy = !$this->match(TokenType::Question);

            return new QuantifierNode($node, 0, 1, $greedy);
        }

        // {n,m} quantifier
        if ($this->match(TokenType::Quantifier)) {
            $quantifier = $this->previous()->value();
            [$min, $max] = $this->parseQuantifierRange($quantifier);

            /**
             * Check for non-greedy quantifier (e.g., {2,5}?)
             * @phpstan-ignore booleanNot.alwaysTrue
             */
            $greedy = !$this->match(TokenType::Question);

            return new QuantifierNode($node, $min, $max, $greedy);
        }

        return $node;
    }

    /**
     * Parse the range from a {n,m} quantifier.
     *
     * Parses quantifier syntax like {3}, {2,5}, or {1,} into minimum
     * and maximum repetition counts. If max is omitted (e.g., {1,}),
     * returns null for unbounded maximum.
     *
     * @param string $quantifier The quantifier string including braces (e.g., "{2,5}")
     *
     * @return array{int, null|int} Array containing [min, max] where max is null for unbounded
     */
    private function parseQuantifierRange(string $quantifier): array
    {
        // Remove braces
        $quantifier = mb_trim($quantifier, '{}');

        if (str_contains($quantifier, ',')) {
            [$min, $max] = explode(',', $quantifier);
            $min = (int) mb_trim($min);
            $max = mb_trim($max) === '' ? null : (int) mb_trim($max);
        } else {
            $min = (int) $quantifier;
            $max = (int) $quantifier;
        }

        return [$min, $max];
    }

    /**
     * Parse character class from pre-tokenized character class token.
     * The token value is the full character class like "[abc]" or "[^abc]".
     */
    private function parseCharacterClassFromToken(bool $negated): NodeInterface
    {
        $value = $this->previous()->value();

        // Remove the brackets and optional negation caret
        $content = mb_trim($value, '[]');

        if ($negated && $content !== '' && $content[0] === '^') {
            $content = mb_substr($content, 1);
        }

        $elements = [];
        $i = 0;
        $len = mb_strlen($content, 'UTF-8');

        while ($i < $len) {
            $char = mb_substr($content, $i, 1, 'UTF-8');

            // Handle escape sequences
            if ($char === '\\' && $i + 1 < $len) {
                $nextChar = mb_substr($content, $i + 1, 1, 'UTF-8');

                // Expand character class escapes (\d, \w, \s, etc.)
                $expanded = match ($nextChar) {
                    'd' => [new CharacterRangeNode('0', '9')],
                    'D' => null, // Negated, handle specially
                    'w' => [
                        new CharacterRangeNode('a', 'z'),
                        new CharacterRangeNode('A', 'Z'),
                        new CharacterRangeNode('0', '9'),
                        new LiteralNode('_'),
                    ],
                    'W' => null, // Negated, handle specially
                    's' => [
                        new LiteralNode(' '),
                        new LiteralNode("\t"),
                        new LiteralNode("\n"),
                        new LiteralNode("\r"),
                    ],
                    'S' => null, // Negated, handle specially
                    default => [new LiteralNode($nextChar)],
                };

                if ($expanded !== null) {
                    foreach ($expanded as $node) {
                        $elements[] = $node;
                    }
                } else {
                    // For negated classes like \D, \W, \S in character class, just treat as literal for now
                    // TODO: Handle negated escapes in character classes properly
                    $elements[] = new LiteralNode($nextChar);
                }

                $i += 2;

                continue;
            }

            // Check for range (a-z)
            // A hyphen creates a range only if:
            // 1. It's not at the end of the class (i + 2 < $len ensures there's a character after -)
            // 2. It's followed by a valid character (not backslash for escape)
            // 3. The range is valid (start <= end in character codes)
            if ($i + 2 < $len && mb_substr($content, $i + 1, 1, 'UTF-8') === '-') {
                $endChar = mb_substr($content, $i + 2, 1, 'UTF-8');

                // Only create a range if:
                // - End character is not a backslash (which would be an escape)
                // - The range is valid (start code <= end code)
                if ($endChar !== '\\') {
                    $startCode = mb_ord($char, 'UTF-8');
                    $endCode = mb_ord($endChar, 'UTF-8');

                    // Check if this is a valid range (start <= end)
                    if ($startCode <= $endCode) {
                        // Valid range
                        $elements[] = new CharacterRangeNode($char, $endChar);
                        $i += 3;
                    } else {
                        // Invalid range (end < start), treat as literals
                        $elements[] = new LiteralNode($char);
                        ++$i;
                    }
                } else {
                    // Hyphen followed by escape, treat hyphen as literal
                    $elements[] = new LiteralNode($char);
                    ++$i;
                }
            } else {
                $elements[] = new LiteralNode($char);
                ++$i;
            }
        }

        return new CharacterClassNode($elements, $negated);
    }

    /**
     * Parse capturing group (...).
     */
    private function parseGroup(): NodeInterface
    {
        ++$this->groupCount;
        $groupNumber = $this->groupCount;

        $child = $this->parseAlternation();

        $this->consume(TokenType::RightParen, 'Expected )');

        return new GroupNode($child, true, $groupNumber);
    }

    /**
     * Parse non-capturing group (?:...).
     */
    private function parseNonCapturingGroup(): NodeInterface
    {
        $child = $this->parseAlternation();

        $this->consume(TokenType::RightParen, 'Expected )');

        return new GroupNode($child, false);
    }

    /**
     * Parse assertion (?=...) or (?!...).
     */
    private function parseAssertion(string $kind, bool $positive): NodeInterface
    {
        $child = $this->parseAlternation();

        $this->consume(TokenType::RightParen, 'Expected )');

        return new AssertionNode($child, $kind, $positive);
    }

    /**
     * Parse escape sequences (backreferences and escaped characters).
     */
    private function parseEscape(): NodeInterface
    {
        $value = $this->previous()->value();

        // Remove the leading backslash
        if ($value !== '' && $value[0] === '\\') {
            $value = mb_substr($value, 1);
        }

        // Check if it's a backreference (\1, \2, etc.)
        if (is_numeric($value) && (int) $value > 0) {
            return new BackreferenceNode((int) $value);
        }

        // Handle word boundary assertions
        if ($value === 'b') {
            return new AssertionNode(
                new LiteralNode(''),
                'word_boundary',
                true,
            );
        }

        if ($value === 'B') {
            return new AssertionNode(
                new LiteralNode(''),
                'word_boundary',
                false,
            );
        }

        // Handle character class escapes
        return match ($value) {
            'd' => new CharacterClassNode([new CharacterRangeNode('0', '9')], false),
            'D' => new CharacterClassNode([new CharacterRangeNode('0', '9')], true),
            'w' => new CharacterClassNode([
                new CharacterRangeNode('a', 'z'),
                new CharacterRangeNode('A', 'Z'),
                new CharacterRangeNode('0', '9'),
                new LiteralNode('_'),
            ], false),
            'W' => new CharacterClassNode([
                new CharacterRangeNode('a', 'z'),
                new CharacterRangeNode('A', 'Z'),
                new CharacterRangeNode('0', '9'),
                new LiteralNode('_'),
            ], true),
            's' => new CharacterClassNode([
                new LiteralNode(' '),
                new LiteralNode("\t"),
                new LiteralNode("\n"),
                new LiteralNode("\r"),
            ], false),
            'S' => new CharacterClassNode([
                new LiteralNode(' '),
                new LiteralNode("\t"),
                new LiteralNode("\n"),
                new LiteralNode("\r"),
            ], true),
            default => new LiteralNode($value),
        };
    }

    /**
     * Check if current token matches the given type.
     */
    private function check(TokenType $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->current()->type() === $type->value;
    }

    /**
     * Consume current token if it matches the type, otherwise throw error.
     */
    private function match(TokenType $type): bool
    {
        if ($this->check($type)) {
            $this->advance();

            return true;
        }

        return false;
    }

    /**
     * Consume token or throw error.
     */
    private function consume(TokenType $type, string $message): TokenInterface
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        throw MissingTokenException::atPosition(
            $message,
            $this->current()->position(),
        );
    }

    /**
     * Advance to next token.
     */
    private function advance(): TokenInterface
    {
        if (!$this->isAtEnd()) {
            ++$this->position;
        }

        return $this->previous();
    }

    /**
     * Get current token.
     */
    private function current(): TokenInterface
    {
        return $this->tokens[$this->position];
    }

    /**
     * Get previous token.
     */
    private function previous(): TokenInterface
    {
        return $this->tokens[$this->position - 1];
    }

    /**
     * Check if at end of tokens.
     */
    private function isAtEnd(): bool
    {
        return $this->position >= count($this->tokens) || $this->current()->type() === TokenType::Eof->value;
    }
}
