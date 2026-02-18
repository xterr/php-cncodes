<?php

declare(strict_types=1);

namespace Xterr\CnCodes\Tests\Unit\Translation\Adapter;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\Translation\Adapter\ArrayTranslator;
use Xterr\CnCodes\Translation\LocaleAwareInterface;
use Xterr\CnCodes\Translation\TranslatorInterface;

class ArrayTranslatorTest extends TestCase
{
    public function testImplementsTranslatorInterface(): void
    {
        $translator = new ArrayTranslator();

        $this->assertInstanceOf(TranslatorInterface::class, $translator);
    }

    public function testImplementsLocaleAwareInterface(): void
    {
        $translator = new ArrayTranslator();

        $this->assertInstanceOf(LocaleAwareInterface::class, $translator);
    }

    public function testDefaultLocaleIsEnglish(): void
    {
        $translator = new ArrayTranslator();

        $this->assertEquals('en', $translator->getLocale());
    }

    public function testSetLocale(): void
    {
        $translator = new ArrayTranslator();
        $translator->setLocale('de');

        $this->assertEquals('de', $translator->getLocale());
    }

    public function testTranslateReturnsOriginalWhenNotFound(): void
    {
        $translator = new ArrayTranslator();
        $result = $translator->translate('Non-existent key');

        $this->assertEquals('Non-existent key', $result);
    }

    public function testTranslateWithGermanLocale(): void
    {
        $translator = new ArrayTranslator(null, 'de');

        $result = $translator->translate('LIVE ANIMALS; ANIMAL PRODUCTS');

        $this->assertEquals('LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS', $result);
    }

    public function testTranslateWithFrenchLocale(): void
    {
        $translator = new ArrayTranslator(null, 'fr');

        $result = $translator->translate('Roses');

        $this->assertEquals('Roses', $result);
    }

    public function testFallbackLocale(): void
    {
        $translator = new ArrayTranslator(null, 'en', 'de');
        $translator->setFallbackLocale('de');

        $this->assertEquals('de', $translator->getFallbackLocale());
    }

    public function testGetAvailableLocales(): void
    {
        $translator = new ArrayTranslator();
        $locales = $translator->getAvailableLocales();

        $this->assertIsArray($locales);
        $this->assertContains('de', $locales);
        $this->assertContains('fr', $locales);
        $this->assertContains('es', $locales);
    }

    public function testTranslateWithLocaleParameter(): void
    {
        $translator = new ArrayTranslator(null, 'en');

        $result = $translator->translate('LIVE ANIMALS; ANIMAL PRODUCTS', 'de');

        $this->assertEquals('LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS', $result);
    }

    public function testFallbackToFallbackLocaleWhenTranslationNotFound(): void
    {
        $translator = new ArrayTranslator(null, 'invalid_locale', 'de');

        $result = $translator->translate('LIVE ANIMALS; ANIMAL PRODUCTS');

        $this->assertEquals('LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS', $result);
    }

    public function testIntegrationWithCnCodes(): void
    {
        $translator = new ArrayTranslator(null, 'de');
        $factory = new CnCodesFactory(null, $translator);

        $codes = $factory->getCodes();
        $code = $codes->getByCodeAndVersion('01012100', 2026);

        $this->assertNotNull($code);
        $this->assertEquals('Pure-bred breeding animals', $code->getName());
        $this->assertNotEquals($code->getName(), $code->getLocalName());
    }

    public function testIntegrationWithCnSections(): void
    {
        $translator = new ArrayTranslator(null, 'de');
        $factory = new CnCodesFactory(null, $translator);

        $sections = $factory->getSections();
        $section = $sections->getByCodeAndVersion('I', 2026);

        $this->assertNotNull($section);
        $this->assertEquals('LIVE ANIMALS; ANIMAL PRODUCTS', $section->getName());
        $this->assertEquals('LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS', $section->getLocalName());
    }

    public function testIntegrationWithCnChapters(): void
    {
        $translator = new ArrayTranslator(null, 'de');
        $factory = new CnCodesFactory(null, $translator);

        $chapters = $factory->getChapters();
        $chapter = $chapters->getByCodeAndVersion('01', 2026);

        $this->assertNotNull($chapter);
        $this->assertNotEquals($chapter->getName(), $chapter->getLocalName());
    }

    public function testIntegrationWithCnHeadings(): void
    {
        $translator = new ArrayTranslator(null, 'de');
        $factory = new CnCodesFactory(null, $translator);

        $headings = $factory->getHeadings();
        $heading = $headings->getByCodeAndVersion('0101', 2026);

        $this->assertNotNull($heading);
        $this->assertNotEquals($heading->getName(), $heading->getLocalName());
    }

    public function testMultipleLocalesWork(): void
    {
        $locales = ['de', 'fr', 'es', 'it', 'pl'];

        foreach ($locales as $locale) {
            $translator = new ArrayTranslator(null, $locale);
            $factory = new CnCodesFactory(null, $translator);

            $sections = $factory->getSections();
            $section = $sections->getByCodeAndVersion('I', 2026);

            $this->assertNotNull($section, "Section not found for locale: $locale");
            $this->assertNotEmpty($section->getLocalName(), "LocalName empty for locale: $locale");
        }
    }

    public function testCustomLoaderInjection(): void
    {
        $loader = $this->createMock(\Xterr\CnCodes\Translation\Loader\TranslationLoaderInterface::class);
        $loader->expects($this->once())
            ->method('load')
            ->with('en', 'cnCodes')
            ->willReturn(['test key' => 'test value']);

        $translator = new ArrayTranslator($loader);

        $result = $translator->translate('test key');

        $this->assertEquals('test value', $result);
    }

    public function testCustomBasePath(): void
    {
        $basePath = dirname(__DIR__, 4) . '/Resources/translations/php';
        $translator = new ArrayTranslator(null, 'de', 'en', $basePath);

        $result = $translator->translate('LIVE ANIMALS; ANIMAL PRODUCTS');

        $this->assertEquals('LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS', $result);
    }

    public function testNoFallbackWhenTargetEqualsAndNotFound(): void
    {
        $translator = new ArrayTranslator(null, 'en', 'en');

        $result = $translator->translate('non-existent-key');

        $this->assertEquals('non-existent-key', $result);
    }

    public function testFallbackNotUsedWhenTargetLocaleHasTranslation(): void
    {
        $translator = new ArrayTranslator(null, 'de', 'fr');

        $result = $translator->translate('LIVE ANIMALS; ANIMAL PRODUCTS');

        $this->assertEquals('LEBENDE TIERE UND WAREN TIERISCHEN URSPRUNGS', $result);
    }
}
