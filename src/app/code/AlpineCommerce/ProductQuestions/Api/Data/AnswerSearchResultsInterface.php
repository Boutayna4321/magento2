<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api\Data;

interface AnswerSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface[]
     */
    public function getItems();

    /**
     * @param \AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
