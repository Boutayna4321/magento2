<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Api;

use Cartware\LegalPages\Api\Data\LegalPageInterface;
use Cartware\LegalPages\Api\Data\LegalPageSearchResultsInterface;

/**
 * Legal pages REST API.
 */
interface LegalPageRestInterface
{
    /**
     * @param int $page
     * @param int $pageSize
     * @return LegalPageSearchResultsInterface
     */
    public function getPages(int $page = 1, int $pageSize = 20): LegalPageSearchResultsInterface;

    /**
     * @param string $type
     * @return LegalPageInterface
     */
    public function getPageByType(string $type): LegalPageInterface;
}
