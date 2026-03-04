<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Contracts\NodeInterface;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\Pattern;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * Helper function to create a compiled pattern for testing.
 */
function createPattern(string $source, string $flags = ''): Pattern
{
    $lexer = resolve(LexerInterface::class);
    $parser = resolve(ParserInterface::class);

    return Pattern::compile($source, $flags, $lexer, $parser);
}

/**
 * Helper function to tokenize a pattern string.
 */
function tokenize(string $pattern): array
{
    $lexer = resolve(LexerInterface::class);

    return $lexer->tokenize($pattern);
}

/**
 * Helper function to parse tokens into an AST.
 */
function parseTokens(array $tokens): NodeInterface
{
    $parser = resolve(ParserInterface::class);

    return $parser->parse($tokens);
}
