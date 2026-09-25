<?php
/** Test Magento only. Usage: php tests/magento-smoke.php /path/to/magento /path/to/core/vendor/autoload.php */
declare(strict_types=1);
$magento = $argv[1] ?? '';
$coreAutoload = $argv[2] ?? '';
if (!is_file($magento . '/app/bootstrap.php') || !is_file($coreAutoload)) {
    throw new RuntimeException('Provide Magento root and a Composer autoloader containing Magmi core.');
}
require $coreAutoload;
require $magento . '/app/bootstrap.php';
if (!class_exists(\Grinet\Magmi2\Model\RuntimeFactory::class)) {
    $loader = new \Composer\Autoload\ClassLoader();
    $loader->addPsr4('Grinet\\Magmi2\\', dirname(__DIR__));
    $loader->register();
}
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$factory = $om->create(\Grinet\Magmi2\Model\RuntimeFactory::class);
$defaultExisted = is_file($factory->basePath() . '/data/config/Default.json');
$runtime = $factory->create();
$importer = $om->create(\Grinet\Magmi2\Model\ImportService::class);
$sku = 'MAGMI-MODULE-VERIFY-' . bin2hex(random_bytes(8));
$file = $factory->importPath() . '/' . $sku . '.csv';
$database = $runtime->database;
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) { throw new RuntimeException($label); }
    $checks++; echo "PASS: {$label}\n";
};
try {
    $native = $om->get(\Magento\Framework\App\ResourceConnection::class);
    $check($database->getPdo() === $native->getConnection()->getConnection(), 'Native Magento PDO connection is reused');
    $check($database->tableName('catalog_product_entity') === $native->getTableName('catalog_product_entity'), 'Magento table prefix is respected');
    $check(realpath($runtime->context['magento_path']) === realpath($magento), 'Magento root is detected automatically');
    $check(!file_exists($runtime->configDirectory() . '/global.json'), 'No duplicated DB credentials are written');
    $pump = $importer->createDataPump();
    $pump->beginImportSession('Default', 'create');
    $check($pump->ingest(['sku' => $sku, 'name' => $sku, 'price' => 11, 'status' => 2, 'visibility' => 1]), 'Magento service creates a product immediately');
    $check($pump->endImportSession()['processed'] === 1, 'Magento service reports the created product');
    if (!is_dir(dirname($file))) { mkdir(dirname($file), 0755, true); }
    file_put_contents($file, "sku,name\n{$sku},Module CLI verified\n");
    $command = $om->create(\Grinet\Magmi2\Console\Command\ImportCommand::class);
    $tester = new \Symfony\Component\Console\Tester\CommandTester($command);
    $exitCode = $tester->execute(['--file' => basename($file), '--mode' => 'update']);
    $report = json_decode($tester->getDisplay(), true);
    $check($exitCode === 0 && ($report['processed'] ?? 0) === 1, 'Magento CLI command executes import with automatic settings');
    $id = $database->selectOne('SELECT entity_id FROM ' . $database->tableName('catalog_product_entity') . ' WHERE sku=?', [$sku], 'entity_id');
    $values = $database->tableName('catalog_product_entity_varchar');
    $attrs = $database->tableName('eav_attribute');
    $name = $database->selectOne("SELECT v.value FROM {$values} v JOIN {$attrs} a ON a.attribute_id=v.attribute_id WHERE entity_id=? AND attribute_code='name' AND store_id=0", [$id], 'value');
    $check($name === 'Module CLI verified', 'CLI changes are confirmed by reading Magento EAV data');
    try { $importer->resolveFile('../../app/etc/env.php'); $rejected = false; }
    catch (InvalidArgumentException) { $rejected = true; }
    $check($rejected, 'Files outside var/import are rejected');
    echo "{$checks} Magento module checks passed.\n";
} finally {
    $database->delete('DELETE FROM ' . $database->tableName('catalog_product_entity') . ' WHERE sku=?', [$sku]);
    $database->delete('DELETE FROM ' . $database->tableName('inventory_source_item') . ' WHERE sku=?', [$sku]);
    if (is_file($file)) { unlink($file); }
    if (!$defaultExisted) { unlink($factory->basePath() . '/data/config/Default.json'); }
    echo "Temporary test product and input file removed.\n";
}
