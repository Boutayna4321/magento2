<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api\Data;

interface QuestionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
