<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Model;

use Cartware\LegalPages\Api\Data\LegalPageSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class LegalPageSearchResults extends SearchResults implements LegalPageSearchResultsInterface
{
}
