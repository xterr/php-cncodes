<?php

declare(strict_types=1);

namespace Xterr\CnCodes\Translation\Adapter;

use Xterr\CnCodes\Translation\LocaleAwareInterface;
use Xterr\CnCodes\Translation\Loader\PhpArrayLoader;
use Xterr\CnCodes\Translation\Loader\TranslationLoaderInterface;
use Xterr\CnCodes\Translation\TranslatorInterface;

/**
 * Native PHP translator using array-based translations.
 *
 * Zero external dependencies - uses PHP array files directly.
 * This is the recommended translator for standalone usage without frameworks.
 */
final class ArrayTranslator implements TranslatorInterface, LocaleAwareInterface
{
    /**
     * @var TranslationLoaderInterface|null
     */
    private $loader;

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * @var string
     */
    private $fallbackLocale = 'en';

    /**
     * @var string|null
     */
    private $basePath;

    public function __construct(
        ?TranslationLoaderInterface $loader = null,
        ?string $defaultLocale = null,
        ?string $fallbackLocale = null,
        ?string $basePath = null
    ) {
        $this->loader = $loader;
        if ($defaultLocale !== null) {
            $this->locale = $defaultLocale;
        }
        if ($fallbackLocale !== null) {
            $this->fallbackLocale = $fallbackLocale;
        }
        $this->basePath = $basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $id, ?string $locale = null, string $domain = 'cnCodes'): string
    {
        $loader = $this->getLoader();
        $targetLocale = $locale ?? $this->locale;

        // Try target locale
        $translations = $loader->load($targetLocale, $domain);
        if (isset($translations[$id])) {
            return $translations[$id];
        }

        // Try fallback locale
        if ($targetLocale !== $this->fallbackLocale) {
            $translations = $loader->load($this->fallbackLocale, $domain);
            if (isset($translations[$id])) {
                return $translations[$id];
            }
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLocales(): array
    {
        return $this->getLoader()->getAvailableLocales('cnCodes');
    }

    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    private function getLoader(): TranslationLoaderInterface
    {
        if ($this->loader === null) {
            $this->loader = new PhpArrayLoader($this->basePath);
        }

        return $this->loader;
    }
}
