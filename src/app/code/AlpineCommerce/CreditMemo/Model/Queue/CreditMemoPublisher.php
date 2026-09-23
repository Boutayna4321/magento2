<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Model\Queue;

use AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface;
use AlpineCommerce\CreditMemo\Model\Data\CreditMemoMessageFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publishes credit memo processing messages to the async queue.
 */
class CreditMemoPublisher
{
    public const TOPIC_NAME = 'alpincommerce.creditmemo.process';

    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly CreditMemoMessageFactory $messageFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function publish(int $orderId, int $storeId, string $correlationId): void
    {
        /** @var CreditMemoMessageInterface $message */
        $message = $this->messageFactory->create();
        $message->setOrderId($orderId);
        $message->setStoreId($storeId);
        $message->setCreatedAt(date('Y-m-d H:i:s'));
        $message->setCorrelationId($correlationId);
        $message->setNotifyCustomer(false);
        $message->setRefundShipping(false);

        $this->logger->info('Publishing credit memo process message', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'store_id' => $storeId,
        ]);

        $this->publisher->publish(self::TOPIC_NAME, $message);
    }
}
