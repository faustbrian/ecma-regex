<?php declare(strict_types=1);

/**
 * Copyright (c) 2025 Cline
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/cline/ecma-regex
 */

/*
|--------------------------------------------------------------------------
| ECMA-262 Regex Configuration
|--------------------------------------------------------------------------
|
| This file defines the configuration for the ECMA-262 regex package.
| It controls pattern compilation caching for improved performance.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether compiled patterns should be cached.
    | Enabling caching significantly improves performance when the same
    | patterns are used repeatedly, at the cost of some memory usage.
    |
    */

    'cache_enabled' => env('ECMA_REGEX_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | This option controls how long (in seconds) compiled patterns should
    | be stored in the cache. Set to null for no expiration, or specify
    | a number of seconds for automatic cache invalidation.
    |
    | Default: 3600 (1 hour)
    |
    */

    'cache_ttl' => env('ECMA_REGEX_CACHE_TTL', 3600),
];
