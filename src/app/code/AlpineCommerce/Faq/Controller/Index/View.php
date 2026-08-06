<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Controller\Index;

use AlpineCommerce\Faq\Api\FaqRepositoryInterface;
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
        private readonly FaqRepositoryInterface $faqRepository,
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

        $items = $this->faqRepository->getList($searchCriteria)->getItems();
        $faq = reset($items);

        if (!$faq) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->forward('noroute');

            return $result;
        }

        $this->coreRegistry->register('current_faq', $faq);

        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set($faq->getTitle());

        return $page;
    }
}
