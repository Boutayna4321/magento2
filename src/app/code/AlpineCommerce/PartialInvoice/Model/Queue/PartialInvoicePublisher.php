<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Model\Queue;

use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface;
use AlpineCommerce\PartialInvoice\Model\Data\PartialInvoiceMessageFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publishes partial invoice processing messages to the async queue.
 */
class PartialInvoicePublisher
{
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly PartialInvoiceMessageFactory $messageFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function publish(int $orderId, int $storeId, string $correlationId): void
    {
        /** @var PartialInvoiceMessageInterface $message */
        $message = $this->messageFactory->create();
        $message->setOrderId($orderId);
        $message->setStoreId($storeId);
        $message->setCreatedAt(date('Y-m-d H:i:s'));
        $message->setCorrelationId($correlationId);

        $this->logger->info('Publishing partial invoice process message', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'store_id' => $storeId,
        ]);

        $this->publisher->publish('alpincommerce.partialinvoice.process', $message);
    }
}
