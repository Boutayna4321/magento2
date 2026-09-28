<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Api;

use AlpineCommerce\Faq\Api\Data\FaqInterface;
use AlpineCommerce\Faq\Api\Data\FaqSearchResultsInterface;

/**
 * FAQ REST API.
 */
interface FaqRestInterface
{
    /**
     * @param int $page
     * @param int $pageSize
     * @return \AlpineCommerce\Faq\Api\Data\FaqSearchResultsInterface
     */
    public function getFaqs(int $page = 1, int $pageSize = 20): FaqSearchResultsInterface;

    /**
     * @param int $faqId
     * @return \AlpineCommerce\Faq\Api\Data\FaqInterface
     */
    public function getFaq(int $faqId): FaqInterface;
}
