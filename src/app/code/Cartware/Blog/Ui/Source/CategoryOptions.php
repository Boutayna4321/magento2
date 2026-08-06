<?php
declare(strict_types=1);

namespace Cartware\Blog\Ui\Source;

use Cartware\Blog\Api\BlogCategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class CategoryOptions implements OptionSourceInterface
{
    public function __construct(
        private readonly BlogCategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function toOptionArray(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('is_active', 1)
            ->create();

        $options = [['value' => '', 'label' => __('-- None --')]];

        foreach ($this->categoryRepository->getList($searchCriteria)->getItems() as $category) {
            $options[] = [
                'value' => $category->getId(),
                'label' => $category->getName(),
            ];
        }

        return $options;
    }
}
