<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\EcmaRegex\Exceptions;

use Throwable;

/**
 * Marker interface for all ECMA Regex package exceptions.
 *
 * Provides a unified exception hierarchy for the package by extending the
 * standard Throwable interface. This enables consumers to catch all package-specific
 * exceptions with a single catch block, while still allowing granular exception
 * handling for specific error conditions.
 *
 * ```php
 * try {
 *     $manager->compile($pattern);
 * } catch (EcmaRegexException $e) {
 *     // Handle any ECMA Regex package exception
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface EcmaRegexException extends Throwable {}
