<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex;

use Cline\EcmaRegex\Contracts\LexerInterface;
use Cline\EcmaRegex\Contracts\MatcherInterface;
use Cline\EcmaRegex\Contracts\ParserInterface;
use Cline\EcmaRegex\Contracts\PatternInterface;
use Cline\EcmaRegex\Lexer\Lexer;
use Cline\EcmaRegex\Matcher\Matcher;
use Cline\EcmaRegex\Parser\Parser;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Cache;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function config;

/**
 * Service provider for the ECMA-262 regex package.
 *
 * Registers the EcmaRegexManager as a singleton in the Laravel service container,
 * binds all package interfaces to their concrete implementations, and publishes
 * the package configuration file for customization.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EcmaRegexServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package settings and define publishable assets.
     *
     * Sets the package name and registers the configuration file that will be
     * published to the application's config directory when users run the vendor:publish
     * command with the package service provider tag.
     *
     * @param Package $package The package configuration instance to configure
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ecma-regex')
            ->hasConfigFile();
    }

    /**
     * Register the package's services in the Laravel service container.
     *
     * Binds the EcmaRegexManager as a singleton with optional caching support,
     * and registers all package interfaces to their concrete implementations
     * for dependency injection throughout the application.
     */
    #[Override()]
    public function registeringPackage(): void
    {
        // Register EcmaRegexManager as singleton
        $this->app->singleton(function (Container $app): EcmaRegexManager {
            /** @var bool $cacheEnabled */
            $cacheEnabled = config('ecma-regex.cache_enabled', true);

            /** @var int $cacheTtl */
            $cacheTtl = config('ecma-regex.cache_ttl', 3_600);

            $cache = $cacheEnabled ? Cache::store() : null;

            return new EcmaRegexManager(
                lexer: $app->make(LexerInterface::class),
                parser: $app->make(ParserInterface::class),
                cache: $cache,
                cacheEnabled: $cacheEnabled,
                cacheTtl: $cacheTtl,
            );
        });

        // Bind PatternInterface to Pattern
        $this->app->bind(PatternInterface::class, Pattern::class);

        // Bind LexerInterface to Lexer
        $this->app->bind(LexerInterface::class, Lexer::class);

        // Bind ParserInterface to Parser
        $this->app->bind(ParserInterface::class, Parser::class);

        // Bind MatcherInterface to Matcher
        $this->app->bind(MatcherInterface::class, Matcher::class);
    }

    /**
     * Bootstrap the package's services after registration.
     *
     * Performs any initialization tasks required after all services have been
     * registered. Currently unused but available for future extension points
     * such as view composers, route bindings, or event listeners.
     */
    #[Override()]
    public function bootingPackage(): void
    {
        //
    }
}
