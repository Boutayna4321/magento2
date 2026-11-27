<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Unit\Service;

use AlpineCommerce\AutoInvoice\Api\Data\AutoInvoiceConfigInterface;
use AlpineCommerce\AutoInvoice\Api\Data\AutoInvoiceConfigInterfaceFactory;
use AlpineCommerce\AutoInvoice\Model\Data\AutoInvoiceConfig;
use AlpineCommerce\AutoInvoice\Model\Data\InvoiceResultFactory;
use AlpineCommerce\AutoInvoice\Model\InvoicePersister;
use AlpineCommerce\AutoInvoice\Service\InvoiceAutomationService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * getConfig() returns a web API data object instead of an array shape: Magento's web API can only describe
 * scalars, typed arrays and data interfaces, and one undescribable return type breaks SOAP for every service.
 */
class InvoiceAutomationServiceConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function service(array $values): InvoiceAutomationService
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn (string $path) => $values[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(static fn (string $path) => (bool) ($values[$path] ?? false));
        $configFactory = $this->getMockBuilder(AutoInvoiceConfigInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $configFactory->method('create')->willReturnCallback(static fn (): AutoInvoiceConfig => new AutoInvoiceConfig());

        return new InvoiceAutomationService(
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(InvoiceService::class),
            $scopeConfig,
            $this->getMockBuilder(InvoiceResultFactory::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(LoggerInterface::class),
            $configFactory,
            $this->createMock(InvoicePersister::class)
        );
    }

    public function testConfigIsADataObjectWithTheStoreValues(): void
    {
        $config = $this->service([
            'autoinvoice/general/enabled' => '1',
            'autoinvoice/general/payment_methods' => ' checkmo , ,banktransfer',
        ])->getConfig(6);

        $this->assertInstanceOf(AutoInvoiceConfigInterface::class, $config);
        $this->assertTrue($config->isEnabled());
        $this->assertSame(['checkmo', 'banktransfer'], $config->getPaymentMethods());
    }

    /**
     * Existing behaviour, kept as is: capture_mode is "offline" when payment methods are configured.
     * (It reads the payment methods setting; reported to the project owner as a probable defect.)
     */
    public function testCaptureModeKeepsItsExistingRule(): void
    {
        $this->assertSame('offline', $this->service(['autoinvoice/general/payment_methods' => 'checkmo'])->getConfig(6)->getCaptureMode());
        $this->assertSame('none', $this->service([])->getConfig(6)->getCaptureMode());
    }

    public function testDisabledStoreReportsDisabledWithoutPaymentMethods(): void
    {
        $config = $this->service([])->getConfig(6);

        $this->assertFalse($config->isEnabled());
        $this->assertSame([], $config->getPaymentMethods());
    }
}
