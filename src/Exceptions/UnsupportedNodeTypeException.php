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
 * Exception thrown when an unsupported node type is encountered during pattern matching.
 *
 * This exception is raised when the matcher encounters an AST node with a type that
 * the current implementation does not support or recognize. Each node type in the
 * abstract syntax tree represents different regex pattern elements (literals, groups,
 * quantifiers, etc.). This typically indicates an unimplemented feature or an invalid
 * node type in the AST structure.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedNodeTypeException extends RuntimeException implements EcmaRegexException
{
    /**
     * Create a new exception for an unsupported node type.
     *
     * @param string $type The node type identifier that is not supported
     */
    public static function forType(string $type): self
    {
        return new self(
            sprintf('Unsupported node type: %s', $type),
        );
    }
}
