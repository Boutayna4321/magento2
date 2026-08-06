<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Controller\Adminhtml\Page;

use Cartware\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class AbstractAction extends Action
{
    public const ADMIN_RESOURCE = 'Cartware_LegalPages::legal';

    public function __construct(
        Context $context,
        protected readonly LegalPageRepositoryInterface $pageRepository,
        protected readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    protected function initPage()
    {
        $page = $this->_objectManager->create(\Magento\Framework\View\Result\PageFactory::class)->create();
        $page->setActiveMenu('Cartware_LegalPages::menu');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('Legal Pages'), __('Legal Pages'));

        return $page;
    }
}
