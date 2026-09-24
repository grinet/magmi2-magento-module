<?php

declare(strict_types=1);
namespace Grinet\Magmi2\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Manager;
use Magmi\Core\Config\Profile;
use Magmi\Core\Config\ProfileManager;
use Magmi\Core\Config\RuntimeConfig;
use Magmi\Core\Database\DbHelper;

final class RuntimeFactory
{
    public function __construct(
        private readonly DirectoryList $directories,
        private readonly ResourceConnection $resource,
        private readonly Manager $modules,
    ) {}

    public function basePath(): string { return $this->directories->getPath(DirectoryList::VAR_DIR) . '/magmi'; }
    public function importPath(): string { return $this->directories->getPath(DirectoryList::VAR_DIR) . '/import'; }

    public function profiles(): ProfileManager
    {
        $profiles = new ProfileManager($this->basePath() . '/data/config');
        if ($profiles->load('Default') === null) { $profiles->save(new Profile('Default')); }
        return $profiles;
    }

    public function create(array $context = []): RuntimeConfig
    {
        $this->profiles();
        $connection = $this->resource->getConnection()->getConnection();
        if (!$connection instanceof \PDO) { throw new \RuntimeException('Magento must provide a PDO connection.'); }
        return new RuntimeConfig(
            $this->basePath(),
            DbHelper::fromPdo($connection, $this->resource->getTablePrefix()),
            array_merge($context, [
                'magento_path' => $this->directories->getRoot(),
                'magento_media_dir' => $this->directories->getPath(DirectoryList::MEDIA),
                'enable_msi' => $this->modules->isEnabled('Magento_Inventory'),
            ])
        );
    }
}
