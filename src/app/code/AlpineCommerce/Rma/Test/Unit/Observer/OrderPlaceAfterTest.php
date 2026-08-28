<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\Rma\Test\Unit\Observer;

use AlpineCommerce\Rma\Observer\OrderPlaceAfter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Object as FrameworkObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrderPlaceAfterTest extends TestCase
{
    public function testSkipsWhenEventDoesNotProvideAnOrder(): void
    {
        $observer = $this->createConfiguredMock(Observer::class, [
            'getEvent' => $this->createConfiguredMock(Event::class, ['getOrder' => null]),
        ]);

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::never())->method('save');

        $this->createObserver()->execute($observer);

        $this->addToAssertionCount(1);
    }

    public function testSkipsWhenModuleDisabled(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);
        $order->expects(self::never())->method('save');

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(false);

        $this->createObserver($config)->execute($this->wrapOrder($order));

        $this->addToAssertionCount(1);
    }

    public function testSetsExpirationAndEnabledFlagOnOrder(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);
        $order->expects(self::once())->method('setData');
        $order->expects(self::once())->method('addStatusHistoryComment')
            ->willReturn($this->createMock(FrameworkObject::class));
        $order->expects(self::once())->method('save');

        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')->willReturn($this->createConfiguredMock(\DateTime::class, [
            'format' => '2026-09-27 12:00:00',
        ]));

        $this->createObserver($this->getEnabledConfig(), $timezone)
            ->execute($this->wrapOrder($order));
    }

    public function testUsesConfiguredReturnWindow(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);
        $order->method('setData')->willReturnSelf();
        $order->method('addStatusHistoryComment')->willReturn($this->createMock(FrameworkObject::class));
        $order->expects(self::once())->method('save');

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        $config->expects(self::once())->method('getValue')
            ->with('rma/general/allow_return_days', 'store', 1)
            ->willReturn(14);

        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')->willReturn($this->createConfiguredMock(\DateTime::class));

        $this->createObserver($config, $timezone, $this->createMock(LoggerInterface::class))
            ->execute($this->wrapOrder($order));

        $this->addToAssertionCount(1);
    }

    public function testLoggerIsInjectedButNeverCalled(): void
    {
        // NOTE: OrderPlaceAfter injects LoggerInterface but execute() has no try/catch,
        // so the logger is currently unused (see code-quality finding). Verifying that fact.
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);
        $order->method('setData')->willReturnSelf();
        $order->method('addStatusHistoryComment')->willReturn($this->createMock(FrameworkObject::class));
        $order->method('save');

        $this->createObserver($this->getEnabledConfig(), $this->createMock(TimezoneInterface::class), $logger)
            ->execute($this->wrapOrder($order));

        $this->addToAssertionCount(1);
    }

    private function createObserver(
        ?ScopeConfigInterface $config = null,
        ?TimezoneInterface $timezone = null,
        ?LoggerInterface $logger = null
    ): OrderPlaceAfter {
        return new OrderPlaceAfter(
            $config ?? $this->getEnabledConfig(),
            $timezone ?? $this->createMock(TimezoneInterface::class),
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }

    private function getEnabledConfig(): MockObject&ScopeConfigInterface
    {
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        $config->method('getValue')->willReturn(null);
        return $config;
    }

    private function wrapOrder(OrderInterface $order): MockObject&Observer
    {
        $event = $this->createConfiguredMock(Event::class, ['getOrder' => $order]);
        return $this->createConfiguredMock(Observer::class, ['getEvent' => $event]);
    }
}
