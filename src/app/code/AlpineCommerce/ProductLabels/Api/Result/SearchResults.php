<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Api\Result;

use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class ProductLabelSearchResults extends SearchResults implements ProductLabelSearchResultsInterface
{
    protected array $items = [];

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }
}
