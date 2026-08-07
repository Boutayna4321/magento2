<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Block\Adminhtml\Review\Edit;

use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;

    protected $reviewRepository;

    public function __construct(
        Context $context,
        ReviewRepositoryInterface $reviewRepository
    ) {
        $this->context = $context;
        $this->reviewRepository = $reviewRepository;
    }

    public function getReviewId()
    {
        try {
            return $this->reviewRepository->getById(
                (int) $this->context->getRequest()->getParam('review_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }

        return null;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
