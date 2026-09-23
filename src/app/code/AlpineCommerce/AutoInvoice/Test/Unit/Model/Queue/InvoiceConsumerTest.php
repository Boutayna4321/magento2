<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Unit\Model\Queue;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceMessageInterface;
use AlpineCommerce\AutoInvoice\Api\InvoiceAutomationInterface;
use AlpineCommerce\AutoInvoice\Model\Queue\InvoiceConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InvoiceConsumerTest extends TestCase
{
    private InvoiceConsumer $consumer;
    private InvoiceAutomationInterface&MockObject $invoiceService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->invoiceService = $this->createMock(InvoiceAutomationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->consumer = new InvoiceConsumer(
            $this->invoiceService,
            $this->logger
        );
    }

    public function testProcessCallsServiceWithCorrectParameters(): void
    {
        $message = $this->createConfiguredMock(InvoiceMessageInterface::class, [
            'getOrderId' => 123,
            'getStoreId' => 1,
            'getCorrelationId' => 'test-correlation-123',
        ]);

        $result = $this->createConfiguredMock(
            \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface::class,
            ['isSuccess' => true]
        );

        $this->invoiceService->expects($this->once())
            ->method('processOrder')
            ->with(123)
            ->willReturn($result);

        $this->logger->expects($this->atLeast(2))
            ->method('info');

        $this->consumer->process($message);
    }

    public function testProcessThrowsOnFailureAndLogsError(): void
    {
        $message = $this->createConfiguredMock(InvoiceMessageInterface::class, [
            'getOrderId' => 456,
            'getStoreId' => 2,
            'getCorrelationId' => 'error-correlation',
        ]);

        $this->invoiceService->method('processOrder')
            ->willThrowException(new \RuntimeException('Invoice failed'));

        $this->logger->expects($this->once())->method('error');

        $this->expectException(\RuntimeException::class);

        $this->consumer->process($message);
    }

    public function testProcessHandlesNullCorrelationId(): void
    {
        $message = $this->createConfiguredMock(InvoiceMessageInterface::class, [
            'getOrderId' => 789,
            'getStoreId' => 0,
            'getCorrelationId' => null,
        ]);

        $result = $this->createConfiguredMock(
            \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface::class,
            ['isSuccess' => true]
        );

        $this->invoiceService->method('processOrder')->willReturn($result);
        $this->logger->expects($this->atLeast(1))->method('info');

        $this->consumer->process($message);
    }
}
