<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\Twin;

use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\Twin\TwinBlocker;
use AlpineCommerce\Turnstile\Model\Twin\TwinDefinition;
use AlpineCommerce\Turnstile\Test\Unit\Stub\RecordingLogger;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TwinBlockerTest extends TestCase
{
    private Config&MockObject $config;
    private RecordingLogger $logger;
    private TwinBlocker $blocker;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(6);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $this->logger = new RecordingLogger();
        $this->blocker = new TwinBlocker($this->config, $storeManager, $this->logger);
    }

    private function twin(): TwinDefinition
    {
        return new TwinDefinition('customer_create_account', 'REST', [], 'Magento\Customer\Api\AccountManagementInterface', 'createAccount');
    }

    /**
     * @dataProvider anonymousUserTypes
     */
    public function testBlocksAnAnonymousCallWhenTheSwitchIsOn(mixed $userType): void
    {
        $this->config->method('isTwinBlocked')->with('customer_create_account', 6)->willReturn(true);

        $this->assertTrue($this->blocker->mustBlock($this->twin(), $userType, 'rest'));
        $this->assertSame('warning', $this->logger->records[0]['level']);
        $this->assertSame(
            ['twin_id' => 'customer_create_account', 'channel' => 'rest', 'store_id' => 6],
            $this->logger->records[0]['context']
        );
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function anonymousUserTypes(): array
    {
        return ['no user context' => [null], 'guest' => [4], 'guest as string' => ['4']];
    }

    public function testLetsTheCallThroughWhenTheSwitchIsOff(): void
    {
        $this->config->method('isTwinBlocked')->willReturn(false);

        $this->assertFalse($this->blocker->mustBlock($this->twin(), null, 'rest'));
        $this->assertSame([], $this->logger->records);
    }

    /**
     * S30: integration (1), admin (2) and customer (3) callers are authenticated and never blocked.
     *
     * @dataProvider authenticatedUserTypes
     */
    public function testNeverBlocksAnAuthenticatedCaller(int $userType): void
    {
        $this->config->expects($this->never())->method('isTwinBlocked');

        $this->assertFalse($this->blocker->mustBlock($this->twin(), $userType, 'rest'));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function authenticatedUserTypes(): array
    {
        return ['integration' => [1], 'admin' => [2], 'customer' => [3]];
    }
}
