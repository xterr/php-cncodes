<?php

namespace Xterr\CnCodes;

class CnSubheading
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $rawCode;

    /**
     * @var int
     */
    private $version;

    /**
     * @var string
     */
    private $section;

    /**
     * @var string
     */
    private $chapter;

    /**
     * @var string
     */
    private $heading;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $localName;

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRawCode(): string
    {
        return $this->rawCode;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getChapter(): string
    {
        return $this->chapter;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the translated name, or falls back to the English name.
     *
     * @return string
     */
    public function getLocalName(): string
    {
        return $this->localName ?? $this->name;
    }
}
