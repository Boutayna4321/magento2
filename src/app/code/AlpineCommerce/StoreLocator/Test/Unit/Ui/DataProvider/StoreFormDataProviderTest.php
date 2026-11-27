<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Test\Unit\Ui\DataProvider;

use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\Collection;
use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\CollectionFactory;
use AlpineCommerce\StoreLocator\Model\Store;
use AlpineCommerce\StoreLocator\Ui\DataProvider\StoreFormDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The form data provider must hold a collection: Magento's form component calls
 * addFilter() on it, which forwards to addFieldToFilter(). Holding a single model
 * made the "Add New Store" and "Edit" pages fail with "Invalid method addFieldToFilter".
 */
class StoreFormDataProviderTest extends TestCase
{
    private Collection&MockObject $collection;
    private RequestInterface&MockObject $request;

    protected function setUp(): void
    {
        $this->collection = $this->createMock(Collection::class);
        $this->request = $this->createMock(RequestInterface::class);
    }

    private function provider(): StoreFormDataProvider
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($this->collection);

        return new StoreFormDataProvider(
            'alphacommerce_store_locator_store_form_data_source',
            'entity_id',
            'entity_id',
            $factory,
            $this->request,
            [],
            [],
            $this->createMock(PoolInterface::class)
        );
    }

    public function testFrameworkFilterIsForwardedToTheCollection(): void
    {
        $this->collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', ['eq' => 7]);

        $filter = new Filter(['field' => 'entity_id', 'value' => 7, 'condition_type' => 'eq']);
        $this->provider()->addFilter($filter);
    }

    public function testGetDataIsKeyedByEntityId(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(3);
        $store->method('getData')->willReturn(['entity_id' => 3, 'name' => 'Casablanca Main']);
        $this->collection->method('getItems')->willReturn([$store]);

        $this->assertSame(
            [3 => ['entity_id' => 3, 'name' => 'Casablanca Main']],
            $this->provider()->getData()
        );
    }

    public function testNewStoreHasNoData(): void
    {
        $this->collection->method('getItems')->willReturn([]);

        $this->assertSame([], $this->provider()->getData());
    }
}
