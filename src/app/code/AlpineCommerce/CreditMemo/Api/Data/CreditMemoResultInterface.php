<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface CreditMemoResultInterface extends ExtensibleDataInterface
{
    public const KEY_ORDER_ID = 'order_id';
    public const KEY_CREDITMEMO_ID = 'creditmemo_id';
    public const KEY_CREDITMEMO_INCREMENT_ID = 'creditmemo_increment_id';
    public const KEY_STATUS = 'status';
    public const KEY_REFUNDED_AMOUNT = 'refunded_amount';
    public const KEY_MESSAGE = 'message';

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * @return int|null
     */
    public function getCreditmemoId(): ?int;

    /**
     * @param int|null $creditmemoId
     * @return $this
     */
    public function setCreditmemoId(?int $creditmemoId): self;

    /**
     * @return string|null
     */
    public function getCreditmemoIncrementId(): ?string;

    /**
     * @param string|null $incrementId
     * @return $this
     */
    public function setCreditmemoIncrementId(?string $incrementId): self;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return float|null
     */
    public function getRefundedAmount(): ?float;

    /**
     * @param float $amount
     * @return $this
     */
    public function setRefundedAmount(float $amount): self;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self;
}
