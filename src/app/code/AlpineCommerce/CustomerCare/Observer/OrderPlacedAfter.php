<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Observer;

use AlpineCommerce\CustomerCare\Api\CustomerCareInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Recomputes VIP status when a guest-less order is placed by a customer.
 */
class OrderPlacedAfter implements ObserverInterface
{
    public function __construct(
        private readonly CustomerCareInterface $customerCare,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getCustomerId()) {
            return;
        }

        try {
            $this->customerCare->recalculateVipStatus((int) $order->getCustomerId());
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('CustomerCare: failed to update VIP for customer %s: %s', $order->getCustomerId(), $e->getMessage())
            );
        }
    }
}
