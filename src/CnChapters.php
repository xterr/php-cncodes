<?php

namespace Xterr\CnCodes;

use Xterr\CnCodes\Translation\TranslatorInterface;

class CnChapters extends AbstractDatabase
{
    /**
     * @param string                   $baseDirectory
     * @param TranslatorInterface|null $translator
     */
    public function __construct(string $baseDirectory, ?TranslatorInterface $translator = null)
    {
        parent::__construct($baseDirectory, CnChapter::class, $translator);
    }

    public function getByCodeAndVersion(string $code, int $version = CnVersion::VERSION_2024): ?CnChapter
    {
        $entries = $this->_find('code', [$code, $version]);

        return $entries[0] ?? null;
    }

    public function getAllByVersion(int $version = CnVersion::VERSION_2024): array
    {
        return $this->_find('version', $version);
    }

    protected function _getIndexDefinition(): array
    {
        return [
            'code' => ['code', 'version'],
            'version' => ['version'],
        ];
    }
}
