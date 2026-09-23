<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Service contract for automatic invoice processing.
 *
 * @api
 */
interface InvoiceAutomationInterface
{
    /**
     * Process an order for auto-invoicing (extracted from observer logic).
     *
     * @param int $orderId
     * @return \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface
     * @throws NoSuchEntityException If the order does not exist.
     */
    public function processOrder(int $orderId): \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface;

    /**
     * Get the auto-invoice configuration for a given store.
     *
     * @param int $storeId
     * @return array{
     *     enabled: bool,
     *     payment_methods: array<string>,
     *     capture_mode: string
     * }
     */
    public function getConfig(int $storeId): array;

    /**
     * Determine whether the order is eligible for auto-invoicing.
     *
     * @param int $orderId
     * @return bool
     * @throws NoSuchEntityException If the order does not exist.
     */
    public function canInvoice(int $orderId): bool;
}
