<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Support;

use Cline\EcmaRegex\Contracts\TokenInterface;
use Cline\EcmaRegex\Enums\TokenType;

/**
 * Represents a single token in an ECMA-262 regex pattern.
 *
 * Tokens are the atomic units produced by the lexer during tokenization.
 * Each token represents a meaningful element of the regex syntax: literals,
 * operators, quantifiers, anchors, etc. The token includes its type, value,
 * and position for error reporting and parsing.
 *
 * This class is immutable and lightweight, designed to be created in large
 * quantities during lexical analysis without significant memory overhead.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Token implements TokenInterface
{
    /**
     * Create a new token instance.
     *
     * @param TokenType $type     The token type enum identifying the syntactic role
     *                            (e.g., Literal, Asterisk, LeftParen)
     * @param string    $value    The raw text value from the source pattern
     *                            (e.g., 'a', '*', '(')
     * @param int       $position The zero-based character position in the source pattern
     *                            where this token was found
     */
    public function __construct(
        private TokenType $type,
        private string $value,
        private int $position,
    ) {}

    /**
     * Get the token type as a string value.
     *
     * Returns the string representation of the token type enum,
     * primarily used for interface compatibility and debugging.
     *
     * @return string The token type value (e.g., 'literal', 'asterisk')
     */
    public function type(): string
    {
        return $this->type->value;
    }

    /**
     * Get the raw token value from the source pattern.
     *
     * Returns the original text that this token represents,
     * such as a literal character, operator symbol, or escape sequence.
     *
     * @return string The token's text value
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get the token's position in the source pattern.
     *
     * Returns the zero-based character offset where this token was found.
     * Used for error reporting and debugging to pinpoint issues in the
     * original regex pattern.
     *
     * @return int The zero-based position in the source string
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * Get the TokenType enum instance.
     *
     * Returns the typed enum value for type-safe comparisons and
     * pattern matching in the parser.
     *
     * @return TokenType The token type enum instance
     */
    public function tokenType(): TokenType
    {
        return $this->type;
    }
}
