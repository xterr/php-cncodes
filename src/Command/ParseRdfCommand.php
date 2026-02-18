<?php

declare(strict_types=1);

namespace Xterr\CnCodes\Command;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use EasyRdf\Resource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Xterr\CnCodes\CnVersion;

/**
 * Console command to parse CN RDF files and generate resource files.
 *
 * Parses the official CN (Combined Nomenclature) classification RDF files from
 * EU Vocabularies and generates:
 * - JSON data files for sections, chapters, headings, subheadings, and CN codes
 * - YAML translation files for all available languages
 */
class ParseRdfCommand extends Command
{
    private const VALID_YEARS = [
        CnVersion::VERSION_2023,
        CnVersion::VERSION_2024,
        CnVersion::VERSION_2025,
        CnVersion::VERSION_2026,
    ];

    private const URI_BASE = 'http://data.europa.eu/xsp/cn';
    private const SU_BASE = 'http://data.europa.eu/gzn/su/';

    private const COLLECTION_LEVELS = [
        'sections' => 'section',
        'chapters' => 'chapter',
        'headings' => 'heading',
        'hs_subheadings' => 'hs_subheading',
        'cn_subheadings' => 'cn_subheading',
    ];

    public function __construct()
    {
        parent::__construct('cn:parse-rdf');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Parse CN RDF file and generate resource files')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Path to the CN RDF file'
            )
            ->addOption(
                'year',
                'y',
                InputOption::VALUE_REQUIRED,
                'CN version year (2024, 2025, or 2026)'
            )
            ->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output directory for generated files',
                dirname(__DIR__, 2) . '/Resources'
            )
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command parses CN classification RDF files and generates
JSON data files and YAML translation files.

Example:
  <info>%command.full_name% --file=CN_2025.rdf --year=2025</info>
  <info>%command.full_name% -f CN_2025.rdf -y 2025 -o ./output</info>

