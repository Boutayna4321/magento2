<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Integration\Api;

use AlpineCommerce\AutoInvoice\Api\Data\AutoInvoiceConfigInterface;
use AlpineCommerce\AutoInvoice\Api\InvoiceAutomationInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Webapi\Model\ServiceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * The AutoInvoice web API contract can be described by Magento: before, getConfig() documented an array
 * shape, the SOAP metadata of every service failed ("The \"array\" class doesn't exist...") and SOAP was
 * down for the whole store.
 *
 * @magentoAppArea webapi_soap
 * @magentoAppIsolation enabled
 */
class WebapiContractTest extends TestCase
{
    public function testSoapMetadataOfAnotherServiceCanBeBuilt(): void
    {
        $metadata = Bootstrap::getObjectManager()->get(ServiceMetadata::class)
            ->getServiceMetadata('customerAccountManagementV1');

        $this->assertArrayHasKey('methods', $metadata);
    }

    public function testAutoInvoiceServiceExposesGetConfig(): void
    {
        $serviceMetadata = Bootstrap::getObjectManager()->get(ServiceMetadata::class);
        $name = $serviceMetadata->getServiceName(InvoiceAutomationInterface::class, 'V1');

        $this->assertArrayHasKey('getConfig', $serviceMetadata->getServiceMetadata($name)['methods']);
    }

    /**
     * REST clients keep the same keys as before: enabled, payment_methods, capture_mode.
     */
    public function testWebapiOutputKeepsTheSameKeys(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $config = $objectManager->create(AutoInvoiceConfigInterface::class)
            ->setEnabled(true)
            ->setPaymentMethods(['checkmo'])
            ->setCaptureMode('offline');

        $output = $objectManager->get(DataObjectProcessor::class)
            ->buildOutputDataArray($config, AutoInvoiceConfigInterface::class);

        $this->assertSame(['enabled' => true, 'payment_methods' => ['checkmo'], 'capture_mode' => 'offline'], $output);
    }
}
