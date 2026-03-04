<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Contracts;

/**
 * Defines the contract for parsing token sequences into Abstract Syntax Trees.
 *
 * The parser is the second stage in the regex compilation pipeline, following
 * lexical analysis. It takes the flat sequence of tokens produced by the lexer
 * and constructs a hierarchical AST that captures the semantic structure and
 * operator precedence of the regex pattern. The parser handles grouping,
 * alternation, quantifiers, and nested structures to build a tree that can
 * be efficiently evaluated during pattern matching.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface ParserInterface
{
    /**
     * Parse a token sequence into an Abstract Syntax Tree.
     *
     * Performs syntactic analysis on the token stream, constructing a hierarchical
     * tree structure that represents the regex pattern's semantic meaning. The
     * parser handles operator precedence, validates pattern syntax, and creates
     * appropriate node types for each pattern element. The resulting AST serves
     * as the executable representation of the regex for matching operations.
     *
     * @param array<int, TokenInterface> $tokens Ordered array of tokens from the lexer,
     *                                           representing the tokenized regex pattern.
     *                                           Must be a valid token sequence.
     *
     * @return NodeInterface The root node of the constructed AST, representing the
     *                       complete parsed pattern structure
     */
    public function parse(array $tokens): NodeInterface;
}
