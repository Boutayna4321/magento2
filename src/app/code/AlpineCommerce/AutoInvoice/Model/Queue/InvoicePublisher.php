<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Model\Queue;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceMessageInterface;
use AlpineCommerce\AutoInvoice\Model\Data\InvoiceMessageFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publishes invoice processing messages to the async queue.
 *
 * Observer calls this instead of executing invoice logic directly.
 */
class InvoicePublisher
{
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly InvoiceMessageFactory $messageFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function publish(int $orderId, int $storeId, string $correlationId): void
    {
        /** @var InvoiceMessageInterface $message */
        $message = $this->messageFactory->create();
        $message->setOrderId($orderId);
        $message->setStoreId($storeId);
        $message->setCreatedAt(date('Y-m-d H:i:s'));
        $message->setCorrelationId($correlationId);

        $this->logger->info('Publishing invoice process message', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'store_id' => $storeId,
        ]);

        $this->publisher->publish('alpinecommerce.autoinvoice.process', $message);
    }
}
