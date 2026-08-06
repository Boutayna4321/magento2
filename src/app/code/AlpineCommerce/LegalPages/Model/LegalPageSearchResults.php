<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Model;

use AlpineCommerce\LegalPages\Api\Data\LegalPageSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class LegalPageSearchResults extends SearchResults implements LegalPageSearchResultsInterface
{
}
