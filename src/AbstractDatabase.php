<?php

namespace Xterr\CnCodes;

use Closure;
use Countable;
use Iterator;
use Xterr\CnCodes\Translation\TranslatorInterface;

/**
 * @template-implements Iterator<mixed>
 */
abstract class AbstractDatabase implements Iterator, Countable
{
    protected $data = [];

    /** @var string */
    private $baseDirectory;

    /** @var TranslatorInterface|null */
    private $translator;

    private $class;

    /** @var array<string, array<string, array>> Per-index lookup: index name => key => entries */
    private $index = [];

    /** @var array<int, bool> Tracks which versions have been loaded */
    private $loadedVersions = [];

    /** @var bool Whether all versions have been loaded (for iteration/count) */
    private $allLoaded = false;

    /**
     * @param string                   $baseDirectory
     * @param string                   $class
     * @param TranslatorInterface|null $translator
     */
    public function __construct(string $baseDirectory, string $class, ?TranslatorInterface $translator = null)
    {
        $this->baseDirectory = $baseDirectory;
        $this->class = $class;
        $this->translator = $translator;
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    public function count(): int
    {
        $this->_loadAllVersions();

        return count($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function valid(): bool
    {
        return $this->key() !== null;
    }

    public function key(): ?int
    {
        return key($this->data);
    }

    public function rewind(): void
    {
        $this->_loadAllVersions();
        reset($this->data);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_arrayToEntry(current($this->data));
    }

    protected function _arrayToEntry(array $entry)
    {
        $class = $this->class;
        $translator = $this->translator;

        $closure = Closure::bind(static function () use ($entry, $class, $translator) {
            $instance = new $class();

            foreach (array_keys($entry) as $field) {
                $instance->$field = $entry[$field];
            }

            if ($translator !== null && isset($entry['name'])) {
                $instance->localName = $translator->translate($entry['name']);
            }

            return $instance;
        }, null, $class);

        return $closure();
    }

    protected function _find(string $indexedFieldName, $value): array
    {
        $version = $this->_extractVersion($indexedFieldName, $value);

        if ($version !== null) {
            $this->_loadVersion($version);
        } else {
            $this->_loadAllVersions();
        }

        if (!isset($this->index[$indexedFieldName])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown index "%s" in database "%s"',
                    $indexedFieldName,
                    static::class
                )
            );
        }

        return $this->index[$indexedFieldName][is_array($value) ? implode('_', $value) : $value] ?? [];
    }

    protected function _fileName(): string
    {
        return lcfirst(basename(str_replace('\\', '/', static::class)));
    }

    protected function _getIndexDefinition(): array
    {
        return [];
    }

    private function _extractVersion(string $indexedFieldName, $value): ?int
    {
        if ($indexedFieldName === 'version') {
            return is_int($value) ? $value : null;
        }

        $indexDef = $this->_getIndexDefinition();

        if (!isset($indexDef[$indexedFieldName])) {
            return null;
        }

        $fields = is_array($indexDef[$indexedFieldName]) ? $indexDef[$indexedFieldName] : [$indexDef[$indexedFieldName]];
        $versionPos = array_search('version', $fields, true);

        if ($versionPos === false || !is_array($value)) {
            return null;
        }

        $values = array_values($value);

        return isset($values[$versionPos]) && is_int($values[$versionPos]) ? $values[$versionPos] : null;
    }

    private function _loadVersion(int $version): void
    {
        if (isset($this->loadedVersions[$version])) {
            return;
        }

        $file = $this->baseDirectory . DIRECTORY_SEPARATOR . $this->_fileName() . '_' . $version . '.json';

        if (file_exists($file)) {
            $versionData = json_decode(file_get_contents($file), true);

            if (is_array($versionData)) {
                $this->data = array_merge($this->data, $versionData);
                $this->_indexData($versionData);
            }
        }

        $this->loadedVersions[$version] = true;
    }

    private function _loadAllVersions(): void
    {
        if ($this->allLoaded) {
            return;
        }

        $pattern = $this->baseDirectory . DIRECTORY_SEPARATOR . $this->_fileName() . '_*.json';

        foreach (glob($pattern) ?: [] as $file) {
            if (preg_match('/_(\d+)\.json$/', $file, $m)) {
                $version = (int) $m[1];

                if (isset($this->loadedVersions[$version])) {
                    continue;
                }

                $versionData = json_decode(file_get_contents($file), true);

                if (is_array($versionData)) {
                    $this->data = array_merge($this->data, $versionData);
                    $this->_indexData($versionData);
                }

                $this->loadedVersions[$version] = true;
            }
        }

        $this->allLoaded = true;
    }

    private function _indexData(array $entries): void
    {
        $indexedFields = $this->_getIndexDefinition();

        if (empty($indexedFields)) {
            return;
        }

        foreach ($entries as $entryArray) {
            $entry = $this->_arrayToEntry($entryArray);

            foreach ($indexedFields as $indexName => $indexDefinition) {
                if (!isset($this->index[$indexName])) {
                    $this->index[$indexName] = [];
                }

                $indexDefinition = is_array($indexDefinition) ? $indexDefinition : [$indexDefinition];

                $values = array_map(static function ($field) use ($entryArray) {
                    return $entryArray[$field];
                }, $indexDefinition);

                $key = implode('_', $values);

                if (!isset($this->index[$indexName][$key])) {
                    $this->index[$indexName][$key] = [];
                }

                $this->index[$indexName][$key][] = $entry;
            }
        }
    }
}
