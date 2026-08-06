<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Index;

use Cartware\Blog\Api\BlogPostRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly BlogPostRepositoryInterface $postRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $urlKey = (string) $this->getRequest()->getParam('url_key');
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('url_key', $urlKey)
            ->addFilter('is_active', 1)
            ->create();

        $items = $this->postRepository->getList($searchCriteria)->getItems();
        $post = reset($items);

        if (!$post) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->forward('noroute');

            return $result;
        }

        $this->coreRegistry->register('current_post', $post);

        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set($post->getTitle());

        return $page;
    }
}
