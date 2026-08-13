<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Plugin\Order;

use AlpineCommerce\CustomerCare\Api\CustomerCareInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class AfterPlace
{
    public function __construct(
        private readonly CustomerCareInterface $customerCare,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterPlace(Order $subject, OrderInterface $result): OrderInterface
    {
        if (!$result || !$result->getCustomerId()) {
            return $result;
        }

        try {
            $this->customerCare->recalculateVipStatus((int) $result->getCustomerId());
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('CustomerCare: failed to update VIP for customer %s: %s', $result->getCustomerId(), $e->getMessage())
            );
        }

        return $result;
    }
}
