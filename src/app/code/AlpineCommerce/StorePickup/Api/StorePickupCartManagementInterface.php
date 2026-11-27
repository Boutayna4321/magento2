<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Api;

interface StorePickupCartManagementInterface
{
    /**
     * @param int $cartId
     * @param string|null $sourceCode
     * @return void
     */
    public function setPickupSourceCode(int $cartId, ?string $sourceCode): void;

    /**
     * @param int $cartId
     * @return \AlpineCommerce\StorePickup\Api\Data\PickupStoreInterface[]
     */
    public function getAvailableStores(int $cartId): array;
}
