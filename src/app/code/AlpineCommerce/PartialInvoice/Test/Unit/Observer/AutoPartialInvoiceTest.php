<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Test\Unit\Observer;

use AlpineCommerce\PartialInvoice\Observer\AutoPartialInvoice;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AutoPartialInvoiceTest extends TestCase
{
    public function testSkipsWhenOrderAlreadyHasAnInvoice(): void
    {
        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 10.00]);
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService)->execute($this->wrapOrder($order));

        $this->addToAssertionCount(1);
    }

    public function testSkipsWhenModuleDisabled(): void
    {
        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 0.0]);
        $order->method('getStoreId')->willReturn(1);

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(false);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $config)->execute($this->wrapOrder($order));
    }

    public function testSkipsWhenPaymentMethodNotAllowed(): void
    {
        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 0.0]);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'free'])
        );
        $order->method('canInvoice')->willReturn(true);

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        $config->method('getValue')->willReturnMap([
            ['partialinvoice/general/payment_methods', 'store', 1, 'checkmo'],
        ]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $config)->execute($this->wrapOrder($order));
    }

    public function testBailsWhenNoShippableItems(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getQtyOrdered')->willReturn(1.0);
        $item->method('getQtyInvoiced')->willReturn(1.0); // nothing left
        $item->method('getItemId')->willReturn(10);
        $item->method('getQtyBackordered')->willReturn(0.0);

        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 0.0]);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'checkmo'])
        );
        $order->method('canInvoice')->willReturn(true);
        $order->method('getAllItems')->willReturn([$item]);

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnMap([
            ['partialinvoice/general/enabled', 'store', 1, true],
            ['partialinvoice/general/allow_backorders', 'store', 1, false],
        ]);
        $config->method('getValue')->willReturnMap([
            ['partialinvoice/general/payment_methods', 'store', 1, ''],
            ['partialinvoice/general/min_qty_to_invoice', 'store', 1, 0],
        ]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $config)->execute($this->wrapOrder($order));
    }

    public function testInvoicesOnlyShippableQuantity(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getQtyOrdered')->willReturn(3.0);
        $item->method('getQtyInvoiced')->willReturn(1.0); // 2 available
        $item->method('getItemId')->willReturn(42);
        $item->method('getQtyBackordered')->willReturn(0.0);

        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 0.0]);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'checkmo'])
        );
        $order->method('canInvoice')->willReturn(true);
        $order->method('getAllItems')->willReturn([$item]);
        $order->method('addStatusHistoryComment')->willReturnSelf();

        $invoice = $this->createMock(\Magento\Sales\Api\Data\InvoiceInterface::class);
        $invoice->method('getTotalQty')->willReturn(2);
        $invoice->expects(self::once())->method('register');
        $invoice->expects(self::once())->method('save');

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnMap([
            ['partialinvoice/general/enabled', 'store', 1, true],
            ['partialinvoice/general/allow_backorders', 'store', 1, false],
        ]);
        $config->method('getValue')->willReturnMap([
            ['partialinvoice/general/payment_methods', 'store', 1, ''],
            ['partialinvoice/general/min_qty_to_invoice', 'store', 1, 0],
        ]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::once())
            ->method('prepareInvoice')
            ->with(self::anything(), [42 => 2.0])
            ->willReturn($invoice);

        $this->createObserver($invoiceService, $config)->execute($this->wrapOrder($order));

        $order->expects(self::once())->method('save');
    }

    public function testLogsAndContinuesOnException(): void
    {
        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 0.0]);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'checkmo'])
        );
        $order->method('canInvoice')->willReturn(true);
        $order->method('getAllItems')->willReturn([]); // empty -> bails before invoice
        $order->expects(self::never())->method('save');

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $this->createObserver($this->createMock(InvoiceService::class), $config, $logger)
            ->execute($this->wrapOrder($order));

        $this->addToAssertionCount(1);
    }

    private function createObserver(
        InvoiceService $invoiceService,
        ?ScopeConfigInterface $scopeConfig = null,
        ?LoggerInterface $logger = null
    ): AutoPartialInvoice {
        return new AutoPartialInvoice($invoiceService, $scopeConfig ?? $this->createMock(ScopeConfigInterface::class), $logger ?? $this->createMock(LoggerInterface::class));
    }

    private function wrapOrder(OrderInterface $order): MockObject&Observer
    {
        $event = $this->createConfiguredMock(Event::class, ['getOrder' => $order]);
        return $this->createConfiguredMock(Observer::class, ['getEvent' => $event]);
    }
}
