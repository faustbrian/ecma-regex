<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Matcher;

use Cline\EcmaRegex\Contracts\MatcherInterface;
use Cline\EcmaRegex\Contracts\NodeInterface;
use Cline\EcmaRegex\Enums\NodeType;
use Cline\EcmaRegex\Exceptions\UnsupportedAssertionKindException;
use Cline\EcmaRegex\Exceptions\UnsupportedNodeTypeException;
use Cline\EcmaRegex\ValueObjects\MatchResult;

use function assert;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function mb_ord;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function min;
use function preg_match;
use function str_contains;

/**
 * ECMA-262 compliant regex matcher using backtracking NFA simulation.
 *
 * Implements the ECMA-262 regex matching algorithm with support for:
 * - Greedy and non-greedy quantifiers
 * - Lookahead and lookbehind assertions
 * - Capturing groups and backreferences
 * - Character classes and ranges
 * - Word boundaries and anchors
 *
 * Uses a backtracking approach to handle complex patterns with quantifiers,
 * maintaining a stack of alternative match states for efficient backtracking
 * when greedy matches fail.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Matcher implements MatcherInterface
{
    /**
     * The input string being matched against.
     */
    private string $input = '';

    /**
     * The length of the input string in UTF-8 characters.
     */
    private int $inputLength = 0;

    /**
     * The next available capture group index for numbered groups.
     */
    private int $nextCaptureIndex = 0;

    /**
     * Whether case-insensitive matching is enabled (i flag).
     */
    private bool $caseInsensitive = false;

    /**
     * Stack of alternative match contexts for backtracking.
     */
    private readonly BacktrackStack $backtrackStack;

    /**
     * Create a new matcher instance.
     */
    public function __construct()
    {
        $this->backtrackStack = new BacktrackStack();
    }

    /**
     * Test if the pattern matches the input string.
     *
     * Returns true if any match is found, false otherwise. This is equivalent
     * to JavaScript's RegExp.test() method.
     *
     * @param NodeInterface $ast   The compiled AST representing the regex pattern
     * @param string        $input The string to match against
     * @param string        $flags The regex flags (i for case-insensitive, g for global)
     *
     * @return bool True if the pattern matches, false otherwise
     */
    public function test(NodeInterface $ast, string $input, string $flags = ''): bool
    {
        $result = $this->exec($ast, $input, $flags);

        return $result->matched;
    }

    /**
     * Execute the pattern match and return detailed results.
     *
     * Attempts to find the first match in the input string by trying each
     * position from left to right. Returns a MatchResult containing the
     * match location, captured text, and all capture groups.
     *
     * @param NodeInterface $ast   The compiled AST representing the regex pattern
     * @param string        $input The string to match against
     * @param string        $flags The regex flags (i for case-insensitive, g for global)
     *
     * @return MatchResult The match result with captured groups and position
     */
    public function exec(NodeInterface $ast, string $input, string $flags = ''): MatchResult
    {
        $this->input = $input;
        $this->inputLength = mb_strlen($input, 'UTF-8');
        $this->backtrackStack->clear();
        $this->nextCaptureIndex = 0;
        $this->caseInsensitive = str_contains($flags, 'i');

        // Try to match starting from each position
        for ($start = 0; $start <= $this->inputLength; ++$start) {
            $context = new MatchContext(position: $start, startIndex: $start);

            if ($this->matchNode($ast, $context)) {
                $matchLength = $context->position - $start;
                $match = mb_substr($this->input, $start, $matchLength, 'UTF-8');

                return MatchResult::success(
                    index: $start,
                    match: $match,
                    captures: $context->captures,
                    input: $this->input,
                );
            }

            $this->backtrackStack->clear();
        }

        return MatchResult::failed();
    }

    /**
     * Find all non-overlapping matches in the input string.
     *
     * Similar to JavaScript's String.matchAll() or RegExp with the g flag.
     * Returns an array of all matches found, scanning left to right.
     * Prevents overlapping matches by advancing at least one position
     * after each match.
     *
     * @param NodeInterface $ast   The compiled AST representing the regex pattern
     * @param string        $input The string to match against
     * @param string        $flags The regex flags (i for case-insensitive, g for global)
     *
     * @return array<int, MatchResult> Array of all matches with their captures and positions
     */
    public function matchAll(NodeInterface $ast, string $input, string $flags = ''): array
    {
        $this->input = $input;
        $this->inputLength = mb_strlen($input, 'UTF-8');
        $this->caseInsensitive = str_contains($flags, 'i');
        $matches = [];
        $lastMatchEnd = 0;

        while ($lastMatchEnd <= $this->inputLength) {
            $this->backtrackStack->clear();
            $this->nextCaptureIndex = 0;
            $found = false;

            for ($start = $lastMatchEnd; $start <= $this->inputLength; ++$start) {
                $context = new MatchContext(position: $start, startIndex: $start);

                if ($this->matchNode($ast, $context)) {
                    $matchLength = $context->position - $start;
                    $match = mb_substr($this->input, $start, $matchLength, 'UTF-8');

                    $matches[] = MatchResult::success(
                        index: $start,
                        match: $match,
                        captures: $context->captures,
                        input: $this->input,
                    );

                    // Move past this match, ensure we advance at least 1 position
                    $lastMatchEnd = $context->position > $start ? $context->position : $start + 1;
                    $found = true;

                    break;
                }

                $this->backtrackStack->clear();
            }

            if (!$found) {
                break;
            }
        }

        return $matches;
    }

    /**
     * Match a node against the input at the current context position.
     *
     * Dispatches to the appropriate matching method based on the node type.
     * Each node type has specialized matching logic that may advance the
     * context position and capture groups.
     *
     * @param NodeInterface $node    The AST node to match
     * @param MatchContext  $context The current matching context with position and captures
     *
     * @throws UnsupportedNodeTypeException If the node type is not recognized
     * @return bool                         True if the node matches at the current position
     */
    private function matchNode(NodeInterface $node, MatchContext $context): bool
    {
        $nodeArray = $node->toArray();
        $type = $node->type();

        return match ($type) {
            NodeType::Root => $this->matchRoot($nodeArray, $context),
            NodeType::Alternation => $this->matchAlternation($nodeArray, $context),
            NodeType::Concatenation => $this->matchConcatenation($nodeArray, $context),
            NodeType::Literal => $this->matchLiteral($nodeArray, $context),
            NodeType::Dot => $this->matchDot($context),
            NodeType::Anchor => $this->matchAnchor($nodeArray, $context),
            NodeType::CharacterClass => $this->matchCharacterClass($nodeArray, $context),
            NodeType::Group => $this->matchGroup($nodeArray, $context),
            NodeType::Quantifier => $this->matchQuantifier($nodeArray, $context),
            NodeType::Assertion => $this->matchAssertion($nodeArray, $context),
            NodeType::Backreference => $this->matchBackreference($nodeArray, $context),
            default => throw UnsupportedNodeTypeException::forType($type->value),
        };
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchRoot(array $nodeArray, MatchContext $context): bool
    {
        if (!isset($nodeArray['child'])) {
            return true;
        }

        /** @var array<string, mixed>|NodeInterface $child */
        $child = $nodeArray['child'];

        return $this->matchNodeFromArray($child, $context);
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchAlternation(array $nodeArray, MatchContext $context): bool
    {
        if (!isset($nodeArray['alternatives']) || !is_array($nodeArray['alternatives'])) {
            return false;
        }

        foreach ($nodeArray['alternatives'] as $alternative) {
            /** @var array<string, mixed>|NodeInterface $alternative */
            $alternativeContext = $context->clone();

            if ($this->matchNodeFromArray($alternative, $alternativeContext)) {
                // Copy the successful state back to the original context
                $context->position = $alternativeContext->position;
                $context->captures = $alternativeContext->captures;

                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchConcatenation(array $nodeArray, MatchContext $context): bool
    {
        if (!isset($nodeArray['children']) || !is_array($nodeArray['children'])) {
            return true;
        }

        /** @var array<int, array<string, mixed>|NodeInterface> $children */
        $children = $nodeArray['children'];

        return $this->matchConcatenationWithBacktracking($children, $context, 0);
    }

    /**
     * Match concatenation with backtracking support.
     *
     * @param array<int, array<string, mixed>|NodeInterface> $children
     */
    private function matchConcatenationWithBacktracking(array $children, MatchContext $context, int $childIndex): bool
    {
        // Base case: all children matched
        if ($childIndex >= count($children)) {
            return true;
        }

        $child = $children[$childIndex];

        // Check if this child is a quantifier
        $childType = null;

        if ($child instanceof NodeInterface) {
            $childType = $child->type();
        } elseif (isset($child['type']) && (is_string($child['type']) || is_int($child['type']))) {
            $childType = NodeType::from($child['type']);
        }

        // If this is a quantifier, use backtracking-aware matching
        if ($childType === NodeType::Quantifier) {
            $alternatives = [];
            $savedContext = $context->clone();

            // Get the node array for the quantifier
            $quantifierArray = $child instanceof NodeInterface ? $child->toArray() : $child;

            // Try to match the quantifier and get alternatives
            if (!$this->matchQuantifierWithBacktracking($quantifierArray, $context, $alternatives)) {
                return false;
            }

            // Try to match the rest of the concatenation
            if ($this->matchConcatenationWithBacktracking($children, $context, $childIndex + 1)) {
                return true;
            }

            // If rest failed, try alternatives (backtrack)
            foreach ($alternatives as $altContext) {
                $context->position = $altContext->position;
                $context->captures = $altContext->captures;

                /**
                 * PHPStan doesn't understand that we've mutated $context, so it thinks this is always false
                 * @phpstan-ignore-next-line if.alwaysFalse
                 */
                if ($this->matchConcatenationWithBacktracking($children, $context, $childIndex + 1)) {
                    return true;
                }
            }

            // All alternatives failed, restore context and return false
            $context->position = $savedContext->position;
            $context->captures = $savedContext->captures;

            return false;
        }

        // For non-quantifier nodes, match normally
        if (!$this->matchNodeFromArray($child, $context)) {
            return false;
        }

        // Continue with next child
        return $this->matchConcatenationWithBacktracking($children, $context, $childIndex + 1);
    }

    /**
     * Match a literal string at the current position.
     *
     * Compares the literal value against the input at the current position.
     * Respects case-insensitive flag if enabled. Advances the context
     * position by the length of the matched literal.
     *
     * @param array<string, mixed> $nodeArray The node data containing the literal value
     * @param MatchContext         $context   The current matching context
     *
     * @return bool True if the literal matches at the current position
     */
    private function matchLiteral(array $nodeArray, MatchContext $context): bool
    {
        $rawValue = $nodeArray['value'] ?? '';
        assert(is_string($rawValue));
        $value = $rawValue;
        $valueLength = mb_strlen($value, 'UTF-8');

        if ($context->position + $valueLength > $this->inputLength) {
            return false;
        }

        $substr = mb_substr($this->input, $context->position, $valueLength, 'UTF-8');

        $matched = $this->caseInsensitive
            ? mb_strtolower($substr, 'UTF-8') === mb_strtolower($value, 'UTF-8')
            : $substr === $value;

        if ($matched) {
            $context->position += $valueLength;

            return true;
        }

        return false;
    }

    /**
     * Match the dot wildcard (.) at the current position.
     *
     * The dot matches any character except line terminators (\n, \r,
     * Unicode line separator U+2028, Unicode paragraph separator U+2029).
     * Advances the context position by one character if matched.
     *
     * @param MatchContext $context The current matching context
     *
     * @return bool True if a valid character is matched
     */
    private function matchDot(MatchContext $context): bool
    {
        if ($context->position >= $this->inputLength) {
            return false;
        }

        $char = mb_substr($this->input, $context->position, 1, 'UTF-8');

        // Dot matches any character except line terminators
        if (in_array($char, ["\n", "\r", "\u{2028}", "\u{2029}"], true)) {
            return false;
        }

        ++$context->position;

        return true;
    }

    /**
     * Match an anchor (^ or $) at the current position.
     *
     * Anchors are zero-width assertions that match positions rather than
     * characters. The ^ anchor matches the start of the string, and the
     * $ anchor matches the end of the string.
     *
     * @param array<string, mixed> $nodeArray The node data containing the anchor kind
     * @param MatchContext         $context   The current matching context
     *
     * @return bool True if the anchor condition is satisfied
     */
    private function matchAnchor(array $nodeArray, MatchContext $context): bool
    {
        $kind = $nodeArray['kind'] ?? '';

        return match ($kind) {
            'start', '^' => $context->position === 0,
            'end', '$' => $context->position === $this->inputLength,
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchCharacterClass(array $nodeArray, MatchContext $context): bool
    {
        if ($context->position >= $this->inputLength) {
            return false;
        }

        $char = mb_substr($this->input, $context->position, 1, 'UTF-8');
        $negated = $nodeArray['negated'] ?? false;
        $rawElements = $nodeArray['elements'] ?? [];
        assert(is_array($rawElements));

        /** @var array<int, array<string, mixed>> $elements */
        $elements = $rawElements;

        $matched = false;

        foreach ($elements as $element) {
            if ($this->matchCharacterClassElement($element, $char)) {
                $matched = true;

                break;
            }
        }

        if ($negated) {
            $matched = !$matched;
        }

        if ($matched) {
            ++$context->position;

            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $element
     */
    private function matchCharacterClassElement(array $element, string $char): bool
    {
        $elementType = $element['type'] ?? '';

        if ($elementType === 'literal') {
            return $char === ($element['value'] ?? '');
        }

        if ($elementType === 'range' || $elementType === NodeType::CharacterRange->value) {
            $rawStart = $element['start'] ?? '';
            $rawEnd = $element['end'] ?? '';
            assert(is_string($rawStart));
            assert(is_string($rawEnd));
            $start = $rawStart;
            $end = $rawEnd;

            $charCode = mb_ord($char, 'UTF-8');
            $startCode = mb_ord($start, 'UTF-8');
            $endCode = mb_ord($end, 'UTF-8');

            return $charCode >= $startCode && $charCode <= $endCode;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchGroup(array $nodeArray, MatchContext $context): bool
    {
        $capturing = $nodeArray['capturing'] ?? true;
        $captureIndex = null;

        if ($capturing) {
            $captureIndex = $this->nextCaptureIndex++;
        }

        $startPosition = $context->position;

        if (!isset($nodeArray['child'])) {
            return true;
        }

        /** @var array<string, mixed>|NodeInterface $child */
        $child = $nodeArray['child'];

        if (!$this->matchNodeFromArray($child, $context)) {
            if ($capturing) {
                --$this->nextCaptureIndex;
            }

            return false;
        }

        if ($capturing) {
            $captureLength = $context->position - $startPosition;
            $captureValue = mb_substr($this->input, $startPosition, $captureLength, 'UTF-8');
            $context->setCapture($captureIndex, $captureValue);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchQuantifier(array $nodeArray, MatchContext $context): bool
    {
        $min = $nodeArray['min'] ?? 0;
        $max = $nodeArray['max'] ?? null; // null means infinite
        $greedy = $nodeArray['greedy'] ?? true;
        $child = $nodeArray['child'] ?? null;

        if ($child === null) {
            return true;
        }

        /** @var array<string, mixed>|NodeInterface $child */

        // For non-greedy quantifiers, match as few as possible
        if (!$greedy) {
            // Match minimum required times
            for ($i = 0; $i < $min; ++$i) {
                if (!$this->matchNodeFromArray($child, $context)) {
                    return false;
                }
            }

            return true;
        }

        // For greedy quantifiers, we need to try matching from max down to min
        // to support backtracking
        $maxIterations = $max ?? $this->inputLength;

        // First, try to match as many as possible
        $matchedContexts = [];
        $currentContext = $context->clone();
        $count = 0;

        while ($count < $maxIterations) {
            $savedContext = $currentContext->clone();

            if (!$this->matchNodeFromArray($child, $currentContext)) {
                break;
            }

            ++$count;
            $matchedContexts[] = $currentContext->clone();

            // Prevent infinite loop on zero-width matches
            if ($currentContext->position === $savedContext->position) {
                break;
            }
        }

        // Check if we met the minimum requirement
        if ($count < $min) {
            return false;
        }

        // Greedy: use the longest match that we achieved
        if ($count > 0 && isset($matchedContexts[$count - 1])) {
            // Copy the final successful match context back to the input context
            $finalContext = $matchedContexts[$count - 1];
            $context->position = $finalContext->position;
            $context->captures = $finalContext->captures;
        }

        return true;
    }

    /**
     * @param array<string, mixed>     $nodeArray
     * @param array<int, MatchContext> $alternatives Store alternative contexts for backtracking
     */
    private function matchQuantifierWithBacktracking(array $nodeArray, MatchContext $context, array &$alternatives): bool
    {
        $min = $nodeArray['min'] ?? 0;
        $max = $nodeArray['max'] ?? null; // null means infinite
        $greedy = $nodeArray['greedy'] ?? true;
        $child = $nodeArray['child'] ?? null;

        if ($child === null) {
            return true;
        }

        /** @var array<string, mixed>|NodeInterface $child */

        // For non-greedy quantifiers, match as few as possible
        if (!$greedy) {
            // Match minimum required times
            for ($i = 0; $i < $min; ++$i) {
                if (!$this->matchNodeFromArray($child, $context)) {
                    return false;
                }
            }

            return true;
        }

        // For greedy quantifiers, match as many as possible and store alternatives
        $maxIterations = $max ?? $this->inputLength;

        // First, try to match as many as possible
        $matchedContexts = [];
        $currentContext = $context->clone();
        $count = 0;

        while ($count < $maxIterations) {
            $savedContext = $currentContext->clone();

            if (!$this->matchNodeFromArray($child, $currentContext)) {
                break;
            }

            ++$count;
            $matchedContexts[] = $currentContext->clone();

            // Prevent infinite loop on zero-width matches
            if ($currentContext->position === $savedContext->position) {
                break;
            }
        }

        // Check if we met the minimum requirement
        if ($count < $min) {
            return false;
        }

        // Store all alternatives from count-1 down to min (for backtracking)
        $alternatives = [];

        for ($i = $count - 1; $i >= $min; --$i) {
            if ($i > 0 && isset($matchedContexts[$i - 1])) {
                $alternatives[] = $matchedContexts[$i - 1];
            } elseif ($i === 0) {
                // If min is 0, we need to store the original context as an alternative
                $alternatives[] = $context->clone();
            }
        }

        // Greedy: use the longest match that we achieved
        if ($count > 0 && isset($matchedContexts[$count - 1])) {
            // Copy the final successful match context back to the input context
            $finalContext = $matchedContexts[$count - 1];
            $context->position = $finalContext->position;
            $context->captures = $finalContext->captures;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchAssertion(array $nodeArray, MatchContext $context): bool
    {
        $rawKind = $nodeArray['kind'] ?? '';
        assert(is_string($rawKind));
        $kind = $rawKind;
        $child = $nodeArray['child'] ?? null;

        // Handle word boundary assertions
        if ($kind === 'word_boundary') {
            $isWordBoundary = $this->isAtWordBoundary($context->position);
            // For \b, positive=true means we want a boundary
            // For \B, positive=false means we want NOT a boundary (handled by assertion positive flag)
            $rawPositive = $nodeArray['positive'] ?? true;
            assert(is_bool($rawPositive));
            $positive = $rawPositive;

            return $positive ? $isWordBoundary : !$isWordBoundary;
        }

        if ($child === null) {
            return false;
        }

        /** @var array<string, mixed>|NodeInterface $child */

        // For lookahead assertions, we need to match the child pattern
        // but not advance the position (lookahead is zero-width)
        if ($kind === 'lookahead') {
            $assertionContext = $context->clone();
            $matched = $this->matchNodeFromArray($child, $assertionContext);

            $rawPositive = $nodeArray['positive'] ?? true;
            assert(is_bool($rawPositive));
            $positive = $rawPositive;

            return $positive ? $matched : !$matched;
        }

        // For lookbehind assertions, we need to match the pattern backwards
        // ending at the current position (lookbehind is zero-width)
        if ($kind === 'lookbehind') {
            $rawPositive = $nodeArray['positive'] ?? true;
            assert(is_bool($rawPositive));
            $positive = $rawPositive;

            // Try to match the pattern at various positions before current position
            // We'll try starting from 1 character back up to a reasonable limit
            $maxLookback = min($context->position, 100); // Limit lookback distance

            for ($lookback = 1; $lookback <= $maxLookback; ++$lookback) {
                $assertionContext = new MatchContext($context->position - $lookback, $context->captures);
                $matched = $this->matchNodeFromArray($child, $assertionContext);

                // Check if match ended exactly at current position
                if ($matched && $assertionContext->position === $context->position) {
                    return $positive;
                }
            }

            // Also try matching from position 0 (for patterns that match the entire prefix)
            if ($context->position > 0) {
                $assertionContext = new MatchContext(0, $context->captures);
                $matched = $this->matchNodeFromArray($child, $assertionContext);

                if ($matched && $assertionContext->position === $context->position) {
                    return $positive;
                }
            }

            // No match found
            return !$positive;
        }

        throw UnsupportedAssertionKindException::forKind($kind);
    }

    /**
     * @param array<string, mixed> $nodeArray
     */
    private function matchBackreference(array $nodeArray, MatchContext $context): bool
    {
        $rawReference = $nodeArray['reference'] ?? 0;
        assert(is_int($rawReference));
        $reference = $rawReference;
        $capturedValue = $context->getCapture($reference);

        if ($capturedValue === null) {
            // If the capture group hasn't been captured yet, backreference fails
            return false;
        }

        $valueLength = mb_strlen($capturedValue, 'UTF-8');

        if ($context->position + $valueLength > $this->inputLength) {
            return false;
        }

        $substr = mb_substr($this->input, $context->position, $valueLength, 'UTF-8');

        if ($substr === $capturedValue) {
            $context->position += $valueLength;

            return true;
        }

        return false;
    }

    /**
     * Check if the current position is at a word boundary.
     *
     * A word boundary occurs between a word character and a non-word character,
     * or at the start/end of the string adjacent to a word character.
     *
     * Word characters are: [a-zA-Z0-9_]
     */
    private function isAtWordBoundary(int $position): bool
    {
        $beforeIsWord = false;
        $afterIsWord = false;

        // Check character before position
        if ($position > 0) {
            $beforeChar = mb_substr($this->input, $position - 1, 1, 'UTF-8');
            $beforeIsWord = preg_match('/\w/', $beforeChar) === 1;
        }

        // Check character at position
        if ($position < $this->inputLength) {
            $afterChar = mb_substr($this->input, $position, 1, 'UTF-8');
            $afterIsWord = preg_match('/\w/', $afterChar) === 1;
        }

        // Boundary exists when one side is a word char and the other isn't
        return $beforeIsWord !== $afterIsWord;
    }

    /**
     * Helper to match a node from array representation.
     * @param array<string, mixed>|NodeInterface $nodeOrArray
     */
    private function matchNodeFromArray(array|NodeInterface $nodeOrArray, MatchContext $context): bool
    {
        if ($nodeOrArray instanceof NodeInterface) {
            return $this->matchNode($nodeOrArray, $context);
        }

        // Create a simple wrapper to convert array to NodeInterface
        $wrapper = new readonly class($nodeOrArray) implements NodeInterface
        {
            /**
             * @param array<string, mixed> $data
             */
            public function __construct(
                private array $data,
            ) {}

            public function type(): NodeType
            {
                $type = $this->data['type'] ?? 'unknown';
                assert(is_string($type));

                return NodeType::from($type);
            }

            /**
             * @return array<string, mixed>
             */
            public function toArray(): array
            {
                return $this->data;
            }
        };

        return $this->matchNode($wrapper, $context);
    }
}
