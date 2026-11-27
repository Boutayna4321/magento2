<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Test\Unit\Service;

use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceConfigInterface;
use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceConfigInterfaceFactory;
use AlpineCommerce\PartialInvoice\Model\Data\PartialInvoiceConfig;
use AlpineCommerce\PartialInvoice\Service\PartialInvoiceService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * getConfig() returns a web API data object instead of an array shape: Magento's web API can only describe
 * scalars, typed arrays and data interfaces, and one undescribable return type breaks SOAP for every service.
 */
class PartialInvoiceServiceConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function service(array $values): PartialInvoiceService
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn (string $path) => $values[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(static fn (string $path) => (bool) ($values[$path] ?? false));
        $configFactory = $this->getMockBuilder(PartialInvoiceConfigInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $configFactory->method('create')->willReturnCallback(static fn (): PartialInvoiceConfig => new PartialInvoiceConfig());

        return new PartialInvoiceService(
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(InvoiceService::class),
            $scopeConfig,
            $this->createMock(LoggerInterface::class),
            $configFactory
        );
    }

    public function testConfigIsADataObjectWithTheStoreValues(): void
    {
        $config = $this->service([
            'partialinvoice/general/enabled' => '1',
            'partialinvoice/general/payment_methods' => ' checkmo , ,banktransfer',
            'partialinvoice/general/allow_backorders' => '1',
            'partialinvoice/general/min_qty_to_invoice' => '2.5',
        ])->getConfig(6);

        $this->assertInstanceOf(PartialInvoiceConfigInterface::class, $config);
        $this->assertTrue($config->isEnabled());
        $this->assertSame(['checkmo', 'banktransfer'], $config->getPaymentMethods());
        $this->assertTrue($config->isAllowBackorders());
        $this->assertSame(2.5, $config->getMinQtyToInvoice());
    }

    public function testDisabledStoreReportsDefaults(): void
    {
        $config = $this->service([])->getConfig(6);

        $this->assertFalse($config->isEnabled());
        $this->assertSame([], $config->getPaymentMethods());
        $this->assertFalse($config->isAllowBackorders());
        $this->assertSame(0.0, $config->getMinQtyToInvoice());
    }
}
