<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Test\Unit\Model\Queue;

use AlpineCommerce\CreditMemo\Api\CreditMemoAutomationInterface;
use AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface;
use AlpineCommerce\CreditMemo\Model\Queue\CreditMemoConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreditMemoConsumerTest extends TestCase
{
    private CreditMemoConsumer $consumer;
    private CreditMemoAutomationInterface&MockObject $creditMemoService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->creditMemoService = $this->createMock(CreditMemoAutomationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->consumer = new CreditMemoConsumer(
            $this->creditMemoService,
            $this->logger
        );
    }

    public function testProcessCallsServiceWithCorrectOrderId(): void
    {
        $message = $this->createConfiguredMock(CreditMemoMessageInterface::class, [
            'getOrderId' => 123,
            'getStoreId' => 1,
            'getCorrelationId' => 'creditmemo-test-123',
        ]);

        $result = $this->createConfiguredMock(
            \AlpineCommerce\CreditMemo\Api\Data\CreditMemoResultInterface::class,
            ['isSuccess' => true]
        );

        $this->creditMemoService->expects($this->once())
            ->method('processCancellation')
            ->with(123)
            ->willReturn($result);

        $this->logger->expects($this->atLeast(2))->method('info');

        $this->consumer->process($message);
    }

    public function testProcessLogsAndRethrowsOnFailure(): void
    {
        $message = $this->createConfiguredMock(CreditMemoMessageInterface::class, [
            'getOrderId' => 456,
            'getStoreId' => 1,
            'getCorrelationId' => 'creditmemo-error',
        ]);

        $this->creditMemoService->method('processCancellation')
            ->willThrowException(new \RuntimeException('Credit memo creation failed'));

        $this->logger->expects($this->once())->method('error');

        $this->expectException(\RuntimeException::class);

        $this->consumer->process($message);
    }
}
