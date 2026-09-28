<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Test\Unit\Service;

use AlpineCommerce\CreditMemo\Api\Data\CreditMemoConfigInterface;
use AlpineCommerce\CreditMemo\Api\Data\CreditMemoConfigInterfaceFactory;
use AlpineCommerce\CreditMemo\Model\Data\CreditMemoConfig;
use AlpineCommerce\CreditMemo\Service\CreditMemoService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService as MagentoCreditmemoService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * getConfig() returns a web API data object instead of an array shape: Magento's web API can only describe
 * scalars, typed arrays and data interfaces, and one undescribable return type breaks SOAP for every service.
 */
class CreditMemoServiceConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function service(array $values): CreditMemoService
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn (string $path) => $values[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(static fn (string $path) => (bool) ($values[$path] ?? false));
        $configFactory = $this->getMockBuilder(CreditMemoConfigInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $configFactory->method('create')->willReturnCallback(static fn (): CreditMemoConfig => new CreditMemoConfig());

        return new CreditMemoService(
            $this->createMock(OrderRepositoryInterface::class),
            $this->getMockBuilder(CreditmemoFactory::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(MagentoCreditmemoService::class),
            $scopeConfig,
            $this->createMock(LoggerInterface::class),
            $configFactory
        );
    }

    public function testConfigIsADataObjectWithTheStoreValues(): void
    {
        $config = $this->service([
            'autocreditmemo/general/enabled' => '1',
            'autocreditmemo/general/payment_methods' => ' checkmo , ,banktransfer',
            'autocreditmemo/general/auto_refund' => '1',
        ])->getConfig(6);

        $this->assertInstanceOf(CreditMemoConfigInterface::class, $config);
        $this->assertTrue($config->isEnabled());
        $this->assertSame(['checkmo', 'banktransfer'], $config->getPaymentMethods());
        $this->assertTrue($config->isAutoRefund());
    }

    public function testDisabledStoreReportsDisabledWithoutPaymentMethods(): void
    {
        $config = $this->service([])->getConfig(6);

        $this->assertFalse($config->isEnabled());
        $this->assertSame([], $config->getPaymentMethods());
        $this->assertFalse($config->isAutoRefund());
    }
}
