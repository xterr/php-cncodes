<?php

namespace Xterr\CnCodes;

use Xterr\CnCodes\Translation\TranslatorInterface;

class CnCodesFactory
{
    /**
     * @var string
     */
    private $baseDirectory;

    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    /**
     * @param string|null              $baseDirectory Base directory for data files
     * @param TranslatorInterface|null $translator    Translator instance (optional)
     */
    public function __construct(?string $baseDirectory = null, ?TranslatorInterface $translator = null)
    {
        $this->baseDirectory = $baseDirectory ?? __DIR__ . '/../Resources';
        $this->translator = $translator;
    }

    /**
     * @return CnCodes
     */
    public function getCodes()
    {
        return new CnCodes($this->baseDirectory, $this->translator);
    }

    /**
     * @return CnSections
     */
    public function getSections()
    {
        return new CnSections($this->baseDirectory, $this->translator);
    }

    /**
     * @return CnChapters
     */
    public function getChapters()
    {
        return new CnChapters($this->baseDirectory, $this->translator);
    }

    /**
     * @return CnHeadings
     */
    public function getHeadings()
    {
        return new CnHeadings($this->baseDirectory, $this->translator);
    }

    /**
     * @return CnSubheadings
     */
    public function getSubheadings()
    {
        return new CnSubheadings($this->baseDirectory, $this->translator);
    }

    /**
     * @return CnCodesMappings
     */
    public function getMappings()
    {
        return new CnCodesMappings($this->baseDirectory);
    }
}
