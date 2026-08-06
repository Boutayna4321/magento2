<?php
declare(strict_types=1);

namespace Cartware\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface BlogPostSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Cartware\Blog\Api\Data\BlogPostInterface[]
     */
    public function getItems();

    /**
     * @param \Cartware\Blog\Api\Data\BlogPostInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
