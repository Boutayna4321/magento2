<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Controller\Adminhtml\Faq;

use AlpineCommerce\Faq\Api\FaqRepositoryInterface;
use AlpineCommerce\Faq\Model\FaqFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Faq::faq';

    public function __construct(
        Context $context,
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly FaqFactory $faqFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('faq/faq/index');
        }

        $id = (int) $this->getRequest()->getParam('faq_id');

        try {
            $model = $id
                ? $this->faqRepository->getById($id)
                : $this->faqFactory->create();

            if (isset($data['faq_id']) && (int) $data['faq_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_faq', $data);
                $this->messageManager->addErrorMessage(__('The FAQ entry was not found.'));
                return $this->_redirect('*/*/edit', ['faq_id' => $id]);
            }

            $model->setData($data);
            $this->faqRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The FAQ entry has been saved.'));
            $this->dataPersistor->clear('alphacommerce_faq');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the FAQ entry: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_faq', $data);
            return $this->_redirect('*/*/edit', ['faq_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['faq_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
