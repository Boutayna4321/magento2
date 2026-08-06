<?php
declare(strict_types=1);

namespace Cartware\Faq\Model;

use Cartware\Faq\Api\Data\FaqSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class FaqSearchResults extends SearchResults implements FaqSearchResultsInterface
{
}
