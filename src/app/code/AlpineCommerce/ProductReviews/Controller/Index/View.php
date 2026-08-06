<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Controller\Index;

use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $reviewId = (int) $this->getRequest()->getParam('id');

        try {
            $review = $this->reviewRepository->getById($reviewId);
        } catch (\Exception $e) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $this->coreRegistry->register('current_review', $review);

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set($review->getTitle());

        return $page;
    }
}
