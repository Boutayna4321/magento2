<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Api;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterface;

interface StoreInfoRepositoryInterface
{
    public function save(StoreInfoInterface $storeInfo): StoreInfoInterface;

    public function getById(int $entityId): StoreInfoInterface;

    public function getBySourceCode(string $sourceCode): StoreInfoInterface;

    /**
     * @return \AlpineCommerce\StorePickup\Api\Data\StoreInfoInterface[]
     */
    public function getActiveStores(): array;

    public function delete(StoreInfoInterface $storeInfo): bool;
}
