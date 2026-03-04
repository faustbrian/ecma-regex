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
 * Exception thrown when an unexpected token is encountered during pattern parsing.
 *
 * This exception is raised when the lexer or parser encounters a token that is
 * not valid in the current context according to ECMA-262 regex grammar rules.
 * The exception provides both the position and the type of the unexpected token
 * to aid in debugging invalid patterns.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedTokenException extends RuntimeException implements EcmaRegexException
{
    /**
     * Create a new exception for an unexpected token at a specific position.
     *
     * @param string $tokenType Description or type of the unexpected token encountered
     * @param int    $position  Zero-based character position where the unexpected token was found
     */
    public static function atPosition(string $tokenType, int $position): self
    {
        return new self(
            sprintf('Unexpected token at position %d: %s', $position, $tokenType),
        );
    }
}
