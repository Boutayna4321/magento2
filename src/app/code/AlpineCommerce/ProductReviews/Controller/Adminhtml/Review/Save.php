<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Controller\Adminhtml\Review;

use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use AlpineCommerce\ProductReviews\Model\ReviewFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductReviews::review';

    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ReviewFactory $reviewFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('*/*/');
        }

        $id = (int) $this->getRequest()->getParam('review_id');

        try {
            $model = $id
                ? $this->reviewRepository->getById($id)
                : $this->reviewFactory->create();

            if (isset($data['review_id']) && (int) $data['review_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_product_review', $data);
                $this->messageManager->addErrorMessage(__('The review was not found.'));
                return $this->_redirect('*/*/edit', ['review_id' => $id]);
            }

            $model->setData($data);
            $this->reviewRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The review has been saved.'));
            $this->dataPersistor->clear('alphacommerce_product_review');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the review: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_product_review', $data);
            return $this->_redirect('*/*/edit', ['review_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['review_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
