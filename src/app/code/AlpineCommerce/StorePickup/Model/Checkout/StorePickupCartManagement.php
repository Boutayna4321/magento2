<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Model\Checkout;

use AlpineCommerce\StorePickup\Api\StoreAvailabilityInterface;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use AlpineCommerce\StorePickup\Api\StorePickupCartManagementInterface;
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
        $quote->setAlpinecommercePickupSourceCode($sourceCode);
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
