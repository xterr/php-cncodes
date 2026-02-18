<?php

declare(strict_types=1);

namespace Xterr\CnCodes\Tests\Unit\Translation\Loader;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\Translation\Loader\PhpArrayLoader;
use Xterr\CnCodes\Translation\Loader\TranslationLoaderInterface;

class PhpArrayLoaderTest extends TestCase
{
    public function testImplementsTranslationLoaderInterface(): void
    {
        $loader = new PhpArrayLoader();

        $this->assertInstanceOf(TranslationLoaderInterface::class, $loader);
    }

    public function testLoadReturnsArrayForValidLocale(): void
    {
        $loader = new PhpArrayLoader();
        $translations = $loader->load('de', 'cnCodes');

        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    public function testLoadReturnsEmptyArrayForInvalidLocale(): void
    {
        $loader = new PhpArrayLoader();
        $translations = $loader->load('invalid_locale', 'cnCodes');

        $this->assertIsArray($translations);
        $this->assertEmpty($translations);
    }

    public function testSupportsReturnsTrueForValidLocale(): void
    {
        $loader = new PhpArrayLoader();

        $this->assertTrue($loader->supports('de', 'cnCodes'));
        $this->assertTrue($loader->supports('fr', 'cnCodes'));
    }

    public function testSupportsReturnsFalseForInvalidLocale(): void
    {
        $loader = new PhpArrayLoader();

        $this->assertFalse($loader->supports('invalid_locale', 'cnCodes'));
    }

    public function testGetAvailableLocales(): void
    {
        $loader = new PhpArrayLoader();
        $locales = $loader->getAvailableLocales('cnCodes');

        $this->assertIsArray($locales);
        $this->assertContains('de', $locales);
        $this->assertContains('fr', $locales);
        $this->assertContains('es', $locales);
    }

    public function testLoadCachesResults(): void
    {
        $loader = new PhpArrayLoader();

        $first = $loader->load('de', 'cnCodes');
        $second = $loader->load('de', 'cnCodes');

        $this->assertSame($first, $second);
    }

    public function testNormalizesLocale(): void
    {
        $loader = new PhpArrayLoader();

        $this->assertTrue($loader->supports('DE', 'cnCodes'));
        $this->assertTrue($loader->supports('de_DE', 'cnCodes'));
    }

    public function testCustomBasePath(): void
    {
        $basePath = dirname(__DIR__, 4) . '/Resources/translations/php';
        $loader = new PhpArrayLoader($basePath);

        $translations = $loader->load('de', 'cnCodes');

        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    public function testLoadWithNormalizedLocale(): void
    {
        $loader = new PhpArrayLoader();

        $translations = $loader->load('DE', 'cnCodes');

        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    public function testLoadWithRegionLocale(): void
    {
        $loader = new PhpArrayLoader();

        $translations = $loader->load('de_DE', 'cnCodes');

        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    public function testLoadWithHyphenatedLocale(): void
    {
        $loader = new PhpArrayLoader();

        $translations = $loader->load('de-DE', 'cnCodes');

        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    public function testGetAvailableLocalesCachesResults(): void
    {
        $loader = new PhpArrayLoader();

        $first = $loader->getAvailableLocales('cnCodes');
        $second = $loader->getAvailableLocales('cnCodes');

        $this->assertSame($first, $second);
    }

    public function testGetAvailableLocalesReturnsEmptyForInvalidDomain(): void
    {
        $loader = new PhpArrayLoader();

        $locales = $loader->getAvailableLocales('nonexistent_domain');

        $this->assertIsArray($locales);
        $this->assertEmpty($locales);
    }

    public function testLoadReturnsEmptyForInvalidDomain(): void
    {
        $loader = new PhpArrayLoader();

        $translations = $loader->load('de', 'nonexistent_domain');

        $this->assertIsArray($translations);
        $this->assertEmpty($translations);
    }
}
