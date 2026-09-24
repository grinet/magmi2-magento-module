<?php

declare(strict_types=1);
namespace Grinet\Magmi2\Console\Command;

use Grinet\Magmi2\Model\ImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportCommand extends Command
{
    public function __construct(private readonly ImportService $importer) { parent::__construct(); }
    protected function configure(): void
    {
        $this->setName('magmi:import')->setDescription('Import a product file from Magento var/import using the native connection.');
        $this->addOption('file', null, InputOption::VALUE_REQUIRED, 'File relative to var/import');
        $this->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Saved Magmi profile', 'Default');
        $this->addOption('mode', null, InputOption::VALUE_REQUIRED, 'create, update, or xcreate', 'update');
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum records; 0 means all', '0');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($limit === false) { throw new \InvalidArgumentException('Invalid --limit value.'); }
            $report = $this->importer->importFile((string) $input->getOption('file'), (string) $input->getOption('profile'), (string) $input->getOption('mode'), $limit);
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return $report['status'] ? Command::SUCCESS : Command::FAILURE;
        } catch (\Throwable $error) {
            $output->writeln($error->getMessage(), OutputInterface::OUTPUT_RAW);
            return Command::FAILURE;
        }
    }
}
