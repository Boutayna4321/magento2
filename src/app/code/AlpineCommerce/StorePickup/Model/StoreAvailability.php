<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Model;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterface;
use AlpineCommerce\StorePickup\Api\StoreAvailabilityInterface;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;

class StoreAvailability implements StoreAvailabilityInterface
{
    public function __construct(
        private readonly StoreInfoRepositoryInterface $storeInfoRepository,
        private readonly GetSourceItemsBySkuInterface $getSourceItemsBySku
    ) {
    }

    public function getAvailableStores(array $items): array
    {
        $items = array_filter($items, fn($qty) => (float)$qty > 0);
        if (!$items) {
            return [];
        }

        $sourceItemsBySku = [];
        foreach (array_keys($items) as $sku) {
            $sourceItemsBySku[$sku] = [];
            foreach ($this->getSourceItemsBySku->execute((string)$sku) as $item) {
                $sourceItemsBySku[$sku][$item->getSourceCode()] = $item;
            }
        }

        $result = [];
        foreach ($this->storeInfoRepository->getActiveStores() as $store) {
            $sourceCode = $store->getSourceCode();
            if ($sourceCode === null) {
                continue;
            }
            $available = true;
            foreach ($items as $sku => $qty) {
                $sourceItem = $sourceItemsBySku[$sku][$sourceCode] ?? null;
                if (!$sourceItem
                    || !$sourceItem->getStatus()
                    || $sourceItem->getQuantity() < (float)$qty
                ) {
                    $available = false;
                    break;
                }
            }
            if ($available) {
                $result[] = $this->toArray($store);
            }
        }

        return $result;
    }

    private function toArray(StoreInfoInterface $store): array
    {
        return [
            'source_code' => $store->getSourceCode(),
            'name' => $store->getName(),
            'street' => $store->getStreet(),
            'city' => $store->getCity(),
            'region' => $store->getRegion(),
            'postcode' => $store->getPostcode(),
            'country_id' => $store->getCountryId(),
            'phone' => $store->getPhone(),
            'latitude' => $store->getLatitude(),
            'longitude' => $store->getLongitude(),
            'opening_hours' => $store->getOpeningHours(),
        ];
    }
}
