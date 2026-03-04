[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

# ecma-regex

A 100% ECMA-262 compliant regular expression engine for PHP, designed for JSON Schema validation and full JavaScript regex compatibility.

## Why This Package?

PHP uses PCRE (Perl Compatible Regular Expressions), while JSON Schema and JavaScript use ECMA-262 regex syntax. These engines have subtle differences that can cause validation inconsistencies. This package provides a pure PHP implementation of ECMA-262 regex, ensuring perfect compatibility with JSON Schema patterns.

## Requirements

> **Requires [PHP 8.5+](https://php.net/releases/)**

## Installation

```bash
composer require cline/ecma-regex
```

## Usage

See the [Getting Started](https://docs.cline.sh/ecma-regex/getting-started/) guide for usage examples and the [API Reference](https://docs.cline.sh/ecma-regex/api-reference/) for complete API documentation.

## Documentation

Comprehensive documentation available at [docs.cline.sh](https://docs.cline.sh/ecma-regex/):

- **[Getting Started](https://docs.cline.sh/ecma-regex/getting-started/)** - Installation and basic usage
- **[Pattern Syntax](https://docs.cline.sh/ecma-regex/pattern-syntax/)** - Complete pattern reference
- **[API Reference](https://docs.cline.sh/ecma-regex/api-reference/)** - Full API documentation
- **[JSON Schema Integration](https://docs.cline.sh/ecma-regex/json-schema/)** - JSON Schema validation patterns
- **[Performance](https://docs.cline.sh/ecma-regex/performance/)** - Caching and optimization
- **[Architecture](https://docs.cline.sh/ecma-regex/architecture/)** - Internal design and structure
- **[Troubleshooting](https://docs.cline.sh/ecma-regex/troubleshooting/)** - Common issues and solutions

**External References:**
- [ECMA-262 Specification](https://tc39.es/ecma262/#sec-regexp-regular-expression-objects)
- [JSON Schema Regular Expressions](https://json-schema.org/understanding-json-schema/reference/regular_expressions)

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://github.com/faustbrian/ecma-regex/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/ecma-regex.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/ecma-regex.svg

[link-tests]: https://github.com/faustbrian/ecma-regex/actions
[link-packagist]: https://packagist.org/packages/cline/ecma-regex
[link-downloads]: https://packagist.org/packages/cline/ecma-regex
[link-security]: https://github.com/faustbrian/ecma-regex/security
[link-maintainer]: https://github.com/faustbrian
[link-contributors]: ../../contributors
