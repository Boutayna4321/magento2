<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Api\Data;

interface ReviewSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\ProductReviews\Api\Data\ReviewInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\ProductReviews\Api\Data\ReviewInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
