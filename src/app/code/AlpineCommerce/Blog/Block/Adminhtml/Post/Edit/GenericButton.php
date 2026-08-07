<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Block\Adminhtml\Post\Edit;

use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;

    protected $postRepository;

    public function __construct(
        Context $context,
        BlogPostRepositoryInterface $postRepository
    ) {
        $this->context = $context;
        $this->postRepository = $postRepository;
    }

    public function getPostId()
    {
        try {
            return $this->postRepository->getById(
                (int) $this->context->getRequest()->getParam('post_id')
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
