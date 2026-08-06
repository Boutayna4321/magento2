<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model;

use AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class ReviewSearchResults extends SearchResults implements ReviewSearchResultsInterface
{
}
