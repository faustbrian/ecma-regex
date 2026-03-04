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
 * Exception thrown when a group construct is invalid.
 *
 * Indicates that a special group construct starting with "(?" was encountered
 * with an unrecognized or invalid suffix character. The lexer identified the
 * start of a special group but the following character(s) do not match any
 * valid ECMA-262 group syntax (such as :, =, !, <, etc.).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidGroupConstructException extends InvalidArgumentException implements EcmaRegexException
{
    /**
     * Creates an exception for an invalid group construct at the specified position.
     *
     * @param string $next     The invalid character(s) that followed the "(?" sequence,
     *                         included in the error message to help identify the malformed construct
     * @param int    $position The zero-based character position where the invalid
     *                         group construct was encountered in the regex pattern
     *
     * @return self The constructed exception instance with a descriptive error message
     */
    public static function atPosition(string $next, int $position): self
    {
        return new self(
            sprintf('Invalid group construct "(?%s" at position %d', $next, $position),
        );
    }
}
