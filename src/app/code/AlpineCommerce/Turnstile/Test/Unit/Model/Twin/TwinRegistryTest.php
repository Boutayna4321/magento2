<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\Twin;

use AlpineCommerce\Turnstile\Model\Twin\TwinDefinition;
use AlpineCommerce\Turnstile\Model\Twin\TwinRegistry;
use Magento\Framework\Module\Manager as ModuleManager;
use PHPUnit\Framework\TestCase;

class TwinRegistryTest extends TestCase
{
    private const SERVICE = 'Magento\Customer\Api\AccountManagementInterface';

    /**
     * @param array<string, mixed> $twins
     * @param string[] $enabledModules
     */
    private function registry(
        array $twins,
        array $enabledModules = ['Magento_Webapi', 'Magento_Customer', 'Magento_GraphQl', 'Magento_CustomerGraphQl']
    ): TwinRegistry {
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => in_array($module, $enabledModules, true)
        );

        return new TwinRegistry($moduleManager, $twins);
    }

    private function createAccount(): TwinDefinition
    {
        return new TwinDefinition('customer_create_account', 'REST', ['Magento_Webapi', 'Magento_Customer'], self::SERVICE, 'createAccount', null, '', 10);
    }

    private function createCustomer(): TwinDefinition
    {
        return new TwinDefinition('gql_create_customer', 'GraphQL', ['Magento_GraphQl', 'Magento_CustomerGraphQl'], null, null, 'createCustomer', '', 20);
    }

    public function testFindsServiceTwinWhateverTheClassAndMethodCase(): void
    {
        $twin = $this->createAccount();
        $registry = $this->registry(['customer_create_account' => $twin]);

        $this->assertSame($twin, $registry->findByService('\\' . strtoupper(self::SERVICE), 'CREATEACCOUNT'));
        $this->assertNull($registry->findByService(self::SERVICE, 'getById'));
    }

    public function testFindsMutationTwinByItsExactName(): void
    {
        $twin = $this->createCustomer();
        $registry = $this->registry(['gql_create_customer' => $twin]);

        $this->assertSame($twin, $registry->findByMutation('createCustomer'));
        // GraphQL field names are case-sensitive: another case is another field.
        $this->assertNull($registry->findByMutation('createcustomer'));
    }

    public function testTwinIsHiddenAndNeverAppliedUnlessAllItsModulesAreEnabled(): void
    {
        $registry = $this->registry(
            ['customer_create_account' => $this->createAccount(), 'gql_create_customer' => $this->createCustomer()],
            ['Magento_Webapi', 'Magento_Customer', 'Magento_GraphQl']
        );

        $this->assertSame(['customer_create_account'], array_keys($registry->getAll()));
        $this->assertNull($registry->findByMutation('createCustomer'));
    }

    public function testGetAllIsSortedBySortOrderThenId(): void
    {
        $registry = $this->registry(['gql_create_customer' => $this->createCustomer(), 'customer_create_account' => $this->createAccount()]);

        $this->assertSame(['customer_create_account', 'gql_create_customer'], array_keys($registry->getAll()));
    }

    public function testRejectsInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Turnstile API twin id');

        $this->registry(['Bad-Id' => new TwinDefinition('Bad-Id', 'x', [], null, null, 'contactUs')])->getAll();
    }

    public function testRejectsKeyThatDiffersFromId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is registered under the key "other"');

        $this->registry(['other' => $this->createAccount()])->getAll();
    }

    public function testRejectsItemThatIsNotATwinDefinition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a');

        $this->registry(['x' => new \stdClass()])->getAll();
    }

    public function testRejectsServiceMethodClaimedTwice(): void
    {
        $copy = new TwinDefinition('copy', 'Copy', [], '\\' . self::SERVICE, 'createaccount');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('claimed by API twins "customer_create_account" and "copy"');

        $this->registry(['customer_create_account' => $this->createAccount(), 'copy' => $copy])->findByService(self::SERVICE, 'createAccount');
    }

    public function testRejectsMutationClaimedTwice(): void
    {
        $copy = new TwinDefinition('copy', 'Copy', [], null, null, 'createCustomer');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('claimed by API twins "gql_create_customer" and "copy"');

        $this->registry(['gql_create_customer' => $this->createCustomer(), 'copy' => $copy])->findByMutation('createCustomer');
    }
}
