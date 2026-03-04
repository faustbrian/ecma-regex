<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Contracts;

/**
 * Defines the contract for individual tokens in a regex pattern.
 *
 * Tokens are the atomic units produced by lexical analysis, representing
 * individual regex components such as literals, metacharacters, quantifiers,
 * or grouping symbols. Each token captures not only the type and value of
 * the regex element, but also its position in the original pattern string,
 * which is essential for error reporting and source mapping.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface TokenInterface
{
    /**
     * Get the type of this token.
     *
     * The token type identifies what kind of regex element this token represents,
     * such as a literal character, quantifier symbol, group delimiter, or special
     * metacharacter. This classification is used by the parser to build the AST.
     *
     * @return string The token type identifier (e.g., 'LITERAL', 'QUANTIFIER', 'GROUP_START')
     */
    public function type(): string;

    /**
     * Get the raw value of this token from the source pattern.
     *
     * Returns the exact substring from the original pattern that this token
     * represents. For literals, this is the character itself. For metacharacters
     * and operators, this is the symbol or sequence that was matched.
     *
     * @return string The raw token value from the source pattern
     */
    public function value(): string;

    /**
     * Get the character position where this token starts in the source pattern.
     *
     * The position is the zero-based index into the original pattern string
     * where this token begins. This is used for error reporting, debugging,
     * and source mapping when displaying parse errors or warnings.
     *
     * @return int The zero-based character offset in the source pattern
     */
    public function position(): int;
}
