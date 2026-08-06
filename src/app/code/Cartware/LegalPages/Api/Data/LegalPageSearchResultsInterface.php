<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface LegalPageSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Cartware\LegalPages\Api\Data\LegalPageInterface[]
     */
    public function getItems();

    /**
     * @param \Cartware\LegalPages\Api\Data\LegalPageInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
