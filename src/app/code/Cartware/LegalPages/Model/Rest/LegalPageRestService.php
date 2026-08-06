<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Model\Rest;

use Cartware\LegalPages\Api\Data\LegalPageInterface;
use Cartware\LegalPages\Api\Data\LegalPageSearchResultsInterface;
use Cartware\LegalPages\Api\LegalPageRepositoryInterface;
use Cartware\LegalPages\Api\LegalPageRestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class LegalPageRestService implements LegalPageRestInterface
{
    public function __construct(
        private readonly LegalPageRepositoryInterface $pageRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function getPages(int $page = 1, int $pageSize = 20): LegalPageSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(LegalPageInterface::IS_ACTIVE, 1, 'eq')
            ->setPageSize($pageSize)
            ->setCurrentPage(max(1, $page))
            ->create();

        return $this->pageRepository->getList($searchCriteria);
    }

    public function getPageByType(string $type): LegalPageInterface
    {
        $page = $this->pageRepository->getByType($type);
        if (!$page || !$page->isActive()) {
            throw new NoSuchEntityException(
                __('The legal page of type "%1" does not exist.', $type)
            );
        }

        return $page;
    }
}
