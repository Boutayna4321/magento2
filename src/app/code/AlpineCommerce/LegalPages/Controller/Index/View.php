<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Controller\Index;

use AlpineCommerce\LegalPages\Api\LegalPageRepositoryInterface;
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
        private readonly LegalPageRepositoryInterface $pageRepository,
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

        $items = $this->pageRepository->getList($searchCriteria)->getItems();
        $page = reset($items);

        if (!$page) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->forward('noroute');

            return $result;
        }

        $this->coreRegistry->register('current_legal_page', $page);

        $pageResult = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $pageResult->getConfig()->getTitle()->set($page->getTitle());

        return $pageResult;
    }
}
