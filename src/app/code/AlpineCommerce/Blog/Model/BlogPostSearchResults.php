<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model;

use AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class BlogPostSearchResults extends SearchResults implements BlogPostSearchResultsInterface
{
}
