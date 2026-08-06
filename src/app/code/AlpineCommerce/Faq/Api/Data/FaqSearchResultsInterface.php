<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface FaqSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\Faq\Api\Data\FaqInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\Faq\Api\Data\FaqInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
