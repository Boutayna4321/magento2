<?php
declare(strict_types=1);

namespace Cartware\Faq\Block;

use Cartware\Faq\Api\Data\FaqInterface;
use Cartware\Faq\Api\FaqRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;

class Listing extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return FaqInterface[]
     */
    public function getFaqs(): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setDirection(SortOrder::SORT_ASC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('is_active', 1)
            ->setSortOrders([$sortOrder])
            ->create();

        return $this->faqRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param FaqInterface $faq
     * @return string
     */
    public function getFaqUrl(FaqInterface $faq): string
    {
        return $this->getUrl('faq/index/view', ['url_key' => $faq->getUrlKey()]);
    }
}
