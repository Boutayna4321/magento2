<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface BlogPostSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\Blog\Api\Data\BlogPostInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\Blog\Api\Data\BlogPostInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
