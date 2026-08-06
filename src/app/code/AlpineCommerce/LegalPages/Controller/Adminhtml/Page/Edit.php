<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Controller\Adminhtml\Page;

use AlpineCommerce\LegalPages\Api\LegalPageRepositoryInterface;
use AlpineCommerce\LegalPages\Model\LegalPageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends AbstractAction
{
    public function __construct(
        Context $context,
        LegalPageRepositoryInterface $pageRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory,
        private readonly LegalPageFactory $pageModelFactory
    ) {
        parent::__construct($context, $pageRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('page_id');

        try {
            $model = $id
                ? $this->pageRepository->getById($id)
                : $this->pageModelFactory->create();
            $this->coreRegistry->register('current_legal_page', $model);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This legal page no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Legal Page') : __('New Legal Page')
        );

        return $page;
    }
}
