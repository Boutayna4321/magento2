<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Api;

use AlpineCommerce\ProductReviews\Api\Data\ReviewInterface;
use AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterface;

interface ReviewRestInterface
{
    /**
     * @param int $productId
     * @param int $page
     * @param int $pageSize
     * @return ReviewSearchResultsInterface
     */
    public function getReviews(int $productId, int $page = 1, int $pageSize = 20): ReviewSearchResultsInterface;

    /**
     * @param int $reviewId
     * @return ReviewInterface
     */
    public function getReview(int $reviewId): ReviewInterface;

    /**
     * @param int $productId
     * @param string $title
     * @param string $detail
     * @param int $rating
     * @return ReviewInterface
     */
    public function addReview(int $productId, string $title, string $detail, int $rating): ReviewInterface;

    /**
     * @param int $reviewId
     * @param int $helpful
     * @return bool
     */
    public function voteHelpful(int $reviewId, int $helpful): bool;
}
