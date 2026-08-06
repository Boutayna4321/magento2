<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Model\Rest;

use AlpineCommerce\Faq\Api\Data\FaqInterface;
use AlpineCommerce\Faq\Api\Data\FaqSearchResultsInterface;
use AlpineCommerce\Faq\Api\FaqRepositoryInterface;
use AlpineCommerce\Faq\Api\FaqRestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class FaqRestService implements FaqRestInterface
{
    public function __construct(
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function getFaqs(int $page = 1, int $pageSize = 20): FaqSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(FaqInterface::IS_ACTIVE, 1, 'eq')
            ->setPageSize($pageSize)
            ->setCurrentPage(max(1, $page))
            ->create();

        return $this->faqRepository->getList($searchCriteria);
    }

    public function getFaq(int $faqId): FaqInterface
    {
        $faq = $this->faqRepository->getById($faqId);
        if (!$faq->isActive()) {
            throw new NoSuchEntityException(
                __('The FAQ with ID "%1" does not exist.', $faqId)
            );
        }

        return $faq;
    }
}
