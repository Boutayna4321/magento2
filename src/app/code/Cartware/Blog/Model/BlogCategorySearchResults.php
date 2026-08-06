<?php
declare(strict_types=1);

namespace Cartware\Blog\Model;

use Cartware\Blog\Api\Data\BlogCategorySearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class BlogCategorySearchResults extends SearchResults implements BlogCategorySearchResultsInterface
{
}
