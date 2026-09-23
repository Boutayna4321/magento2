<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Service contract for automatic credit memo processing.
 *
 * @api
 */
interface CreditMemoAutomationInterface
{
    /**
     * Process a credit memo for an order upon cancellation.
     *
     * @param int $orderId
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoResultInterface
     * @throws NoSuchEntityException If the order does not exist.
     */
    public function processCancellation(int $orderId): \AlpineCommerce\CreditMemo\Api\Data\CreditMemoResultInterface;

    /**
     * Get the credit memo configuration for a given store.
     *
     * @param int $storeId
     * @return array{
     *     enabled: bool,
     *     payment_methods: array<string>,
     *     auto_refund: bool
     * }
     */
    public function getConfig(int $storeId): array;
}
