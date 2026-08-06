<?php
declare(strict_types=1);

namespace Cartware\Faq\Api;

use Cartware\Faq\Api\Data\FaqInterface;
use Cartware\Faq\Api\Data\FaqSearchResultsInterface;

/**
 * FAQ REST API.
 */
interface FaqRestInterface
{
    /**
     * @param int $page
     * @param int $pageSize
     * @return FaqSearchResultsInterface
     */
    public function getFaqs(int $page = 1, int $pageSize = 20): FaqSearchResultsInterface;

    /**
     * @param int $faqId
     * @return FaqInterface
     */
    public function getFaq(int $faqId): FaqInterface;
}
