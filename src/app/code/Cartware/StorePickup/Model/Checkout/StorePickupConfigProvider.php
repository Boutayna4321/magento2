<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Model\Checkout;

use Cartware\StorePickup\Api\StoreAvailabilityInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class StorePickupConfigProvider implements ConfigProviderInterface
{
    public const CARRIER_CODE = 'storepickup';

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly StoreAvailabilityInterface $storeAvailability
    ) {
    }

    public function getConfig(): array
    {
        $items = [];
        $quote = $this->checkoutSession->getQuote();
        if ($quote && $quote->getId()) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $items[$item->getSku()] = ($items[$item->getSku()] ?? 0) + (float)$item->getQty();
            }
        }

        return [
            'storePickup' => [
                'carrierCode' => self::CARRIER_CODE,
                'availableStores' => $this->storeAvailability->getAvailableStores($items),
            ],
        ];
    }
}
