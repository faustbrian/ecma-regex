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
 * Exception thrown when a lookbehind construct is incomplete.
 *
 * Indicates that a lookbehind assertion starting with "(?<" was encountered
 * but lacks the required assertion specifier (= or !) to identify whether it's
 * a positive or negative lookbehind. This occurs when the pattern ends or
 * contains invalid characters immediately after the "(?<" sequence.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IncompleteLookbehindConstructException extends InvalidArgumentException implements EcmaRegexException
{
    /**
     * Creates an exception for an incomplete lookbehind construct at the specified position.
     *
     * @param int $position The zero-based character position where the incomplete
     *                      "(?<" lookbehind construct was encountered in the regex pattern
     *
     * @return self The constructed exception instance with a descriptive error message
     */
    public static function atPosition(int $position): self
    {
        return new self(
            sprintf('Incomplete lookbehind construct "(?<" at position %d', $position),
        );
    }
}
