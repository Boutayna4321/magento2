<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Test\Unit\Plugin;

use AlpineCommerce\CreditMemo\Plugin\OrderCancelPlugin;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrderCancelPluginTest extends TestCase
{
    public function testReturnsOriginalResultWhenCancellationAlreadyFailed(): void
    {
        $order = $this->createMock(Order::class);
        $plugin = $this->createPlugin();

        self::assertSame(false, $plugin->afterCancel($order, false));

        $order->expects(self::never())->method('canCreditmemo');
    }

    public function testSkipsWhenModuleDisabled(): void
    {
        $order = $this->createConfiguredMock(Order::class, ['canCreditmemo' => true]);
        $order->method('getStoreId')->willReturn(1);

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(false);

        $plugin = $this->createPlugin($config);

        $order->expects(self::never())->method('getPayment');
        self::assertSame(true, $plugin->afterCancel($order, true));
    }

    public function testSkipsWhenPaymentMethodNotAllowed(): void
    {
        $order = $this->createConfiguredMock(Order::class, ['canCreditmemo' => true]);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'free'])
        );

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        $config->method('getValue')->willReturnMap([
            ['autocreditmemo/general/payment_methods', 'store', 1, 'checkmo'],
        ]);

        $this->createMock(CreditmemoFactory::class)->expects(self::never())->method('createByOrder');

        self::assertSame(true, $this->createPlugin($config)->afterCancel($order, true));
    }

    public function testSkipsWhenOrderCannotBeCredited(): void
    {
        $order = $this->createConfiguredMock(Order::class, ['canCreditmemo' => false]);
        $order->method('getStoreId')->willReturn(1);

        self::assertSame(true, $this->createPlugin($this->getEnabledConfig())->afterCancel($order, true));
    }

    public function testCreatesOpenCreditMemoWhenAutoRefundDisabled(): void
    {
        $order = $this->buildQualifiedOrder();
        $order->method('canCreditmemo')->willReturn(true);

        $creditmemo = $this->createMock(Creditmemo::class);
        $creditmemo->method('getTotalQty')->willReturn(1);
        $creditmemo->expects(self::once())->method('setState')->with(Creditmemo::STATE_OPEN);
        $creditmemo->expects(self::once())->method('save');
        $creditmemo->expects(self::never())->method('delete');

        $factory = $this->createConfiguredMock(CreditmemoFactory::class, ['createByOrder' => $creditmemo]);

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnMap([
            ['autocreditmemo/general/enabled', 'store', 1, true],
            ['autocreditmemo/general/auto_refund', 'store', 1, false],
        ]);

        $plugin = $this->createPlugin($config, $factory);

        self::assertSame(true, $plugin->afterCancel($order, true));
    }

    public function testRefundsWhenAutoRefundEnabled(): void
    {
        $order = $this->buildQualifiedOrder();
        $order->method('canCreditmemo')->willReturn(true);

        $creditmemo = $this->createMock(Creditmemo::class);
        $creditmemo->method('getTotalQty')->willReturn(1);
        $creditmemo->expects(self::never())->method('save'); // auto_refund branch calls refund(), not save()

        $factory = $this->createConfiguredMock(CreditmemoFactory::class, ['createByOrder' => $creditmemo]);
        $service = $this->createMock(CreditmemoService::class);
        $service->expects(self::once())->method('refund')->with($creditmemo);

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnMap([
            ['autocreditmemo/general/enabled', 'store', 1, true],
            ['autocreditmemo/general/auto_refund', 'store', 1, true],
        ]);

        $plugin = $this->createPlugin($config, $factory, $service);

        self::assertSame(true, $plugin->afterCancel($order, true));
    }

    public function testBailsWhenNoQuantityToCredit(): void
    {
        $order = $this->buildQualifiedOrder();
        $order->method('canCreditmemo')->willReturn(true);

        $creditmemo = $this->createMock(Creditmemo::class);
        $creditmemo->method('getTotalQty')->willReturn(0);

        $factory = $this->createMock(CreditmemoFactory::class);
        $factory->expects(self::once())->method('createByOrder')->willReturn($creditmemo);

        // getTotalQty()==0 bails before refund/save, but createByOrder was already called:
        $service = $this->createMock(CreditmemoService::class);
        $service->expects(self::never())->method('refund');
        $creditmemo->expects(self::never())->method('save');
    }

    public function testLogsAndContinuesWhenCreditMemoCreationFails(): void
    {
        $order = $this->buildQualifiedOrder();
        $order->method('canCreditmemo')->willReturn(true);

        $factory = $this->createMock(CreditmemoFactory::class);
        $factory->method('createByOrder')->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $plugin = $this->createPlugin($this->getEnabledConfig(), $factory, null, $logger);

        // should not throw, should return original result
        self::assertSame(true, $plugin->afterCancel($order, true));
    }

    private function createPlugin(
        ?ScopeConfigInterface $config = null,
        ?CreditmemoFactory $factory = null,
        ?CreditmemoService $service = null,
        ?LoggerInterface $logger = null
    ): OrderCancelPlugin {
        return new OrderCancelPlugin(
            $factory ?? $this->createMock(CreditmemoFactory::class),
            $service ?? $this->createMock(CreditmemoService::class),
            $config ?? $this->getEnabledConfig(),
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }

    private function getEnabledConfig(): MockObject&ScopeConfigInterface
    {
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        $config->method('getValue')->willReturn('');
        return $config;
    }

    private function buildQualifiedOrder(): MockObject&Order
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getQtyOrdered')->willReturn(1.0);
        $item->method('getQtyRefunded')->willReturn(0.0);
        $item->method('getId')->willReturn(7);

        $order = $this->createConfiguredMock(Order::class, ['canCreditmemo' => true]);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getPayment')->willReturn(
            $this->createConfiguredMock(\Magento\Payment\Model\MethodInterface::class, ['getCode' => 'checkmo'])
        );
        $order->method('getAllItems')->willReturn([$item]);
        $order->method('addStatusHistoryComment')->willReturnSelf();
        return $order;
    }
}
