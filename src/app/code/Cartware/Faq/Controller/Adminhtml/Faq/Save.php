<?php
declare(strict_types=1);

namespace Cartware\Faq\Controller\Adminhtml\Faq;

use Cartware\Faq\Api\FaqRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Cartware_Faq::faq';

    public function __construct(
        Context $context,
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly DataPersistorInterface $dataPersistor
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
                : $this->_objectManager->create(\Cartware\Faq\Model\Faq::class);

            if (isset($data['faq_id']) && (int) $data['faq_id'] !== $id) {
                $this->dataPersistor->set('cartware_faq', $data);
                $this->messageManager->addErrorMessage(__('The FAQ entry was not found.'));
                return $this->_redirect('*/*/edit', ['faq_id' => $id]);
            }

            $model->setData($data);
            $this->faqRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The FAQ entry has been saved.'));
            $this->dataPersistor->clear('cartware_faq');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the FAQ entry: %1', $e->getMessage()));
            $this->dataPersistor->set('cartware_faq', $data);
            return $this->_redirect('*/*/edit', ['faq_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['faq_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
