<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Controller\Adminhtml\Review;

use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\SuccessMessage;

class DeleteList extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductReviews::review';

    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $ids = (array) $this->getRequest()->getParam('selected', []);

        foreach ($ids as $id) {
            try {
                $this->reviewRepository->deleteById((int) $id);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to delete review with ID "%1".', $id));
            }
        }

        if (!empty($ids)) {
            $this->messageManager->addSuccessMessage(__('Total reviews deleted: %1', count($ids)));
        }

        return $this->_redirect('*/*/index');
    }
}
