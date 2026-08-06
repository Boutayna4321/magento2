<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Controller\Adminhtml\Page;

use AlpineCommerce\LegalPages\Api\LegalPageRepositoryInterface;
use AlpineCommerce\LegalPages\Model\LegalPageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Save extends AbstractAction
{
    public function __construct(
        Context $context,
        LegalPageRepositoryInterface $pageRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly LegalPageFactory $pageModelFactory
    ) {
        parent::__construct($context, $pageRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('legal/page/index');
        }

        $id = (int) $this->getRequest()->getParam('page_id');

        try {
            $model = $id
                ? $this->pageRepository->getById($id)
                : $this->pageModelFactory->create();

            if (isset($data['page_id']) && (int) $data['page_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_legal_page', $data);
                $this->messageManager->addErrorMessage(__('The legal page was not found.'));
                return $this->_redirect('*/*/edit', ['page_id' => $id]);
            }

            $model->setData($data);
            $this->pageRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The legal page has been saved.'));
            $this->dataPersistor->clear('alphacommerce_legal_page');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the legal page: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_legal_page', $data);
            return $this->_redirect('*/*/edit', ['page_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['page_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
