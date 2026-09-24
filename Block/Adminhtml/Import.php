<?php

declare(strict_types=1);
namespace Grinet\Magmi2\Block\Adminhtml;

class Import extends \Magento\Backend\Block\Template
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        private readonly \Grinet\Magmi2\Model\ImportService $importer,
        private readonly \Grinet\Magmi2\Model\RuntimeFactory $runtime,
        array $data = [],
    ) { parent::__construct($context, $data); }
    public function getFiles(): array { return $this->importer->files(); }
    public function getProfiles(): array { return $this->runtime->profiles()->list(); }
}
