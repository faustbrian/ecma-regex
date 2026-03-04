<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Contracts;

/**
 * Defines the contract for lexical analysis of ECMA-262 regex patterns.
 *
 * The lexer is responsible for breaking down a raw regex pattern string into
 * a sequence of tokens that can be processed by the parser. This is the first
 * stage in the compilation pipeline, converting the linear pattern text into
 * structured token objects that represent regex elements like literals,
 * quantifiers, character classes, and special symbols.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface LexerInterface
{
    /**
     * Tokenize a regex pattern string into a sequence of tokens.
     *
     * Performs lexical analysis on the pattern, identifying and extracting
     * individual regex components such as literals, metacharacters, quantifiers,
     * groups, and character classes. Each component is converted into a token
     * object that preserves its type, value, and position in the source pattern.
     *
     * @param string $pattern The raw ECMA-262 regex pattern string to tokenize.
     *                        Must be a valid regex pattern or tokenization may fail.
     *
     * @return array<int, TokenInterface> Ordered array of tokens representing the
     *                                    pattern structure, indexed by position
     */
    public function tokenize(string $pattern): array;
}
