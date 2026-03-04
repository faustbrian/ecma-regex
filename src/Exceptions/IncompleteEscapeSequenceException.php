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
 * Exception thrown when an escape sequence is incomplete.
 *
 * Indicates that a backslash escape sequence was started but not properly
 * completed in the regex pattern. This typically occurs when a backslash
 * appears at the end of the pattern without a following character, or when
 * a multi-character escape sequence (like \u or \x) is missing required digits.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IncompleteEscapeSequenceException extends InvalidArgumentException implements EcmaRegexException
{
    /**
     * Creates an exception for an incomplete escape sequence at the specified position.
     *
     * @param int $position The zero-based character position where the incomplete
     *                      escape sequence was encountered in the regex pattern
     *
     * @return self The constructed exception instance with a descriptive error message
     */
    public static function atPosition(int $position): self
    {
        return new self(
            sprintf('Incomplete escape sequence at position %d', $position),
        );
    }
}
