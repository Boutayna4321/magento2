<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface RmaCreationDataInterface extends ExtensibleDataInterface
{
    public const KEY_ORDER_ID = 'order_id';
    public const KEY_ITEMS = 'items';
    public const KEY_REASON = 'reason';
    public const KEY_CUSTOMER_NOTES = 'customer_notes';

    public function getOrderId(): ?int;
    public function setOrderId(int $orderId);

    public function getItems(): array;
    public function setItems(array $items);

    public function getReason(): ?string;
    public function setReason(string $reason);

    public function getCustomerNotes(): ?string;
    public function setCustomerNotes(string $notes);
}
