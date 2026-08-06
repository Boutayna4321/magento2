<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Api;

use Cartware\StorePickup\Api\Data\StoreInfoInterface;

interface StoreInfoRepositoryInterface
{
    public function save(StoreInfoInterface $storeInfo): StoreInfoInterface;

    public function getById(int $entityId): StoreInfoInterface;

    public function getBySourceCode(string $sourceCode): StoreInfoInterface;

    /**
     * @return \Cartware\StorePickup\Api\Data\StoreInfoInterface[]
     */
    public function getActiveStores(): array;

    public function delete(StoreInfoInterface $storeInfo): bool;
}
