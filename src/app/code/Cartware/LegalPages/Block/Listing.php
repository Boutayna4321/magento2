<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Block;

use Cartware\LegalPages\Api\Data\LegalPageInterface;
use Cartware\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;

class Listing extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly LegalPageRepositoryInterface $pageRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return LegalPageInterface[]
     */
    public function getPages(): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setDirection(SortOrder::SORT_ASC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('is_active', 1)
            ->setSortOrders([$sortOrder])
            ->create();

        return $this->pageRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param LegalPageInterface $page
     * @return string
     */
    public function getPageUrl(LegalPageInterface $page): string
    {
        return $this->getUrl('legal/index/view', ['url_key' => $page->getUrlKey()]);
    }
}
