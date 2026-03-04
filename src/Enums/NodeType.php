<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Enums;

/**
 * AST node types for ECMA-262 regex patterns.
 *
 * Defines the structural node types used in the abstract syntax tree (AST)
 * representation of parsed regular expression patterns. Each case corresponds
 * to a specific regex construct in the ECMA-262 specification, enabling type-safe
 * tree traversal and pattern matching operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum NodeType: string
{
    /** Root node representing the entire regex pattern */
    case Root = 'root';

    /** Alternation (|) node representing pattern alternatives */
    case Alternation = 'alternation';

    /** Concatenation node representing sequential pattern elements */
    case Concatenation = 'concatenation';

    /** Literal character node representing exact character matches */
    case Literal = 'literal';

    /** Dot (.) metacharacter node matching any single character except line terminators */
    case Dot = 'dot';

    /** Anchor node representing position assertions (^ or $) */
    case Anchor = 'anchor';

    /** Character class node ([...]) representing a set of allowed characters */
    case CharacterClass = 'character_class';

    /** Character range node (a-z) representing a contiguous range within a character class */
    case CharacterRange = 'character_range';

    /** Capturing or non-capturing group node (...) or (?:...) */
    case Group = 'group';

    /** Quantifier node (*, +, ?, {n,m}) specifying repetition constraints */
    case Quantifier = 'quantifier';

    /** Assertion node representing lookahead or lookbehind assertions */
    case Assertion = 'assertion';

    /** Backreference node (\1, \2, etc.) referencing previously captured groups */
    case Backreference = 'backreference';
}
