<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model;

use AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class BlogCategorySearchResults extends SearchResults implements BlogCategorySearchResultsInterface
{
}
