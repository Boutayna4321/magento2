<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface BlogCategorySearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
