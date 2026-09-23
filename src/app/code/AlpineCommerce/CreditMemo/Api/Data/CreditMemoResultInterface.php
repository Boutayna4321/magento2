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

    public function getOrderId(): ?int;
    public function setOrderId(int $orderId): self;
    public function getCreditmemoId(): ?int;
    public function setCreditmemoId(?int $creditmemoId): self;
    public function getCreditmemoIncrementId(): ?string;
    public function setCreditmemoIncrementId(?string $incrementId): self;
    public function getStatus(): ?string;
    public function setStatus(string $status): self;
    public function getRefundedAmount(): ?float;
    public function setRefundedAmount(float $amount): self;
    public function getMessage(): ?string;
    public function setMessage(?string $message): self;
}
