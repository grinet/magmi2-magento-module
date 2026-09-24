<?php

declare(strict_types=1);
namespace Grinet\Magmi2\Controller\Adminhtml\Import;

class Index extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Grinet_Magmi2::import';
    public function __construct(\Magento\Backend\App\Action\Context $context, private readonly \Magento\Backend\Model\View\Result\PageFactory $pages) { parent::__construct($context); }
    public function execute()
    {
        $page = $this->pages->create();
        $page->setActiveMenu('Grinet_Magmi2::import');
        $page->getConfig()->getTitle()->prepend(__('Magmi Import'));
        return $page;
    }
}
