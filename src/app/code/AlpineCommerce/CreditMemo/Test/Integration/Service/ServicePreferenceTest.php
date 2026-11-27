<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Test\Integration\Service;

use AlpineCommerce\CreditMemo\Api\CreditMemoAutomationInterface;
use AlpineCommerce\CreditMemo\Model\Queue\CreditMemoConsumer;
use AlpineCommerce\CreditMemo\Service\CreditMemoService;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The service contract has an implementation: before, di.xml had no preference, and the REST routes and the
 * queue consumer failed with "Cannot instantiate interface ...CreditMemoAutomationInterface".
 */
class ServicePreferenceTest extends TestCase
{
    public function testServiceContractResolvesToTheService(): void
    {
        $this->assertInstanceOf(
            CreditMemoService::class,
            Bootstrap::getObjectManager()->get(CreditMemoAutomationInterface::class)
        );
    }

    public function testQueueConsumerCanBeCreated(): void
    {
        $this->assertInstanceOf(CreditMemoConsumer::class, Bootstrap::getObjectManager()->create(CreditMemoConsumer::class));
    }
}
