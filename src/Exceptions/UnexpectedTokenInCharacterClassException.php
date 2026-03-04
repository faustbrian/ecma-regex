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
 * Exception thrown when an unexpected token is found within a character class.
 *
 * This exception occurs during character class parsing when tokens that are not
 * permitted inside character class brackets (e.g., unescaped special characters
 * that have different meanings in character class context) are encountered.
 * Character classes have their own parsing rules distinct from the main pattern.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedTokenInCharacterClassException extends RuntimeException implements EcmaRegexException
{
    /**
     * Create a new exception for an unexpected token within a character class.
     *
     * @param int $position Zero-based character position within the pattern where the invalid token appears
     */
    public static function atPosition(int $position): self
    {
        return new self(
            sprintf('Unexpected token in character class at position %d', $position),
        );
    }
}
