<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Enums;

/**
 * ECMA-262 regex token types for lexical analysis.
 *
 * Defines the complete set of token types recognized by the lexer during
 * tokenization of regular expression patterns. Each token represents a
 * lexical unit in the ECMA-262 regex grammar, providing type-safe token
 * classification for the parsing phase.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum TokenType: string
{
    /** Literal character token representing plain text characters */
    case Literal = 'literal';

    /** Dot metacharacter (.) token matching any character except line terminators */
    case Dot = 'dot';

    /** Caret (^) anchor token matching the start of a line or string */
    case Caret = 'caret';

    /** Dollar sign ($) anchor token matching the end of a line or string */
    case Dollar = 'dollar';

    /** Asterisk (*) quantifier token matching zero or more occurrences */
    case Asterisk = 'asterisk';

    /** Plus (+) quantifier token matching one or more occurrences */
    case Plus = 'plus';

    /** Question mark (?) quantifier token matching zero or one occurrence */
    case Question = 'question';

    /** Pipe (|) alternation token separating alternative patterns */
    case Pipe = 'pipe';

    /** Left parenthesis token marking the start of a group or assertion */
    case LeftParen = 'left_paren';

    /** Right parenthesis token marking the end of a group or assertion */
    case RightParen = 'right_paren';

    /** Left bracket ([) token marking the start of a character class */
    case LeftBracket = 'left_bracket';

    /** Right bracket (]) token marking the end of a character class */
    case RightBracket = 'right_bracket';

    /** Left brace ({) token marking the start of a counted quantifier */
    case LeftBrace = 'left_brace';

    /** Right brace (}) token marking the end of a counted quantifier */
    case RightBrace = 'right_brace';

    /** Comma (,) token separating min and max values in counted quantifiers */
    case Comma = 'comma';

    /** Hyphen (-) token denoting a character range within character classes */
    case Hyphen = 'hyphen';

    /** Backslash (\) token introducing escape sequences or special constructs */
    case Backslash = 'backslash';

    /** Escape sequence token representing backslash-escaped characters */
    case Escape = 'escape';

    /** Character class token ([...]) matching any character in the set */
    case CharacterClass = 'character_class';

    /** Negated character class token ([^...]) matching any character not in the set */
    case NegatedCharacterClass = 'negated_character_class';

    /** Capturing group token (...) capturing matched text for backreferences */
    case Group = 'group';

    /** Non-capturing group token (?:...) grouping without capturing */
    case NonCapturingGroup = 'non_capturing_group';

    /** Positive lookahead assertion token (?=...) matching if pattern follows */
    case PositiveLookahead = 'positive_lookahead';

    /** Negative lookahead assertion token (?!...) matching if pattern doesn't follow */
    case NegativeLookahead = 'negative_lookahead';

    /** Positive lookbehind assertion token (?<=...) matching if pattern precedes */
    case PositiveLookbehind = 'positive_lookbehind';

    /** Negative lookbehind assertion token (?<!...) matching if pattern doesn't precede */
    case NegativeLookbehind = 'negative_lookbehind';

    /** Quantifier token representing repetition specifiers (*, +, ?, {n,m}) */
    case Quantifier = 'quantifier';

    /** End-of-file token marking the completion of input scanning */
    case Eof = 'eof';
}
