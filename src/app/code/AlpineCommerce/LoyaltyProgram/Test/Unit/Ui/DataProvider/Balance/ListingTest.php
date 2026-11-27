<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Ui\DataProvider\Balance;

use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance\GridCollection;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance\GridCollectionFactory;
use AlpineCommerce\LoyaltyProgram\Ui\DataProvider\Balance\Listing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListingTest extends TestCase
{
    private Listing $listing;
    private GridCollectionFactory&MockObject $collectionFactory;
    private GridCollection&MockObject $collection;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(GridCollectionFactory::class);
        $this->collection = $this->createMock(GridCollection::class);
        $this->collectionFactory->method('create')->willReturn($this->collection);

        // Signature: ($name, $primaryFieldName, $requestFieldName, $collectionFactory, array $meta, array $data)
        // The 5th and 6th arguments are arrays; passing a RequestInterface mock there is a type error.
        $this->listing = new Listing(
            'loyalty_balance_listing',
            'entity_id',
            'entity_id',
            $this->collectionFactory,
            [],
            []
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

    public function testGetPrimaryFieldNameIsTakenFromTheConstructor(): void
    {
        $this->assertSame('entity_id', $this->listing->getPrimaryFieldName());
    }

    public function testGetNameIsTakenFromTheConstructor(): void
    {
        $this->assertSame('loyalty_balance_listing', $this->listing->getName());
    }
}
