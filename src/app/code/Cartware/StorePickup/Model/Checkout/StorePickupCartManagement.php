<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Model\Checkout;

use Cartware\StorePickup\Api\StoreAvailabilityInterface;
use Cartware\StorePickup\Api\StoreInfoRepositoryInterface;
use Cartware\StorePickup\Api\StorePickupCartManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;

class StorePickupCartManagement implements StorePickupCartManagementInterface
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly StoreInfoRepositoryInterface $storeInfoRepository,
        private readonly StoreAvailabilityInterface $storeAvailability
    ) {
    }

    public function setPickupSourceCode(int $cartId, ?string $sourceCode): void
    {
        if ($sourceCode === null || $sourceCode === '') {
            $sourceCode = null;
        } else {
            try {
                $this->storeInfoRepository->getBySourceCode($sourceCode);
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(__('Unknown pickup store "%1".', $sourceCode));
            }
        }

        $quote = $this->cartRepository->get($cartId);
        $quote->setCartwarePickupSourceCode($sourceCode);
        $this->cartRepository->save($quote);
    }

    public function getAvailableStores(int $cartId): array
    {
        $quote = $this->cartRepository->get($cartId);
        $items = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $items[$item->getSku()] = ($items[$item->getSku()] ?? 0) + (float)$item->getQty();
        }
        return $this->storeAvailability->getAvailableStores($items);
    }
}
