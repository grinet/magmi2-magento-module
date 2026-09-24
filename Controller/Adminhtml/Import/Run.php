<?php

declare(strict_types=1);
namespace Grinet\Magmi2\Controller\Adminhtml\Import;

class Run extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Grinet_Magmi2::import';
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly \Grinet\Magmi2\Model\ImportService $importer,
        private readonly \Magento\Framework\Data\Form\FormKey\Validator $formKey,
    ) { parent::__construct($context); }
    public function execute()
    {
        try {
            if (!$this->formKey->validate($this->getRequest())) { throw new \InvalidArgumentException('Invalid form key. Refresh the page.'); }
            $limit = filter_var($this->getRequest()->getParam('limit', 100), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
            if ($limit === false) { throw new \InvalidArgumentException('Web imports accept a row limit between 1 and 1000.'); }
            $report = $this->importer->importFile(
                (string) $this->getRequest()->getParam('file', ''),
                (string) $this->getRequest()->getParam('profile', 'Default'),
                (string) $this->getRequest()->getParam('mode', 'update'),
                $limit
            );
            $message = __('Processed: %1. Skipped: %2. Errors: %3.', $report['processed'], $report['skipped'], count($report['errors']));
            if ($report['status']) { $this->messageManager->addSuccessMessage($message); }
            else { $this->messageManager->addErrorMessage($message); }
            foreach (array_slice($report['errors'], 0, 3) as $error) { $this->messageManager->addErrorMessage($error['message']); }
            $this->messageManager->addNoticeMessage(__('Refresh the relevant Magento indexes before checking storefront results.'));
        } catch (\Throwable $error) { $this->messageManager->addErrorMessage($error->getMessage()); }
        return $this->resultRedirectFactory->create()->setPath('magmi2/import/index');
    }
}
