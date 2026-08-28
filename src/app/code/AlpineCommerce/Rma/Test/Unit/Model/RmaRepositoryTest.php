<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\Rma\Test\Unit\Model;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use AlpineCommerce\Rma\Model\Rma;
use AlpineCommerce\Rma\Model\ResourceModel\Rma as RmaResource;
use AlpineCommerce\Rma\Model\RmaFactory;
use AlpineCommerce\Rma\Model\RmaRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RmaRepositoryTest extends TestCase
{
    public function testGetByIdReturnsRma(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $rma->method('getId')->willReturn(1);

        $resource = $this->createMock(RmaResource::class);
        $resource->expects(self::once())->method('load')->with($rma, 1);

        $repo = $this->createRepository($resource, rma: $rma);

        self::assertSame($rma, $repo->getById(1));
    }

    public function testGetByIdThrowsNoSuchEntityWhenMissing(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $rma->method('getId')->willReturn(null);

        $resource = $this->createMock(RmaResource::class);
        $resource->method('load');

        $repo = $this->createRepository($resource, rma: $rma);

        $this->expectException(NoSuchEntityException::class);
        $repo->getById(99);
    }

    public function testSavePersistsViaResource(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $resource = $this->createMock(RmaResource::class);
        $resource->expects(self::once())->method('save')->with($rma);

        $repo = $this->createRepository($resource);
        self::assertSame($rma, $repo->save($rma));
    }

    public function testSaveThrowsCouldNotSaveOnResourceFailure(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $resource = $this->createMock(RmaResource::class);
        $resource->method('save')->willThrowException(new \Exception('db down'));

        $repo = $this->createRepository($resource);

        $this->expectException(CouldNotSaveException::class);
        $repo->save($rma);
    }

    public function testGetListProcessesSearchCriteria(): void
    {
        $criteria = $this->createMock(SearchCriteriaInterface::class);
        $collection = $this->createMock(\Magento\Framework\Data\Collection::class);
        $collection->method('getItems')->willReturn([$this->createMock(RmaInterface::class)]);
        $collection->method('getSize')->willReturn(1);

        $rma = $this->createMock(Rma::class);
        $rma->method('getCollection')->willReturn($collection);

        $rmaFactory = $this->createMock(RmaFactory::class);
        $rmaFactory->method('create')->willReturn($rma);

        $processor = $this->createMock(CollectionProcessorInterface::class);
        $processor->expects(self::once())->method('process')->with($criteria, $collection);

        $searchResults = $this->createMock(SearchResultsInterface::class);
        $searchResults->expects(self::once())->method('setSearchCriteria')->with($criteria);
        $searchResults->expects(self::once())->method('setItems')->with([$this->createMock(RmaInterface::class)]);
        $searchResults->expects(self::once())->method('setTotalCount')->with(1);

        $factory = $this->createMock(SearchResultsInterfaceFactory::class);
        $factory->method('create')->willReturn($searchResults);

        $repo = $this->createRepository($this->createMock(RmaResource::class), $rmaFactory, $processor, $factory);
        $result = $repo->getList($criteria);

        self::assertSame($searchResults, $result);
    }

    public function testDeleteDelegatesToResource(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $resource = $this->createMock(RmaResource::class);
        $resource->expects(self::once())->method('delete')->with($rma);

        $repo = $this->createRepository($resource);
        self::assertTrue($repo->delete($rma));
    }

    public function testDeleteByIdLoadsThenDeletes(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $rma->method('getId')->willReturn(5);

        $resource = $this->createMock(RmaResource::class);
        $resource->method('load')->with($rma, 5);
        $resource->expects(self::once())->method('delete')->with($rma);

        $repo = $this->createRepository($resource, rma: $rma);
        self::assertTrue($repo->deleteById(5));
    }

    private function createRepository(
        ?RmaResource $resource = null,
        ?Rma $rma = null,
        ?CollectionProcessorInterface $processor = null,
        ?SearchResultsInterfaceFactory $searchResultsFactory = null
    ): RmaRepository {
        return new RmaRepository(
            $this->createConfiguredMock(RmaFactory::class, ['create' => $rma ?? $this->createMock(Rma::class)]),
            $resource ?? $this->createMock(RmaResource::class),
            $processor ?? $this->createMock(CollectionProcessorInterface::class),
            $searchResultsFactory ?? $this->createMock(SearchResultsInterfaceFactory::class)
        );
    }
}
