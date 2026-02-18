<?php

namespace Xterr\CnCodes;

use Xterr\CnCodes\Translation\TranslatorInterface;

class CnCodes extends AbstractDatabase
{
    /**
     * @param string                   $baseDirectory
     * @param TranslatorInterface|null $translator
     */
    public function __construct(string $baseDirectory, ?TranslatorInterface $translator = null)
    {
        parent::__construct($baseDirectory, CnCode::class, $translator);
    }

    public function getByCodeAndVersion(string $code, int $version = CnVersion::VERSION_2024): ?CnCode
    {
        return $this->_find('code', [$code, $version])[0] ?? null;
    }

    public function getByRawCodeAndVersion(string $rawCode, int $version = CnVersion::VERSION_2024): ?CnCode
    {
        return $this->_find('rawCode', [$rawCode, $version])[0] ?? null;
    }

    public function getAllByVersion(int $version = CnVersion::VERSION_2024): array
    {
        return $this->_find('version', $version);
    }

    protected function _getIndexDefinition(): array
    {
        return [
            'code' => ['code', 'version'],
            'rawCode' => ['rawCode', 'version'],
            'version' => ['version'],
        ];
    }
}
