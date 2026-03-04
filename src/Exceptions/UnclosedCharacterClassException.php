<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Exceptions;

use InvalidArgumentException;

use function sprintf;

/**
 * Exception thrown when a character class is not properly closed with a closing bracket.
 *
 * This exception occurs during lexical analysis when a character class opened with '['
 * reaches the end of the pattern without encountering a matching ']'. Character classes
 * must be properly terminated to form valid ECMA-262 regex syntax.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnclosedCharacterClassException extends InvalidArgumentException implements EcmaRegexException
{
    /**
     * Create a new exception for an unclosed character class at a specific position.
     *
     * @param int $position Zero-based character position where the unclosed character class begins
     */
    public static function atPosition(int $position): self
    {
        return new self(
            sprintf('Unclosed character class at position %d', $position),
        );
    }
}
