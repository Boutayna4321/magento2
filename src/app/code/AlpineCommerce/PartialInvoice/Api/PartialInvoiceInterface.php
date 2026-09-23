<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Service contract for partial invoice processing.
 *
 * @api
 */
interface PartialInvoiceInterface
{
    /**
     * Process an order for partial invoicing (extracted from observer logic).
     *
     * @param int $orderId
     * @return \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface
     * @throws NoSuchEntityException If the order does not exist.
     */
    public function processOrder(int $orderId): \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface;

    /**
     * Get the partial invoice configuration for a given store.
     *
     * @param int $storeId
     * @return array{
     *     enabled: bool,
     *     payment_methods: array<string>,
     *     allow_backorders: bool,
     *     min_qty_to_invoice: float
     * }
     */
    public function getConfig(int $storeId): array;

    /**
     * Determine whether the order is eligible for partial invoicing.
     *
     * @param int $orderId
     * @return bool
     * @throws NoSuchEntityException If the order does not exist.
     */
    public function canInvoice(int $orderId): bool;
}
