<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when an expected token is missing during regex pattern parsing.
 *
 * This exception is raised by the lexer or parser when it encounters a position
 * in the pattern where a required token should exist but is absent, such as
 * missing closing delimiters or incomplete escape sequences.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingTokenException extends RuntimeException implements EcmaRegexException
{
    /**
     * Create a new exception for a missing token at a specific position.
     *
     * @param string $message  Custom error message describing which token is missing
     * @param int    $position Zero-based character position in the pattern where the token was expected
     */
    public static function atPosition(string $message, int $position): self
    {
        return new self(
            sprintf('%s at position %d', $message, $position),
        );
    }
}
