<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model;

use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class ProductLabelSearchResults extends SearchResults implements ProductLabelSearchResultsInterface
{
}
