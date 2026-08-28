<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Unit\Observer;

use AlpineCommerce\AutoInvoice\Observer\AutoInvoice;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AutoInvoiceTest extends TestCase
{
    private const ENABLED = 'autoinvoice/general/enabled';
    private const PAYMENT_METHODS = 'autoinvoice/general/payment_methods';

    private function createObserver(InvoiceService $invoiceService, ScopeConfigInterface $scopeConfig, LoggerInterface $logger): AutoInvoice
    {
        return new AutoInvoice($invoiceService, $scopeConfig, $logger);
    }

    public function testSkipsWhenEventDoesNotProvideAnOrder(): void
    {
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');
        $observer = $this->createConfiguredMock(Observer::class, ['getEvent' => $this->createConfiguredMock(Event::class, ['getOrder' => null])]);

        $this->createObserver($invoiceService, $this->createMock(ScopeConfigInterface::class), $this->createMock(LoggerInterface::class))
            ->execute($observer);

        $this->assertTrue(true);
    }

    public function testSkipsWhenOrderAlreadyHasAnInvoice(): void
    {
        $order = $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => 50.00]);
        $event = $this->createConfiguredMock(Event::class, ['getOrder' => $order]);
        $observer = $this->createConfiguredMock(Observer::class, ['getEvent' => $event]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $this->createMock(ScopeConfigInterface::class), $this->createMock(LoggerInterface::class))
            ->execute($observer);

        $order->expects(self::never())->method('canInvoice');
    }

    public function testSkipsWhenModuleDisabled(): void
    {
        $order = $this->getQualifiedOrderMock(0.0);
        $order->method('getStoreId')->willReturn(1);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnMap([[self::ENABLED, 'store', 1, false]]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $scopeConfig, $this->createMock(LoggerInterface::class))
            ->execute($this->wrapOrder($order));
    }

    public function testSkipsWhenPaymentMethodNotAllowed(): void
    {
        $order = $this->getQualifiedOrderMock(0.0);
        $order->method('getStoreId')->willReturn(1);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnMap([[self::ENABLED, 'store', 1, true]]);
        $scopeConfig->method('getValue')->willReturnMap([[self::PAYMENT_METHODS, 'store', 1, 'checkmo']]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $scopeConfig, $this->createMock(LoggerInterface::class))
            ->execute($this->wrapOrder($order));
    }

    public function testSkipsWhenOrderCannotBeInvoiced(): void
    {
        $order = $this->getQualifiedOrderMock(0.0);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'checkmo'])
        );
        $order->method('canInvoice')->willReturn(false);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnMap([[self::ENABLED, 'store', 1, true]]);
        $scopeConfig->method('getValue')->willReturnMap([[self::PAYMENT_METHODS, 'store', 1, 'checkmo']]);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->expects(self::never())->method('prepareInvoice');

        $this->createObserver($invoiceService, $scopeConfig, $this->createMock(LoggerInterface::class))
            ->execute($this->wrapOrder($order));
    }

    public function testCreatesInvoiceWhenQualified(): void
    {
        $order = $this->getQualifiedOrderMock(0.0);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'checkmo'])
        );
        $order->method('canInvoice')->willReturn(true);
        $order->method('addStatusHistoryComment')->willReturnSelf();

        $invoice = $this->createMock(\Magento\Sales\Api\Data\InvoiceInterface::class);
        $invoice->method('getTotalQty')->willReturn(2);
        $invoice->expects(self::once())->method('register');
        $invoice->expects(self::once())->method('setIsPaid')->with(true);
        $invoice->expects(self::once())->method('save');

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('prepareInvoice')->willReturn($invoice);

        $this->createObserver($invoiceService, $this->getEnabledConfig(), $this->createMock(LoggerInterface::class))
            ->execute($this->wrapOrder($order));

        $order->expects(self::once())->method('save');
    }

    public function testDoesNotCreateInvoiceWhenQuantityIsZero(): void
    {
        $order = $this->getQualifiedOrderMock(0.0);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'free'])
        );
        $order->method('canInvoice')->willReturn(true);

        $invoice = $this->createMock(\Magento\Sales\Api\Data\InvoiceInterface::class);
        $invoice->method('getTotalQty')->willReturn(0);
        $invoice->expects(self::never())->method('register');

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('prepareInvoice')->willReturn($invoice);

        $this->createObserver($invoiceService, $this->getEnabledConfig('free'), $this->createMock(LoggerInterface::class))
            ->execute($this->wrapOrder($order));
    }

    public function testLogsAndContinuesWhenInvoicePreparationFails(): void
    {
        $order = $this->getQualifiedOrderMock(0.0);
        $exception = new \RuntimeException('prepareInvoice failed');

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('prepareInvoice')->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $this->createObserver($invoiceService, $this->getEnabledConfig(), $logger)
            ->execute($this->wrapOrder($order));

        $this->assertTrue(true);
    }

    private function getQualifiedOrderMock(float $invoiced): MockObject&OrderInterface
    {
        return $this->createConfiguredMock(OrderInterface::class, ['getTotalInvoiced' => $invoiced]);
    }

    private function getEnabledConfig(?string $method = null): MockObject&ScopeConfigInterface
    {
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        if ($method !== null) {
            $config->method('getValue')->willReturnMap([[self::PAYMENT_METHODS, 'store', 1, $method]]);
        } else {
            $config->method('getValue')->willReturn('');
        }
        return $config;
    }

    private function wrapOrder(OrderInterface $order): MockObject&Observer
    {
        $event = $this->createConfiguredMock(Event::class, ['getOrder' => $order]);
        return $this->createConfiguredMock(Observer::class, ['getEvent' => $event]);
    }
}
