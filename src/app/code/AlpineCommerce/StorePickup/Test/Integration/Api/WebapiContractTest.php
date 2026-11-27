<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Test\Integration\Api;

use AlpineCommerce\StorePickup\Api\Data\PickupStoreInterface;
use AlpineCommerce\StorePickup\Api\StorePickupCartManagementInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Webapi\Model\ServiceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * The StorePickup web API contract can be described by Magento: before, getAvailableStores() documented
 * array[] and the SOAP metadata of every service failed.
 *
 * @magentoAppArea webapi_soap
 * @magentoAppIsolation enabled
 */
class WebapiContractTest extends TestCase
{
    public function testStorePickupServiceExposesGetAvailableStores(): void
    {
        $serviceMetadata = Bootstrap::getObjectManager()->get(ServiceMetadata::class);
        $name = $serviceMetadata->getServiceName(StorePickupCartManagementInterface::class, 'V1');

        $this->assertArrayHasKey('getAvailableStores', $serviceMetadata->getServiceMetadata($name)['methods']);
    }

    /**
     * REST clients keep the same keys as before (null values are left out, as for every Magento data object).
     */
    public function testWebapiOutputKeepsTheSameKeys(): void
    {
        $store = [
            'source_code' => 'paris',
            'name' => 'Paris',
            'street' => '1 rue de Rivoli',
            'city' => 'Paris',
            'region' => 'Île-de-France',
            'postcode' => '75001',
            'country_id' => 'FR',
            'phone' => '+33 1 00 00 00 00',
            'latitude' => 48.85,
            'longitude' => 2.35,
            'opening_hours' => 'Mo-Sa 10:00-19:00',
        ];
        $objectManager = Bootstrap::getObjectManager();
        $pickupStore = $objectManager->create(PickupStoreInterface::class, ['data' => $store]);

        $output = $objectManager->get(DataObjectProcessor::class)
            ->buildOutputDataArray($pickupStore, PickupStoreInterface::class);

        $this->assertEquals($store, $output);
    }
}
