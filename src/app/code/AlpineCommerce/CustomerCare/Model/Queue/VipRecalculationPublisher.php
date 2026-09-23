<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model\Queue;

use AlpineCommerce\CustomerCare\Api\CustomerCareInterface;
use AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface;
use AlpineCommerce\CustomerCare\Model\Data\VipRecalculationMessageFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publishes VIP recalculation messages to the async queue.
 *
 * Supports single customer, batch (array of IDs), and all-customers scopes.
 */
class VipRecalculationPublisher
{
    public const TOPIC_NAME = 'alpincommerce.customercare.vip.recalculate';

    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly VipRecalculationMessageFactory $messageFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function publishForCustomer(int $customerId, int $storeId, string $correlationId): void
    {
        /** @var VipRecalculationMessageInterface $message */
        $message = $this->messageFactory->create();
        $message->setCustomerId($customerId);
        $message->setScope(VipRecalculationMessageInterface::SCOPE_CUSTOMER);
        $message->setStoreId($storeId);
        $message->setCorrelationId($correlationId);
        $message->setCreatedAt(date('Y-m-d H:i:s'));

        $this->logger->info('Publishing VIP recalculation for customer', [
            'correlation_id' => $correlationId,
            'customer_id' => $customerId,
            'scope' => 'customer',
        ]);

        $this->publisher->publish(self::TOPIC_NAME, $message);
    }
}
