<?php
declare(strict_types=1);

namespace Cartware\Faq\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface FaqSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Cartware\Faq\Api\Data\FaqInterface[]
     */
    public function getItems();

    /**
     * @param \Cartware\Faq\Api\Data\FaqInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
