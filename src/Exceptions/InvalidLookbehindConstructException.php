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
 * Exception thrown when a lookbehind construct is invalid.
 *
 * Indicates that a lookbehind assertion starting with "(?<" was encountered
 * but the following character is not a valid assertion specifier. Only "=" (for
 * positive lookbehind) and "!" (for negative lookbehind) are recognized as valid
 * assertion operators in ECMA-262 lookbehind syntax.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidLookbehindConstructException extends InvalidArgumentException implements EcmaRegexException
{
    /**
     * Creates an exception for an invalid lookbehind construct at the specified position.
     *
     * @param string $assertion The invalid assertion character that followed the "(?<" sequence,
     *                          included in the error message to help identify the malformed construct
     * @param int    $position  The zero-based character position where the invalid
     *                          lookbehind construct was encountered in the regex pattern
     *
     * @return self The constructed exception instance with a descriptive error message
     */
    public static function atPosition(string $assertion, int $position): self
    {
        return new self(
            sprintf('Invalid lookbehind construct "(?<%s" at position %d', $assertion, $position),
        );
    }
}
