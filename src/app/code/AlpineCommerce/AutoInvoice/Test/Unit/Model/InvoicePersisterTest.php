<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Unit\Model;

use AlpineCommerce\AutoInvoice\Model\InvoicePersister;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;

/**
 * Saving through the repositories (not $invoice->save()) lets other modules hook in:
 * LoyaltyProgram awards points in a plugin on InvoiceRepositoryInterface::save().
 * setIsInProcess(true) is what moves the order from "new" to "processing", as
 * Magento's own admin invoice controller does.
 */
class InvoicePersisterTest extends TestCase
{
    public function testSavesOrderThenInvoiceThroughRepositoriesAndMarksOrderInProcess(): void
    {
        $calls = [];

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()
            ->addMethods(['setIsInProcess'])->getMock();
        $order->expects($this->once())->method('setIsInProcess')->with(true)
            ->willReturnCallback(function () use (&$calls, $order) {
                $calls[] = 'in_process';
                return $order;
            });

        $invoice = $this->createMock(Invoice::class);
        $invoice->expects($this->never())->method('save');

        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $orderRepository->expects($this->once())->method('save')->with($order)
            ->willReturnCallback(function ($o) use (&$calls) {
                $calls[] = 'order';
                return $o;
            });

        $invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $invoiceRepository->expects($this->once())->method('save')->with($invoice)
            ->willReturnCallback(function ($i) use (&$calls) {
                $calls[] = 'invoice';
                return $i;
            });

        (new InvoicePersister($orderRepository, $invoiceRepository))->persist($order, $invoice);

        $this->assertSame(['in_process', 'order', 'invoice'], $calls);
    }
}
