<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Test\Integration\Api;

use AlpineCommerce\CreditMemo\Api\CreditMemoAutomationInterface;
use AlpineCommerce\CreditMemo\Api\Data\CreditMemoConfigInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Webapi\Model\ServiceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * The CreditMemo web API contract can be described by Magento: before, getConfig() documented an array
 * shape and the SOAP metadata of every service failed.
 *
 * @magentoAppArea webapi_soap
 * @magentoAppIsolation enabled
 */
class WebapiContractTest extends TestCase
{
    public function testCreditMemoServiceExposesGetConfig(): void
    {
        $serviceMetadata = Bootstrap::getObjectManager()->get(ServiceMetadata::class);
        $name = $serviceMetadata->getServiceName(CreditMemoAutomationInterface::class, 'V1');

        $this->assertArrayHasKey('getConfig', $serviceMetadata->getServiceMetadata($name)['methods']);
    }

    /**
     * REST clients keep the same keys as before: enabled, payment_methods, auto_refund.
     */
    public function testWebapiOutputKeepsTheSameKeys(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $config = $objectManager->create(CreditMemoConfigInterface::class)
            ->setEnabled(true)
            ->setPaymentMethods(['checkmo'])
            ->setAutoRefund(false);

        $output = $objectManager->get(DataObjectProcessor::class)
            ->buildOutputDataArray($config, CreditMemoConfigInterface::class);

        $this->assertSame(['enabled' => true, 'payment_methods' => ['checkmo'], 'auto_refund' => false], $output);
    }
}
