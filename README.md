# PHP CN Codes

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Latest Version](https://img.shields.io/packagist/v/xterr/php-cncodes.svg)](https://packagist.org/packages/xterr/php-cncodes)

A framework-agnostic PHP library for working with CN (Combined Nomenclature) codes, including built-in
translation support for 23 EU languages.

## Overview

CN codes are the EU's 8-digit goods classification system used for international trade, customs declarations, and
trade statistics. This library provides:

- Complete CN code database (2023, 2024, 2025, 2026)
- Five-level hierarchy: Sections, Chapters, Headings, Subheadings, CN Codes
- Lazy loading per version — only the requested year's data is loaded into memory
- Supplementary unit information on CN codes
- Framework-agnostic translation system with 23 language support
- Adapters for Symfony, Laravel, and native PHP
- Zero runtime dependencies for the core library

## Installation

```bash
composer require xterr/php-cncodes
```

## Quick Start

### Basic Usage (No Translation)

```php
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnVersion;

$factory = new CnCodesFactory();
$codes = $factory->getCodes();

// Find a specific CN code
$cnCode = $codes->getByCodeAndVersion('01012100', CnVersion::VERSION_2026);

echo $cnCode->getCode();              // "01012100"
echo $cnCode->getRawCode();           // "0101 21 00"
echo $cnCode->getName();              // "Pure-bred breeding animals" (English)
echo $cnCode->getLocalName();         // "Pure-bred breeding animals" (falls back to English)
echo $cnCode->getSection();           // "I"
echo $cnCode->getChapter();           // "01"
echo $cnCode->getHeading();           // "0101"
echo $cnCode->getSupplementaryUnit(); // "PST"
```

### With Translation Support

```php
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnVersion;
use Xterr\CnCodes\Translation\Adapter\ArrayTranslator;

// Use the built-in ArrayTranslator for zero-dependency translations
$translator = new ArrayTranslator(null, 'de');
$factory = new CnCodesFactory(null, $translator);

$section = $factory->getSections()->getByCodeAndVersion('I', CnVersion::VERSION_2026);

echo $section->getName();      // "LIVE ANIMALS; ANIMAL PRODUCTS" (always English)
echo $section->getLocalName(); // "LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS" (German translation)
```

## Translation Adapters

The library provides a framework-agnostic `TranslatorInterface` with multiple adapter implementations.

### ArrayTranslator (Native PHP - Zero Dependencies)

Best for standalone PHP applications or when you don't want any framework dependencies.

```php
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\Translation\Adapter\ArrayTranslator;

// Simple usage with locale
$translator = new ArrayTranslator(null, 'fr');
$factory = new CnCodesFactory(null, $translator);

// With fallback locale
$translator = new ArrayTranslator(null, 'fr', 'en');

// Change locale at runtime
$translator->setLocale('de');

// Get available locales
$locales = $translator->getAvailableLocales();
// ['bg', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'ga', 'hr', 'hu', 'it', 'lt', 'lv', 'mt', 'nl', 'pl', 'pt', 'ro', 'sk', 'sl', 'sv']
```

### SymfonyTranslatorAdapter

For Symfony applications. Requires `symfony/translation-contracts`.

```bash
composer require symfony/translation-contracts
```

```php
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\Translation\Adapter\SymfonyTranslatorAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

// In a Symfony controller or service
public function __construct(
    TranslatorInterface $symfonyTranslator
) {
    $adapter = new SymfonyTranslatorAdapter($symfonyTranslator);
    $this->cnCodesFactory = new CnCodesFactory(null, $adapter);
}

// Usage
$codes = $this->cnCodesFactory->getCodes();
$cnCode = $codes->getByCodeAndVersion('01012100');

// Uses the locale from Symfony's translator (auto-detected from request)
echo $cnCode->getLocalName();
```

#### Symfony Configuration

Copy or generate translation files to your Symfony translations directory:

```bash
# Generate YAML files for Symfony
php bin/console cn:translations:build yaml-generate

# Copy to your Symfony project
cp Resources/translations/yaml/cnCodes.*.yaml /path/to/symfony/translations/
```

Or configure as a translation resource in `config/packages/translation.yaml`:

```yaml
framework:
  translator:
    paths:
      - '%kernel.project_dir%/vendor/xterr/php-cncodes/Resources/translations'
```

### LaravelTranslatorAdapter

For Laravel applications. Requires `illuminate/contracts`.

```bash
composer require illuminate/contracts
```

```php
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\Translation\Adapter\LaravelTranslatorAdapter;
use Illuminate\Contracts\Translation\Translator;

// In a Laravel service provider
public function register()
{
    $this->app->singleton(CnCodesFactory::class, function ($app) {
        $adapter = new LaravelTranslatorAdapter($app->make(Translator::class));
        return new CnCodesFactory(null, $adapter);
    });
}

// Usage in controller
public function show(CnCodesFactory $factory, string $code)
{
    $cnCode = $factory->getCodes()->getByCodeAndVersion($code);

    // Uses Laravel's current locale
    return response()->json([
        'code' => $cnCode->getCode(),
        'name' => $cnCode->getName(),
        'localName' => $cnCode->getLocalName(),
    ]);
}
```

#### Laravel Configuration

Generate and publish Laravel translation files:

```bash
# Generate Laravel PHP files
php bin/console cn:translations:build all

# Copy to your Laravel project
cp -r Resources/translations/laravel/cncodes /path/to/laravel/lang/vendor/
```

### NullTranslator (Default)

Returns the original English text. Used internally as the default when no translator is provided.

```php
use Xterr\CnCodes\Translation\Adapter\NullTranslator;

$translator = new NullTranslator();
echo $translator->translate('Pure-bred breeding animals'); // "Pure-bred breeding animals"
```

### Custom Translator

Implement the `TranslatorInterface` for custom translation sources:

```php
use Xterr\CnCodes\Translation\TranslatorInterface;

class DatabaseTranslator implements TranslatorInterface
{
    public function translate(string $id, ?string $locale = null, string $domain = 'cnCodes'): string
    {
        // Your custom translation logic
        return $this->repository->findTranslation($id, $locale) ?? $id;
    }
}
```

## Supported Languages

The library includes translations for 23 EU languages:

| Code | Language  | Code | Language   |
|------|-----------|------|------------|
| bg   | Bulgarian | it   | Italian    |
| cs   | Czech     | lt   | Lithuanian |
| da   | Danish    | lv   | Latvian    |
| de   | German    | mt   | Maltese    |
| el   | Greek     | nl   | Dutch      |
| es   | Spanish   | pl   | Polish     |
| et   | Estonian  | pt   | Portuguese |
| fi   | Finnish   | ro   | Romanian   |
| fr   | French    | sk   | Slovak     |
| ga   | Irish     | sl   | Slovenian  |
| hr   | Croatian  | sv   | Swedish    |
| hu   | Hungarian |      |            |

## CnCode Properties

| Method                   | Return Type | Description                                          |
|--------------------------|-------------|------------------------------------------------------|
| `getCode()`              | `string`    | Normalized code (e.g., "01012100")                   |
| `getRawCode()`           | `string`    | Space-separated code (e.g., "0101 21 00")            |
| `getName()`              | `string`    | English name                                         |
| `getLocalName()`         | `string`    | Translated name (falls back to English)              |
| `getVersion()`           | `int`       | Version year (2023, 2024, 2025, or 2026)             |
| `getSection()`           | `?string`   | Parent section (e.g., "I")                           |
| `getChapter()`           | `?string`   | Parent chapter (e.g., "01")                          |
| `getHeading()`           | `?string`   | Parent heading (e.g., "0101")                        |
| `getSubheading()`        | `?string`   | Parent subheading (e.g., "010121") or null           |
| `getSupplementaryUnit()` | `?string`   | Supplementary unit code (e.g., "PST", "KGM") or null |

## CN Hierarchy

The Combined Nomenclature follows a five-level hierarchy:

```
Section (I-XXI)        — 21 broad categories
  └─ Chapter (01-97)   — 97 product groups
      └─ Heading (4-digit)    — ~957 product types
          └─ Subheading (6-digit) — ~1,830 HS subheadings
              └─ CN Code (8-digit)    — ~9,790 specific goods
```

## CN Versions

```php
CnVersion::VERSION_2023 // 2023
CnVersion::VERSION_2024 // 2024
CnVersion::VERSION_2025 // 2025
CnVersion::VERSION_2026 // 2026
```

## Iterating Over Codes

```php
$factory = new CnCodesFactory();
$codes = $factory->getCodes();

// Iterate all codes (loads all versions)
foreach ($codes as $cnCode) {
    echo $cnCode->getCode() . ': ' . $cnCode->getLocalName() . "\n";
}

// Count total codes across all versions
echo count($codes); // ~39,087 codes

// Get codes for a specific version only
$codes2026 = $codes->getAllByVersion(CnVersion::VERSION_2026);
echo count($codes2026); // 9,791 codes

// Convert to array
$array = $codes->toArray();
```

## Data Source

CN classification data is sourced from the
official [EU Vocabularies](https://op.europa.eu/en/web/eu-vocabularies/taxonomies) (RDF/XML format), published by the
Publications Office of the European Union.

Individual datasets can be downloaded per year:

- [Combined Nomenclature 2026](https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/combined-nomenclature-2026)
- [Combined Nomenclature 2025](https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/combined-nomenclature-2025)
- [Combined Nomenclature 2024](https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/combined-nomenclature-2024)
- [Combined Nomenclature 2023](https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/combined-nomenclature-2023)

## Building Translation Files

The library includes console commands to build translation files in different formats.

```bash
# Build all formats
composer translations:build

# Build specific formats
composer translations:php      # PHP arrays (source of truth)
composer translations:yaml     # Symfony YAML format
composer translations:laravel  # Laravel PHP format
```

## API Platform Integration

For Symfony API Platform, expose `CpvCode` with the `localName` property:

```yaml
# config/api_platform/cn_code.yaml
Xterr\CnCodes\CnCode:
  attributes:
    normalization_context:
      groups: [ 'cn_code:read' ]
  properties:
    code:
      groups: [ 'cn_code:read' ]
    name:
      groups: [ 'cn_code:read' ]
    localName:
      groups: [ 'cn_code:read' ]
    version:
      groups: [ 'cn_code:read' ]
    section:
      groups: [ 'cn_code:read' ]
    heading:
      groups: [ 'cn_code:read' ]
    subheading:
      groups: [ 'cn_code:read' ]
    supplementaryUnit:
      groups: [ 'cn_code:read' ]
```

## Testing

```bash
composer install
./vendor/bin/phpunit
```

## Requirements

- PHP >= 8.0
- ext-json

### Optional Dependencies

- `symfony/translation-contracts` - For Symfony integration
- `illuminate/contracts` - For Laravel integration
- `symfony/console` + `symfony/yaml` + `sweetrdf/easyrdf` - For CLI data generation (dev only)

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- [Razvan Ceana](https://github.com/xterr) - Author
- CN code data sourced from the [EU Vocabularies](https://op.europa.eu/en/web/eu-vocabularies/taxonomies)
