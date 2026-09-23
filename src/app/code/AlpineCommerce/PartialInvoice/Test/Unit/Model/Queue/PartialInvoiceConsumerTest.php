<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Test\Unit\Model\Queue;

use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface;
use AlpineCommerce\PartialInvoice\Api\PartialInvoiceInterface;
use AlpineCommerce\PartialInvoice\Model\Queue\PartialInvoiceConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PartialInvoiceConsumerTest extends TestCase
{
    private PartialInvoiceConsumer $consumer;
    private PartialInvoiceInterface&MockObject $partialInvoiceService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->partialInvoiceService = $this->createMock(PartialInvoiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->consumer = new PartialInvoiceConsumer(
            $this->partialInvoiceService,
            $this->logger
        );
    }

    public function testProcessCallsServiceWithCorrectOrderId(): void
    {
        $message = $this->createConfiguredMock(PartialInvoiceMessageInterface::class, [
            'getOrderId' => 123,
            'getStoreId' => 1,
            'getCorrelationId' => 'partialinvoice-test-123',
        ]);

        $result = $this->createConfiguredMock(
            \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface::class,
            ['isSuccess' => true]
        );

        $this->partialInvoiceService->expects($this->once())
            ->method('processOrder')
            ->with(123)
            ->willReturn($result);

        $this->logger->expects($this->atLeast(2))->method('info');

        $this->consumer->process($message);
    }

    public function testProcessLogsAndRethrowsOnFailure(): void
    {
        $message = $this->createConfiguredMock(PartialInvoiceMessageInterface::class, [
            'getOrderId' => 456,
            'getStoreId' => 1,
            'getCorrelationId' => 'partialinvoice-error',
        ]);

        $this->partialInvoiceService->method('processOrder')
            ->willThrowException(new \RuntimeException('Partial invoice failed'));

        $this->logger->expects($this->once())->method('error');

        $this->expectException(\RuntimeException::class);

        $this->consumer->process($message);
    }
}
