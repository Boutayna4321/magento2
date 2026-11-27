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

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId);

    /**
     * @return float[] Requested quantities keyed by order item id
     */
    public function getItems(): array;

    /**
     * @param float[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * @param string $reason
     * @return $this
     */
    public function setReason(string $reason);

    /**
     * @return string|null
     */
    public function getCustomerNotes(): ?string;

    /**
     * @param string $notes
     * @return $this
     */
    public function setCustomerNotes(string $notes);
}
