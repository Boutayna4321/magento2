<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Model;

use AlpineCommerce\Faq\Api\Data\FaqSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class FaqSearchResults extends SearchResults implements FaqSearchResultsInterface
{
}
