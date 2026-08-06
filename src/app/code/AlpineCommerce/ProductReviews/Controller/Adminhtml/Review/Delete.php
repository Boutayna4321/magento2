<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Controller\Adminhtml\Review;

use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
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
        $id = (int) $this->getRequest()->getParam('review_id');

        try {
            $this->reviewRepository->delete($this->reviewRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The review has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This review no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the review.'));
        }

        return $this->_redirect('*/*/index');
    }
}
