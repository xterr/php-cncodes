<?php

namespace Xterr\CnCodes;

class CnCodesMappings
{
    /**
     * @var string
     */
    private $baseDirectory;

    /**
     * @var array|null
     */
    private $data;

    public function __construct(?string $baseDirectory = null)
    {
        $this->baseDirectory = $baseDirectory ?? __DIR__ . '/../Resources';
    }

    /**
     * Returns all mapping targets for a given code and version (many-to-many).
     *
     * @param string $code
     * @param int    $fromVersion
     *
     * @return array Array of [to_code, to_version] pairs
     */
    public function getMapping(string $code, int $fromVersion): array
    {
        $this->_loadData();

        $results = [];

        foreach ($this->data as $mapping) {
            if ($mapping['from_code'] === $code && $mapping['from_version'] === $fromVersion) {
                $results[] = [$mapping['to_code'], $mapping['to_version']];
            }
        }

        return $results;
    }

    private function _loadData(): void
    {
        if ($this->data !== null) {
            return;
        }

        $file = $this->baseDirectory . DIRECTORY_SEPARATOR . 'cnCodesMapping.json';

        if (!file_exists($file)) {
            $this->data = [];

            return;
        }

        $this->data = json_decode(file_get_contents($file), true) ?: [];
    }
}
