<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Test\Unit\Service;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use AlpineCommerce\Rma\Api\RmaRepositoryInterface;
use AlpineCommerce\Rma\Model\RmaFactory;
use AlpineCommerce\Rma\Model\ResourceModel\Rma as RmaResource;
use AlpineCommerce\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use AlpineCommerce\Rma\Service\ReturnWindow;
use AlpineCommerce\Rma\Service\RmaService;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RmaServiceTest extends TestCase
{
    private RmaService $service;
    private RmaRepositoryInterface&MockObject $rmaRepository;
    private OrderRepositoryInterface&MockObject $orderRepository;
    private CreditmemoService&MockObject $creditmemoService;
    private ReturnWindow&MockObject $returnWindow;
    private RmaResource&MockObject $rmaResource;
    private RmaItemResource&MockObject $rmaItemResource;
    private RmaFactory&MockObject $rmaFactory;
    private ScopeConfigInterface&MockObject $scopeConfig;
    private LoggerInterface&MockObject $logger;
    private SearchCriteriaBuilder&MockObject $searchCriteriaBuilder;
    private CreditmemoFactory&MockObject $creditmemoFactory;

    protected function setUp(): void
    {
        $this->rmaRepository = $this->createMock(RmaRepositoryInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->creditmemoService = $this->createMock(CreditmemoService::class);
        $this->returnWindow = $this->createMock(ReturnWindow::class);
        $this->rmaResource = $this->createMock(RmaResource::class);
        $this->rmaItemResource = $this->createMock(RmaItemResource::class);
        $this->rmaFactory = $this->createMock(RmaFactory::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->creditmemoFactory = $this->createMock(CreditmemoFactory::class);

        $this->service = new RmaService(
            $this->rmaRepository,
            $this->createMock(\AlpineCommerce\Rma\Api\RmaItemRepositoryInterface::class),
            $this->orderRepository,
            $this->creditmemoFactory,
            $this->creditmemoService,
            $this->returnWindow,
            $this->rmaResource,
            $this->rmaItemResource,
            $this->searchCriteriaBuilder,
            $this->rmaFactory,
            $this->createMock(\AlpineCommerce\Rma\Model\RmaItemFactory::class),
            $this->scopeConfig,
            $this->logger
        );
    }

    public function testGetByOrderReturnsEmptyArrayWhenNoRmas(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $connection->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $connection->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $connection->expects($this->once())
            ->method('fetchCol')
            ->willReturn([]);

        $this->rmaResource->method('getConnection')->willReturn($connection);
        $this->rmaResource->method('getTable')->willReturn('alpinecommerce_rma');

        $result = $this->service->getByOrder(123);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testChangeStatusThrowsOnInvalidTransition(): void
    {
        $this->expectException(LocalizedException::class);

        $rma = $this->createMock(RmaInterface::class);
        $rma->method('getStatus')->willReturn(RmaInterface::STATUS_CLOSED);

        $this->rmaRepository->method('getById')->willReturn($rma);

        $this->service->changeStatus(1, RmaInterface::STATUS_APPROVED);
    }

    public function testCloseDelegatesToChangeStatus(): void
    {
        $rma = $this->createMock(RmaInterface::class);
        $rma->method('getStatus')->willReturn(RmaInterface::STATUS_REFUNDED);

        $this->rmaRepository->method('getById')->willReturn($rma);
        $this->rmaRepository->method('save')->willReturn($rma);

        $result = $this->service->close(1);

        $this->assertInstanceOf(RmaInterface::class, $result);
    }

    public function testGetStatsReturnsCorrectStructure(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->method('select')->willReturnSelf();
        $connection->method('from')->willReturnSelf();
        $connection->method('group')->willReturnSelf();
        $connection->method('fetchPairs')->willReturn([
            RmaInterface::STATUS_PENDING => 5,
            RmaInterface::STATUS_APPROVED => 3,
        ]);

        $this->rmaResource->method('getConnection')->willReturn($connection);

        $result = $this->service->getStats();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertEquals(8, $result['total']);
        $this->assertCount(2, $result['by_status']);
    }
}
