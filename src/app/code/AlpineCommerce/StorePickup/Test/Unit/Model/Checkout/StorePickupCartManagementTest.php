<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Test\Unit\Model\Checkout;

use AlpineCommerce\StorePickup\Api\Data\PickupStoreInterface;
use AlpineCommerce\StorePickup\Api\Data\PickupStoreInterfaceFactory;
use AlpineCommerce\StorePickup\Api\StoreAvailabilityInterface;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use AlpineCommerce\StorePickup\Model\Checkout\StorePickupCartManagement;
use AlpineCommerce\StorePickup\Model\Data\PickupStore;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

/**
 * getAvailableStores() returns web API data objects instead of array[]: Magento's web API cannot describe
 * array[], and one undescribable return type breaks SOAP for every service.
 */
class StorePickupCartManagementTest extends TestCase
{
    public function testAvailableStoresAreDataObjectsWithTheSameValues(): void
    {
        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(2);
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item, $item]);
        $cartRepository = $this->createMock(CartRepositoryInterface::class);
        $cartRepository->method('get')->with(7)->willReturn($quote);

        $store = ['source_code' => 'paris', 'name' => 'Paris', 'latitude' => 48.85, 'longitude' => 2.35];
        $availability = $this->createMock(StoreAvailabilityInterface::class);
        $availability->expects($this->once())->method('getAvailableStores')
            ->with(['SKU-1' => 4.0])
            ->willReturn([$store]);

        $factory = $this->getMockBuilder(PickupStoreInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->method('create')->willReturnCallback(static fn (array $args): PickupStore => new PickupStore($args['data']));

        $stores = (new StorePickupCartManagement(
            $cartRepository,
            $this->createMock(StoreInfoRepositoryInterface::class),
            $availability,
            $factory
        ))->getAvailableStores(7);

        $this->assertCount(1, $stores);
        $this->assertInstanceOf(PickupStoreInterface::class, $stores[0]);
        $this->assertSame('paris', $stores[0]->getSourceCode());
        $this->assertSame('Paris', $stores[0]->getName());
        $this->assertSame(48.85, $stores[0]->getLatitude());
        $this->assertNull($stores[0]->getPhone());
    }
}
