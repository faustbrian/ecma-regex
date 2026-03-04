<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Lexer;

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Contracts\TokenInterface;
use Cline\EcmaRegex\Enums\TokenType;
use Cline\EcmaRegex\Exceptions\IncompleteEscapeSequenceException;
use Cline\EcmaRegex\Exceptions\IncompleteGroupConstructException;
use Cline\EcmaRegex\Exceptions\IncompleteLookbehindConstructException;
use Cline\EcmaRegex\Exceptions\InvalidGroupConstructException;
use Cline\EcmaRegex\Exceptions\InvalidLookbehindConstructException;
use Cline\EcmaRegex\Exceptions\UnclosedCharacterClassException;
use Cline\EcmaRegex\Support\Token;

use function ctype_digit;
use function in_array;
use function mb_strlen;
use function mb_substr;
use function preg_match;

/**
 * Tokenizes an ECMA-262 regex pattern into a sequence of tokens for parsing.
 *
 * Implements ECMA-262 section 21.2.1 Pattern grammar, performing lexical analysis
 * of regex patterns. The lexer scans the pattern character by character, recognizing
 * literals, metacharacters, escape sequences, character classes, groups, quantifiers,
 * and other regex constructs, converting them into typed tokens for consumption by
 * the parser.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Lexer implements LexerInterface
{
    /**
     * The regex pattern being tokenized.
     */
    private string $pattern;

    /**
     * The current zero-based position in the pattern during scanning.
     */
    private int $position;

    /**
     * The total character length of the pattern (multibyte-aware).
     */
    private int $length;

    /**
     * The collected tokens generated during lexical analysis.
     *
     * @var array<int, TokenInterface>
     */
    private array $tokens;

    /**
     * Tokenize a regex pattern into a sequence of tokens.
     *
     * Performs complete lexical analysis of the input pattern, scanning from start
     * to end and generating tokens for each recognized construct. Automatically appends
     * an EOF token at the end of the token stream to mark the end of input.
     *
     * @param  string                                 $pattern The ECMA-262 regex pattern string to tokenize
     * @throws IncompleteEscapeSequenceException      When an escape sequence is incomplete
     * @throws IncompleteGroupConstructException      When a group construct is incomplete
     * @throws IncompleteLookbehindConstructException When a lookbehind construct is incomplete
     * @throws InvalidGroupConstructException         When a group construct has invalid syntax
     * @throws InvalidLookbehindConstructException    When a lookbehind construct has invalid syntax
     * @throws UnclosedCharacterClassException        When a character class is not properly closed
     * @return array<int, TokenInterface>             Array of tokens representing the lexical structure of the pattern
     */
    public function tokenize(string $pattern): array
    {
        $this->pattern = $pattern;
        $this->position = 0;
        $this->length = mb_strlen($pattern);
        $this->tokens = [];

        while ($this->position < $this->length) {
            $this->scanToken();
        }

        // Add EOF token
        $this->tokens[] = new Token(TokenType::Eof, '', $this->position);

        return $this->tokens;
    }

    /**
     * Scan a single token from the current position.
     *
     * Examines the current character and dispatches to the appropriate specialized
     * scanning method based on the character type. Handles all regex metacharacters
     * and treats unrecognized characters as literals.
     *
     * @throws IncompleteEscapeSequenceException      When scanning an incomplete escape sequence
     * @throws IncompleteGroupConstructException      When scanning an incomplete group construct
     * @throws IncompleteLookbehindConstructException When scanning an incomplete lookbehind
     * @throws InvalidGroupConstructException         When scanning a group with invalid syntax
     * @throws InvalidLookbehindConstructException    When scanning a lookbehind with invalid syntax
     * @throws UnclosedCharacterClassException        When scanning a character class that is not closed
     */
    private function scanToken(): void
    {
        $char = $this->peek();

        match ($char) {
            '.' => $this->addToken(TokenType::Dot, '.'),
            '^' => $this->addToken(TokenType::Caret, '^'),
            '$' => $this->addToken(TokenType::Dollar, '$'),
            '*' => $this->addToken(TokenType::Asterisk, '*'),
            '+' => $this->addToken(TokenType::Plus, '+'),
            '?' => $this->scanQuestionMark(),
            '|' => $this->addToken(TokenType::Pipe, '|'),
            '(' => $this->scanGroup(),
            ')' => $this->addToken(TokenType::RightParen, ')'),
            '[' => $this->scanCharacterClass(),
            ']' => $this->addToken(TokenType::RightBracket, ']'),
            '{' => $this->scanQuantifier(),
            '}' => $this->addToken(TokenType::RightBrace, '}'),
            '\\' => $this->scanEscape(),
            default => $this->addToken(TokenType::Literal, $char),
        };
    }

    /**
     * Scan a question mark token.
     *
     * The question mark can represent either a zero-or-one quantifier (e.g., 'a?')
     * or a non-greedy modifier following another quantifier (e.g., '*?', '+?').
     * The parser is responsible for interpreting the semantic meaning based on context.
     */
    private function scanQuestionMark(): void
    {
        $this->addToken(TokenType::Question, '?');
    }

    /**
     * Scan a group or special group construct.
     *
     * Recognizes and tokenizes all ECMA-262 group types:
     * - Capturing groups: (...)
     * - Non-capturing groups: (?:...)
     * - Positive lookahead: (?=...)
     * - Negative lookahead: (?!...)
     * - Positive lookbehind: (?<=...)
     * - Negative lookbehind: (?<!...)
     *
     * @throws IncompleteGroupConstructException      When the group construct is incomplete
     * @throws IncompleteLookbehindConstructException When the lookbehind construct is incomplete
     * @throws InvalidGroupConstructException         When the group syntax is invalid
     * @throws InvalidLookbehindConstructException    When the lookbehind syntax is invalid
     */
    private function scanGroup(): void
    {
        $start = $this->position;
        $this->advance(); // Consume '('

        if ($this->peek() === '?') {
            $this->advance(); // Consume '?'

            $next = $this->peek();

            if ($next === '') {
                throw IncompleteGroupConstructException::atPosition($start);
            }

            // Handle lookbehind: (?<= or (?<!
            if ($next === '<') {
                $this->advance(); // Consume '<'
                $assertion = $this->peek();

                if ($assertion === '') {
                    throw IncompleteLookbehindConstructException::atPosition($start);
                }

                match ($assertion) {
                    '=' => $this->addTokenAt(TokenType::PositiveLookbehind, '(?<=', $start),
                    '!' => $this->addTokenAt(TokenType::NegativeLookbehind, '(?<!', $start),
                    default => throw InvalidLookbehindConstructException::atPosition($assertion, $start),
                };

                $this->advance(); // Consume the assertion character

                return;
            }

            match ($next) {
                ':' => $this->addTokenAt(TokenType::NonCapturingGroup, '(?:', $start),
                '=' => $this->addTokenAt(TokenType::PositiveLookahead, '(?=', $start),
                '!' => $this->addTokenAt(TokenType::NegativeLookahead, '(?!', $start),
                default => throw InvalidGroupConstructException::atPosition($next, $start),
            };

            $this->advance(); // Consume the special character
        } else {
            $this->addTokenAt(TokenType::LeftParen, '(', $start);
        }
    }

    /**
     * Scan a character class construct.
     *
     * Recognizes and tokenizes character class constructs including:
     * - Regular character classes: [abc]
     * - Negated character classes: [^abc]
     * - Character ranges: [a-z]
     * - Escaped characters within classes: [\d\w]
     *
     * Consumes all content between the opening '[' and closing ']' brackets,
     * preserving escape sequences and validating proper closure.
     *
     * @throws UnclosedCharacterClassException When the character class is not properly closed
     */
    private function scanCharacterClass(): void
    {
        $start = $this->position;
        $this->advance(); // Consume '['

        // Check for negation
        $isNegated = false;

        if ($this->peek() === '^') {
            $isNegated = true;
            $this->advance();
        }

        $content = '';
        $hasClosing = false;

        while ($this->position < $this->length) {
            $char = $this->peek();

            if ($char === ']') {
                $hasClosing = true;
                $this->advance();

                break;
            }

            if ($char === '\\') {
                $this->advance();

                if ($this->position < $this->length) {
                    $content .= '\\'.$this->peek();
                    $this->advance();
                }
            } else {
                $content .= $char;
                $this->advance();
            }
        }

        if (!$hasClosing) {
            throw UnclosedCharacterClassException::atPosition($start);
        }

        $type = $isNegated ? TokenType::NegatedCharacterClass : TokenType::CharacterClass;
        $value = '['.($isNegated ? '^' : '').$content.']';
        $this->addTokenAt($type, $value, $start);
    }

    /**
     * Scan a quantifier construct.
     *
     * Recognizes and tokenizes quantifier constructs:
     * - Exact count: {n}
     * - Minimum count: {n,}
     * - Range: {n,m}
     *
     * If the braces do not form a valid quantifier (missing closing brace or
     * invalid format), treats the opening '{' as a literal character instead.
     * This permissive behavior matches ECMA-262 specification for backward
     * compatibility with legacy patterns.
     */
    private function scanQuantifier(): void
    {
        $start = $this->position;
        $this->advance(); // Consume '{'

        $content = '';
        $hasClosing = false;
        $isValidQuantifier = true;

        while ($this->position < $this->length) {
            $char = $this->peek();

            if ($char === '}') {
                $hasClosing = true;
                $this->advance();

                break;
            }

            if (!ctype_digit($char) && $char !== ',') {
                // Not a valid quantifier, treat '{' as literal
                $isValidQuantifier = false;

                break;
            }

            $content .= $char;
            $this->advance();
        }

        // If no closing brace or invalid format, treat '{' as literal
        if (!$hasClosing || !$isValidQuantifier || !preg_match('/^\d+(?:,\d*)?$/', $content)) {
            // Reset position to just after '{'
            $this->position = $start + 1;
            $this->addTokenAt(TokenType::LeftBrace, '{', $start);

            return;
        }

        $this->addTokenAt(TokenType::Quantifier, '{'.$content.'}', $start);
    }

    /**
     * Scan an escape sequence.
     *
     * Recognizes and tokenizes all ECMA-262 escape sequences:
     * - Character escapes: \n, \r, \t, \v, \f, \0
     * - Character class escapes: \d, \w, \s, \D, \W, \S
     * - Assertion escapes: \b, \B
     * - Literal escapes: \., \*, \+, \?, etc.
     * - Hexadecimal escapes: \xNN
     * - Unicode escapes: \uNNNN
     * - Backreference escapes: \1, \2, etc.
     *
     * Per ECMA-262, allows escaping any character, even if not strictly necessary,
     * providing permissive handling for maximum pattern compatibility.
     *
     * @throws IncompleteEscapeSequenceException When the escape sequence is incomplete (pattern ends with backslash)
     */
    private function scanEscape(): void
    {
        $start = $this->position;
        $this->advance(); // Consume '\'

        if ($this->position >= $this->length) {
            throw IncompleteEscapeSequenceException::atPosition($start);
        }

        $char = $this->peek();
        $value = '\\'.$char;

        // Validate escape sequence
        $validEscapes = [
            // Character escapes
            'n', 'r', 't', 'v', 'f', '0',
            // Character class escapes
            'd', 'D', 'w', 'W', 's', 'S',
            // Assertion escapes
            'b', 'B',
            // Literal escapes (metacharacters)
            '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '^', '$', '\\', '/',
            // Hexadecimal and Unicode escapes
            'x', 'u',
        ];

        if (!in_array($char, $validEscapes, true) && !ctype_digit($char)) {
            // Allow escaping any character, but record it as an escape
            // This is permissive per ECMA-262
        }

        $this->advance();
        $this->addTokenAt(TokenType::Escape, $value, $start);
    }

    /**
     * Peek at the current character without advancing the position.
     *
     * Uses multibyte-safe string operations to support Unicode patterns correctly.
     * Returns an empty string when attempting to peek beyond the end of the pattern.
     *
     * @return string The current character, or empty string if at end of pattern
     */
    private function peek(): string
    {
        if ($this->position >= $this->length) {
            return '';
        }

        return mb_substr($this->pattern, $this->position, 1, 'UTF-8');
    }

    /**
     * Advance the position by one character.
     *
     * Increments the internal position counter to move forward in the pattern.
     * Does not perform bounds checking - callers should use peek() to verify
     * position validity before advancing.
     */
    private function advance(): void
    {
        ++$this->position;
    }

    /**
     * Add a token at the current position and advance.
     *
     * Creates a token with the current position, adds it to the token stream,
     * and automatically advances the position by one character. This is the
     * standard method for single-character tokens.
     *
     * @param TokenType $type  The type of token being added
     * @param string    $value The string value/content of the token
     */
    private function addToken(TokenType $type, string $value): void
    {
        $this->tokens[] = new Token($type, $value, $this->position);
        $this->advance();
    }

    /**
     * Add a token at a specific position without advancing.
     *
     * Creates a token at an explicitly specified position without modifying
     * the current position counter. Used for multi-character tokens where
     * position advancement is handled separately by the scanning method.
     *
     * @param TokenType $type     The type of token being added
     * @param string    $value    The string value/content of the token
     * @param int       $position The zero-based position where the token begins in the pattern
     */
    private function addTokenAt(TokenType $type, string $value, int $position): void
    {
        $this->tokens[] = new Token($type, $value, $position);
    }
}
