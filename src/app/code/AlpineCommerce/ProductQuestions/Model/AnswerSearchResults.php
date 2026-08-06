<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class AnswerSearchResults extends SearchResults implements AnswerSearchResultsInterface
{
}
