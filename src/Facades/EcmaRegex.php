<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Facades;

use Cline\EcmaRegex\Contracts\PatternInterface;
use Cline\EcmaRegex\EcmaRegexManager;
use Cline\EcmaRegex\ValueObjects\MatchResult;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the ECMA-262 regex manager providing JavaScript-compatible regex operations.
 *
 * Provides a clean static API for compiling and executing ECMA-262 regex patterns
 * with support for pattern caching and fluent interface. All methods proxy to the
 * underlying EcmaRegexManager instance registered in Laravel's service container.
 *
 * @method static PatternInterface        compile(string $pattern, string $flags = '')                 Compile a regex pattern into a reusable Pattern object
 * @method static void                    flushCache()                                                 Clear the compiled pattern cache
 * @method static MatchResult             match(string $pattern, string $input, string $flags = '')    Execute pattern and return first match
 * @method static array<int, MatchResult> matchAll(string $pattern, string $input, string $flags = '') Execute pattern and return all matches
 * @method static bool                    test(string $pattern, string $input, string $flags = '')     Test if pattern matches input
 *
 * @author Brian Faust <brian@cline.sh>
 * @see EcmaRegexManager
 */
final class EcmaRegex extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * Returns the fully-qualified class name used as the service container binding key
     * for the ECMA-262 regex manager. This allows Laravel's facade system to resolve
     * the underlying manager instance.
     *
     * @return string The service container binding key (EcmaRegexManager class name)
     */
    protected static function getFacadeAccessor(): string
    {
        return EcmaRegexManager::class;
    }
}
