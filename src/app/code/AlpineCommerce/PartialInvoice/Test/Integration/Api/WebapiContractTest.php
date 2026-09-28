<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Test\Integration\Api;

use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceConfigInterface;
use AlpineCommerce\PartialInvoice\Api\PartialInvoiceInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Webapi\Model\ServiceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * The PartialInvoice web API contract can be described by Magento: before, getConfig() documented an array
 * shape and the SOAP metadata of every service failed.
 *
 * @magentoAppArea webapi_soap
 * @magentoAppIsolation enabled
 */
class WebapiContractTest extends TestCase
{
    public function testPartialInvoiceServiceExposesGetConfig(): void
    {
        $serviceMetadata = Bootstrap::getObjectManager()->get(ServiceMetadata::class);
        $name = $serviceMetadata->getServiceName(PartialInvoiceInterface::class, 'V1');

        $this->assertArrayHasKey('getConfig', $serviceMetadata->getServiceMetadata($name)['methods']);
    }

    /**
     * REST clients keep the same keys as before: enabled, payment_methods, allow_backorders, min_qty_to_invoice.
     */
    public function testWebapiOutputKeepsTheSameKeys(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $config = $objectManager->create(PartialInvoiceConfigInterface::class)
            ->setEnabled(true)
            ->setPaymentMethods(['checkmo'])
            ->setAllowBackorders(false)
            ->setMinQtyToInvoice(1.5);

        $output = $objectManager->get(DataObjectProcessor::class)
            ->buildOutputDataArray($config, PartialInvoiceConfigInterface::class);

        $this->assertSame(
            ['enabled' => true, 'payment_methods' => ['checkmo'], 'allow_backorders' => false, 'min_qty_to_invoice' => 1.5],
            $output
        );
    }
}
