<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class OrderPlaceAfter implements ObserverInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TimezoneInterface $timezone,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $storeId = (int) $order->getStoreId();

        if (!$this->isModuleEnabled($storeId)) {
            return;
        }

        $returnAllowedUntil = $this->timezone->date()
            ->add(new \DateInterval('P' . (int) $this->getAllowReturnDays($storeId) . 'D'))
            ->format('Y-m-d H:i:s');

        $order->setData('rma_allowed_until', $returnAllowedUntil);
        $order->setData('rma_enabled', 1);

        $order->addStatusHistoryComment(
            __('RMA return allowed until %1.', $returnAllowedUntil)
        )->setIsCustomerNotified(false);

        $order->save();
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'rma/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getAllowReturnDays(int $storeId): int
    {
        $value = $this->scopeConfig->getValue(
            'rma/general/allow_return_days',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value !== null ? (int) $value : 30;
    }
}
