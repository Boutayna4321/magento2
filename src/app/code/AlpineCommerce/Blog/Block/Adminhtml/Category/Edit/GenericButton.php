<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Block\Adminhtml\Category\Edit;

use AlpineCommerce\Blog\Api\BlogCategoryRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;

    protected $categoryRepository;

    public function __construct(
        Context $context,
        BlogCategoryRepositoryInterface $categoryRepository
    ) {
        $this->context = $context;
        $this->categoryRepository = $categoryRepository;
    }

    public function getCategoryId()
    {
        try {
            return $this->categoryRepository->getById(
                (int) $this->context->getRequest()->getParam('category_id')
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
