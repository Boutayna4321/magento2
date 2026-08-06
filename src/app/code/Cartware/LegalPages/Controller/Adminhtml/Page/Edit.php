<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Controller\Adminhtml\Page;

use Cartware\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends AbstractAction
{
    public function __construct(
        Context $context,
        LegalPageRepositoryInterface $pageRepository,
        Registry $coreRegistry
    ) {
        parent::__construct($context, $pageRepository, $coreRegistry);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('page_id');

        try {
            $model = $id
                ? $this->pageRepository->getById($id)
                : $this->_objectManager->create(\Cartware\LegalPages\Model\LegalPage::class);
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
