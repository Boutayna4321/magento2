<?php
declare(strict_types=1);

namespace Cartware\Blog\Model;

use Cartware\Blog\Api\Data\BlogPostSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class BlogPostSearchResults extends SearchResults implements BlogPostSearchResultsInterface
{
}
