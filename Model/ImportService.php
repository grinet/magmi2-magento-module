<?php

declare(strict_types=1);
namespace Grinet\Magmi2\Model;

use Magmi\Core\Datasource\DatasourceFactory;
use Magmi\Core\Import\DataPumpInterface;
use Magmi\Core\Import\ProductImportDataPump;

final class ImportService
{
    public function __construct(private readonly RuntimeFactory $runtime) {}

    /** Inject this service into Magento code; no path or DB settings required. */
    public function createDataPump(): DataPumpInterface
    {
        return new ProductImportDataPump($this->runtime->create());
    }

    public function files(): array
    {
        $files = [];
        foreach (glob($this->runtime->importPath() . '/*') ?: [] as $file) {
            try { $this->resolveFile(basename($file)); $files[] = basename($file); }
            catch (\InvalidArgumentException) { continue; }
        }
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return $files;
    }

    public function resolveFile(string $relative): string
    {
        $root = realpath($this->runtime->importPath());
        $file = $root === false ? false : realpath($root . '/' . $relative);
        if ($relative === '' || $file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)
            || !is_file($file) || !is_readable($file)
            || !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['csv', 'tsv', 'xls', 'xlsx'], true)) {
            throw new \InvalidArgumentException('Select a readable CSV, TSV, XLS or XLSX file inside Magento var/import.');
        }
        return $file;
    }

    public function importFile(string $file, string $profile = 'Default', string $mode = 'update', int $limit = 0): array
    {
        if ($limit < 0) { throw new \InvalidArgumentException('Limit must be zero or positive.'); }
        $path = $this->resolveFile($file);
        $settings = $this->runtime->profiles()->load($profile);
        if ($settings === null) { throw new \InvalidArgumentException('Profile not found: ' . $profile); }
        $source = DatasourceFactory::open($path, $settings->getDatasourceConfig());
        $pump = new ProductImportDataPump($this->runtime->create(['import_file' => $path, 'import_dir' => dirname($path)]));
        $started = false;
        try {
            $pump->beginImportSession($profile, $mode);
            $started = true;
            $count = 0;
            foreach ($source->getRows() as $row) {
                if ($limit > 0 && $count >= $limit) { break; }
                $pump->ingest($row);
                $count++;
            }
        } finally {
            $source->close();
            if ($started) { $report = $pump->endImportSession(); }
        }
        return $report;
    }
}
