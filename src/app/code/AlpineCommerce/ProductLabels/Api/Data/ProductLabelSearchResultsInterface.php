<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ProductLabelSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
