<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Ui\DataProvider\Balance;

use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance\GridCollection;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance\GridCollectionFactory;
use AlpineCommerce\LoyaltyProgram\Ui\DataProvider\Balance\Listing;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListingTest extends TestCase
{
    private Listing $listing;
    private GridCollectionFactory&MockObject $collectionFactory;
    private RequestInterface&MockObject $request;
    private GridCollection&MockObject $collection;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(GridCollectionFactory::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->collection = $this->createMock(GridCollection::class);
        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->listing = new Listing(
            'loyalty_balance_listing',
            'entity_id',
            'id',
            $this->collectionFactory,
            $this->request
        );
    }

    public function testGetDataReturnsCollectionData(): void
    {
        $this->collection->method('toArray')->willReturn([
            'totalRecords' => 2,
            'items' => [
                ['entity_id' => 1, 'points' => 100],
                ['entity_id' => 2, 'points' => 200],
            ],
        ]);

        $data = $this->listing->getData();

        $this->assertSame(2, $data['totalRecords']);
        $this->assertCount(2, $data['items']);
    }
}
