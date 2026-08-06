<?php
declare(strict_types=1);

namespace Cartware\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface BlogCategorySearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Cartware\Blog\Api\Data\BlogCategoryInterface[]
     */
    public function getItems();

    /**
     * @param \Cartware\Blog\Api\Data\BlogCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
