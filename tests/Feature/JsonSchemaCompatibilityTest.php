<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\EcmaRegex\Facades\EcmaRegex;

describe('JSON Schema Compatibility', function (): void {
    describe('JSON Schema Recommended Patterns', function (): void {
        it('validates email pattern from JSON Schema', function (): void {
            // Simplified email pattern commonly used in JSON Schema
            $pattern = '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$';

            expect(EcmaRegex::test($pattern, 'user@example.com'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'test.user+tag@domain.co.uk'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'invalid@'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, '@example.com'))->toBeFalse();
        });

        it('validates URI pattern from JSON Schema', function (): void {
            // Simplified URI pattern
            $pattern = '^https?://[a-zA-Z0-9.-]+';

            expect(EcmaRegex::test($pattern, 'http://example.com'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'https://secure.domain.org'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'ftp://site.net'))->toBeFalse();
        });

        it('validates date pattern (YYYY-MM-DD)', function (): void {
            $pattern = '^\d{4}-\d{2}-\d{2}$';

            expect(EcmaRegex::test($pattern, '2024-01-15'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '2024-12-31'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '24-01-15'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, '2024/01/15'))->toBeFalse();
        });

        it('validates time pattern (HH:MM:SS)', function (): void {
            $pattern = '^\d{2}:\d{2}:\d{2}$';

            expect(EcmaRegex::test($pattern, '12:34:56'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '00:00:00'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '23:59:59'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '1:2:3'))->toBeFalse();
        });

        it('validates datetime pattern (ISO 8601)', function (): void {
            $pattern = '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$';

            expect(EcmaRegex::test($pattern, '2024-01-15T12:34:56'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '2024-01-15 12:34:56'))->toBeFalse();
        });

        it('validates IPv4 address pattern', function (): void {
            $pattern = '^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$';

            expect(EcmaRegex::test($pattern, '192.168.1.1'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '10.0.0.1'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '255.255.255.255'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '192.168.1'))->toBeFalse();
        });

        it('validates UUID pattern', function (): void {
            $pattern = '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$';

            expect(EcmaRegex::test($pattern, '550e8400-e29b-41d4-a716-446655440000'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '123e4567-e89b-12d3-a456-426614174000'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'not-a-uuid'))->toBeFalse();
        });
    });

    describe('String Format Validation', function (): void {
        it('validates hexadecimal color codes', function (): void {
            $pattern = '^#[0-9a-fA-F]{6}$';

            expect(EcmaRegex::test($pattern, '#FF5733'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '#000000'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '#FFFFFF'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '#FFF'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, 'FF5733'))->toBeFalse();
        });

        it('validates alphanumeric strings', function (): void {
            $pattern = '^[a-zA-Z0-9]+$';

            expect(EcmaRegex::test($pattern, 'abc123'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'ABC123'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'abc-123'))->toBeFalse();
        });

        it('validates slug format', function (): void {
            $pattern = '^[a-z0-9]+(?:-[a-z0-9]+)*$';

            expect(EcmaRegex::test($pattern, 'my-slug'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'another-slug-example'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'slug'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'Invalid-Slug'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, 'slug-'))->toBeFalse();
        });

        it('validates username format', function (): void {
            $pattern = '^[a-zA-Z0-9_]{3,16}$';

            expect(EcmaRegex::test($pattern, 'user123'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'User_Name'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'ab'))->toBeFalse() // Too short
                ->and(EcmaRegex::test($pattern, 'user-name'))->toBeFalse(); // Invalid char
        });
    });

    describe('Number Format Validation', function (): void {
        it('validates integer numbers', function (): void {
            $pattern = '^-?\d+$';

            expect(EcmaRegex::test($pattern, '123'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '-456'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '0'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '12.34'))->toBeFalse();
        });

        it('validates decimal numbers', function (): void {
            $pattern = '^-?\d+\.\d+$';

            expect(EcmaRegex::test($pattern, '12.34'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '-56.78'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '0.1'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '123'))->toBeFalse();
        });

        it('validates percentage format', function (): void {
            $pattern = '^\d{1,3}%$';

            expect(EcmaRegex::test($pattern, '50%'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '100%'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '0%'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '50'))->toBeFalse();
        });

        it('validates currency format', function (): void {
            $pattern = '^\$\d+\.\d{2}$';

            expect(EcmaRegex::test($pattern, '$12.34'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '$0.99'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '$100.00'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '12.34'))->toBeFalse();
        });
    });

    describe('Boundary Assertions', function (): void {
        it('matches word boundaries', function (): void {
            $pattern = '\bword\b';

            expect(EcmaRegex::test($pattern, 'word'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'the word here'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'password'))->toBeFalse();
        });

        it('matches start of string', function (): void {
            $pattern = '^start';

            expect(EcmaRegex::test($pattern, 'start here'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'the start'))->toBeFalse();
        });

        it('matches end of string', function (): void {
            $pattern = 'end$';

            expect(EcmaRegex::test($pattern, 'the end'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'end of'))->toBeFalse();
        });
    });

    describe('Common Validation Patterns', function (): void {
        it('validates credit card format', function (): void {
            $pattern = '^\d{4}-\d{4}-\d{4}-\d{4}$';

            expect(EcmaRegex::test($pattern, '1234-5678-9012-3456'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '1234567890123456'))->toBeFalse();
        });

        it('validates postal code (US ZIP)', function (): void {
            $pattern = '^\d{5}(?:-\d{4})?$';

            expect(EcmaRegex::test($pattern, '12345'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '12345-6789'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '1234'))->toBeFalse();
        });

        it('validates social security number format', function (): void {
            $pattern = '^\d{3}-\d{2}-\d{4}$';

            expect(EcmaRegex::test($pattern, '123-45-6789'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '123456789'))->toBeFalse();
        });

        it('validates mac address', function (): void {
            $pattern = '^[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}$';

            expect(EcmaRegex::test($pattern, '00:1A:2B:3C:4D:5E'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'aa:bb:cc:dd:ee:ff'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '00-1A-2B-3C-4D-5E'))->toBeFalse();
        });
    });

    describe('Pattern Constraints', function (): void {
        it('validates minimum length', function (): void {
            $pattern = '^.{8,}$';

            expect(EcmaRegex::test($pattern, '12345678'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '123456789'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '1234567'))->toBeFalse();
        });

        it('validates maximum length', function (): void {
            $pattern = '^.{1,10}$';

            expect(EcmaRegex::test($pattern, '1234567890'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'short'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '12345678901'))->toBeFalse();
        });

        it('validates exact length', function (): void {
            $pattern = '^.{5}$';

            expect(EcmaRegex::test($pattern, '12345'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'abcde'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '1234'))->toBeFalse()
                ->and(EcmaRegex::test($pattern, '123456'))->toBeFalse();
        });
    });

    describe('Character Class Patterns', function (): void {
        it('matches lowercase letters only', function (): void {
            $pattern = '^[a-z]+$';

            expect(EcmaRegex::test($pattern, 'abc'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'xyz'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'ABC'))->toBeFalse();
        });

        it('matches uppercase letters only', function (): void {
            $pattern = '^[A-Z]+$';

            expect(EcmaRegex::test($pattern, 'ABC'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'XYZ'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'abc'))->toBeFalse();
        });

        it('matches digits only', function (): void {
            $pattern = '^[0-9]+$';

            expect(EcmaRegex::test($pattern, '123'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '0'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'abc'))->toBeFalse();
        });

        it('matches whitespace', function (): void {
            $pattern = '^\s+$';

            expect(EcmaRegex::test($pattern, ' '))->toBeTrue()
                ->and(EcmaRegex::test($pattern, "\t"))->toBeTrue()
                ->and(EcmaRegex::test($pattern, "\n"))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'a'))->toBeFalse();
        });
    });

    describe('Advanced JSON Schema Patterns', function (): void {
        it('validates JSON pointer format', function (): void {
            $pattern = '^(/[^/]+)*$';

            expect(EcmaRegex::test($pattern, '/foo/bar'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '/foo'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, ''))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'foo/bar'))->toBeFalse();
        });

        it('validates relative JSON pointer', function (): void {
            $pattern = '^\d+#?(/[^/]+)*$';

            expect(EcmaRegex::test($pattern, '0#'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '1/foo'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '2#/bar'))->toBeTrue();
        });

        it('validates regex pattern strings', function (): void {
            // Simple validation that it looks like a regex
            $pattern = '^.+$';

            expect(EcmaRegex::test($pattern, '[a-z]+'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, '\d{3}'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, ''))->toBeFalse();
        });
    });

    describe('Real-World Scenarios', function (): void {
        it('extracts all email addresses from text', function (): void {
            $pattern = '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}';
            $text = 'Contact us at support@example.com or sales@company.org for more info.';

            $matches = EcmaRegex::matchAll($pattern, $text);

            expect($matches)->toHaveCount(2)
                ->and($matches[0]->match)->toBe('support@example.com')
                ->and($matches[1]->match)->toBe('sales@company.org');
        });

        it('extracts all phone numbers from text', function (): void {
            $pattern = '\d{3}-\d{3}-\d{4}';
            $text = 'Call 123-456-7890 or 555-123-4567';

            $matches = EcmaRegex::matchAll($pattern, $text);

            expect($matches)->toHaveCount(2)
                ->and($matches[0]->match)->toBe('123-456-7890')
                ->and($matches[1]->match)->toBe('555-123-4567');
        });

        it('validates password complexity', function (): void {
            // At least 8 chars, contains letter and number
            $pattern = '^(?=.*[a-zA-Z])(?=.*\d).{8,}$';

            expect(EcmaRegex::test($pattern, 'password123'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'Pass1234'))->toBeTrue()
                ->and(EcmaRegex::test($pattern, 'password'))->toBeFalse() // No number
                ->and(EcmaRegex::test($pattern, '12345678'))->toBeFalse(); // No letter
        });
    });
});
