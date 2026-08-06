<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface LegalPageSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