The RDF file can be downloaded from EU Vocabularies:
https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/combined-nomenclature-2025
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filename = $input->getOption('file');
        $yearString = $input->getOption('year');
        $outputDir = $input->getOption('output-dir');

        if (!$filename) {
            $io->error('File option is required. Use --file or -f to specify the RDF file.');
            return Command::FAILURE;
        }

        if (!file_exists($filename)) {
            $io->error(sprintf('File not found: %s', $filename));
            return Command::FAILURE;
        }

        if (!$yearString) {
            $io->error('The --year option is required. Valid values: 2024, 2025, 2026');
            return Command::FAILURE;
        }

        $year = (int) $yearString;
        if (!in_array($year, self::VALID_YEARS, true)) {
            $io->error(sprintf('Invalid year: %d. Valid values: 2024, 2025, 2026', $year));
            return Command::FAILURE;
        }

        // Large RDF/XML files need significant memory
        ini_set('memory_limit', '4G');
        // Register m8g namespace for supplementary unit URIs
        RdfNamespace::set('m8g', 'http://data.europa.eu/m8g/');

        $io->title(sprintf('Parsing CN %d RDF File', $year));
        $io->text(sprintf('Input file: <info>%s</info>', $filename));
        $io->text(sprintf('Output directory: <info>%s</info>', $outputDir));

        $uriBase = self::URI_BASE . $year . '/';

        $doc = new Graph();
        $io->text('Loading RDF file (this may take a while for large files)...');
        $doc->parseFile($filename);

        // Step 1: Build collection membership map
        $io->section('Step 1: Parsing collection membership...');
        $collectionMap = [];

        foreach (self::COLLECTION_LEVELS as $collectionSuffix => $levelType) {
            $collectionUri = $uriBase . $collectionSuffix;
            $members = $doc->allResources($collectionUri, 'skos:member');

            foreach ($members as $member) {
                $collectionMap[$member->getUri()] = $levelType;
            }

            $io->text(sprintf('  %s: <info>%d</info> members', $collectionSuffix, count($members)));
        }

        $io->text(sprintf('Total collection members: <info>%d</info>', count($collectionMap)));

        if (empty($collectionMap)) {
            $io->error('No collection members found. Ensure the RDF file contains collection data (skos:member triples).');
            return Command::FAILURE;
        }

        // Step 2: Index all concepts — identify coded vs grouping nodes
        $io->section('Step 2: Indexing concepts...');
        $notationByUri = [];
        $groupingNodeCount = 0;
        $conceptCount = 0;

        foreach ($doc->resources() as $uri => $resource) {
            if (!$resource->isA('skos:Concept')) {
                continue;
            }

            $conceptCount++;
            $notation = $resource->get('skos:notation');

            if ($notation !== null) {
                $notationByUri[$uri] = $notation->getValue();
            } else {
                $groupingNodeCount++;
            }
        }

        $io->text(sprintf(
            'Found <info>%d</info> concepts: <info>%d</info> coded, <info>%d</info> grouping nodes',
            $conceptCount,
            count($notationByUri),
            $groupingNodeCount
        ));

        // Step 3: Process concepts — extract data and translations
        $io->section('Step 3: Processing concepts...');

        $sections = [];
        $chapters = [];
        $headings = [];
        $subheadings = [];
        $cnCodes = [];
        $aTranslations = [];

        foreach ($collectionMap as $uri => $level) {
            $resource = $doc->resource($uri);

            if (!$resource->isA('skos:Concept')) {
                continue;
            }

            $notation = $resource->get('skos:notation');
            if ($notation === null) {
                continue;
            }

            $notationValue = $notation->getValue();
            $code = str_replace(' ', '', $notationValue);

            // Get English name from altLabel
            $name = '';
            foreach ($resource->all('skos:altLabel') as $altLabel) {
                if ($altLabel->getLang() === 'en') {
                    $name = $altLabel->getValue();
                }
            }
            $name = $this->cleanName($name, $level);

            // Collect non-English translations
            foreach ($resource->all('skos:altLabel') as $altLabel) {
                if ($altLabel->getLang() !== 'en') {
                    $translatedName = $this->cleanName($altLabel->getValue(), $level);
                    $aTranslations[$altLabel->getLang()][$name] = $translatedName;
                }
            }

            $entry = [
                'code' => $code,
                'rawCode' => $notationValue,
                'name' => $name,
                'version' => $year,
            ];

            $ancestry = $this->resolveAncestry($resource, $notationByUri, $collectionMap);

            switch ($level) {
                case 'section':
                    $sections[$notationValue] = $entry;
                    break;

                case 'chapter':
                    $entry['section'] = $ancestry['section'] ?? null;
                    $chapters[$notationValue] = $entry;
                    break;

                case 'heading':
                    $entry['chapter'] = $ancestry['chapter'] ?? null;
                    $entry['section'] = $ancestry['section'] ?? null;
                    $headings[$notationValue] = $entry;
                    break;

                case 'hs_subheading':
                    $entry['heading'] = $ancestry['heading'] ?? null;
                    $entry['chapter'] = $ancestry['chapter'] ?? null;
                    $entry['section'] = $ancestry['section'] ?? null;
                    $subheadings[$notationValue] = $entry;
                    break;

                case 'cn_subheading':
                    $entry['subheading'] = $ancestry['hs_subheading'] ?? null;
                    $entry['heading'] = $ancestry['heading'] ?? null;
                    $entry['chapter'] = $ancestry['chapter'] ?? null;
                    $entry['section'] = $ancestry['section'] ?? null;
                    $entry['supplementaryUnit'] = $this->getSupplementaryUnit($resource);
                    $cnCodes[$notationValue] = $entry;
                    break;
            }
        }

        $io->text(sprintf('Sections: <info>%d</info>', count($sections)));
        $io->text(sprintf('Chapters: <info>%d</info>', count($chapters)));
        $io->text(sprintf('Headings: <info>%d</info>', count($headings)));
        $io->text(sprintf('Subheadings (HS): <info>%d</info>', count($subheadings)));
        $io->text(sprintf('CN Codes: <info>%d</info>', count($cnCodes)));
        $io->text(sprintf('Skipped <info>%d</info> grouping nodes', $groupingNodeCount));

        // Step 4: Write translation files
        $io->section('Step 4: Writing translation files...');
        $translationsDir = $outputDir . '/translations';
        if (!is_dir($translationsDir)) {
            mkdir($translationsDir, 0755, true);
        }

        foreach ($aTranslations as $language => $messages) {
            $yaml = Yaml::dump($messages, 2, 2);
            $filePath = sprintf('%s/messages_%s.yaml', $translationsDir, $language);
            file_put_contents($filePath, $yaml);
            $io->text(sprintf('  -> <info>messages_%s.yaml</info> (%d translations)', $language, count($messages)));
        }

        // Step 5: Write JSON files
        $io->section('Step 5: Writing JSON files...');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->writeJson($outputDir . "/cnSections_{$year}.json", $sections, $io);
        $this->writeJson($outputDir . "/cnChapters_{$year}.json", $chapters, $io);
        $this->writeJson($outputDir . "/cnHeadings_{$year}.json", $headings, $io);
        $this->writeJson($outputDir . "/cnSubheadings_{$year}.json", $subheadings, $io);
        $this->writeJson($outputDir . "/cnCodes_{$year}.json", $cnCodes, $io);

        $io->success(sprintf('All CN %d resource files generated successfully!', $year));

        return Command::SUCCESS;
    }

    /**
     * Cleans a concept name based on its level.
     *
     * Sections: "SECTION I - LIVE ANIMALS..." → "LIVE ANIMALS..."
     * Chapters: "CHAPTER 1 - LIVE ANIMALS" → "LIVE ANIMALS"
     * Others: "-- Pure-bred..." → "Pure-bred..."
     *
     * @param string $name
     * @param string $level
     *
     * @return string
     */
    private function cleanName(string $name, string $level): string
    {
        switch ($level) {
            case 'section':
                // Language-agnostic: "{WORD(S)} {ROMAN} - {NAME}" → "{NAME}"
                if (preg_match('/^.+\s+[IVXLCDM]+\s*-\s*(.+)$/i', $name, $matches)) {
                    return trim($matches[1]);
                }
                return $name;

            case 'chapter':
                // Language-agnostic: "{WORD(S)} {NUMBER} - {NAME}" → "{NAME}"
                if (preg_match('/^.+\s+\d+\s*-\s*(.+)$/i', $name, $matches)) {
                    return trim($matches[1]);
                }
                return $name;

            default:
                return ltrim($name, '- ');
        }
    }

    /**
     * Walks skos:broader chain, skipping grouping nodes (no notation / not in collection),
     * collecting coded ancestors at each level.
     *
     * CN code → grouping → HS sub → grouping → heading → chapter → section
     * Returns: ['hs_subheading' => '010121', 'heading' => '0101', 'chapter' => '01', 'section' => 'I']
     *
     * @param Resource             $resource
     * @param array<string,string> $notationByUri
     * @param array<string,string> $collectionMap
     *
     * @return array<string, string> level type => code (spaces removed)
     */
    private function resolveAncestry(Resource $resource, array $notationByUri, array $collectionMap): array
    {
        $ancestry = [];
        $current = $resource;
        $visited = [];

        for ($i = 0; $i < 20; $i++) {
            $broader = $current->get('skos:broader');

            if ($broader === null || !($broader instanceof Resource)) {
                break;
            }

            $broaderUri = $broader->getUri();

            if (isset($visited[$broaderUri])) {
                break;
            }
            $visited[$broaderUri] = true;

            if (isset($notationByUri[$broaderUri]) && isset($collectionMap[$broaderUri])) {
                $level = $collectionMap[$broaderUri];
                if (!isset($ancestry[$level])) {
                    $ancestry[$level] = str_replace(' ', '', $notationByUri[$broaderUri]);
                }
            }

            $current = $broader;
        }

        return $ancestry;
    }

    /**
     * Extracts unit code from m8g:statUnitMeasure URI suffix (e.g. ".../su/PST" → "PST").
     * Returns null for NO_SU or absent property.
     *
     * @param Resource $resource
     *
     * @return string|null
     */
    private function getSupplementaryUnit(Resource $resource): ?string
    {
        $unit = $resource->get('m8g:statUnitMeasure');

        if ($unit === null || !($unit instanceof Resource)) {
            return null;
        }

        $unitUri = $unit->getUri();

        if (strpos($unitUri, self::SU_BASE) === false) {
            return null;
        }

        $unitCode = substr($unitUri, strlen(self::SU_BASE));

        if ($unitCode === 'NO_SU') {
            return null;
        }

        return $unitCode;
    }

    /**
     * Writes data array to a JSON file.
     *
     * @param string               $path
     * @param array<string, array> $data
     * @param SymfonyStyle         $io
     *
     * @return void
     */
    private function writeJson(string $path, array $data, SymfonyStyle $io): void
    {
        $json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $json);
        $io->text(sprintf('  -> <info>%s</info> (%d entries)', basename($path), count($data)));
    }
}
