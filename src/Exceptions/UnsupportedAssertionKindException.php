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
 * Exception thrown when an unsupported assertion kind is encountered during matching.
 *
 * This exception is raised when the matcher encounters an assertion node with a kind
 * value that the current implementation does not support. Assertions in ECMA-262 regex
 * include lookaheads, lookbehinds, word boundaries, and anchors. This typically indicates
 * either an unimplemented assertion type or an invalid assertion kind in the AST.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedAssertionKindException extends RuntimeException implements EcmaRegexException
{
    /**
     * Create a new exception for an unsupported assertion kind.
     *
     * @param string $kind The assertion kind identifier that is not supported
     */
    public static function forKind(string $kind): self
    {
        return new self(
            sprintf('Unsupported assertion kind: %s', $kind),
        );
    }
}
