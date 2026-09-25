<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\Twin;

use AlpineCommerce\Turnstile\Model\Twin\TwinDefinition;
use PHPUnit\Framework\TestCase;

class TwinDefinitionTest extends TestCase
{
    public function testServiceTwinExposesItsValues(): void
    {
        $twin = new TwinDefinition(
            'customer_create_account',
            'REST/SOAP: create customer account',
            ['Magento_Webapi', 'Magento_Customer'],
            '\Magento\Customer\Api\AccountManagementInterface',
            'createAccount',
            null,
            'Also blocks the SOAP operation.',
            10
        );

        $this->assertSame('customer_create_account', $twin->getId());
        $this->assertSame('REST/SOAP: create customer account', $twin->getLabel());
        $this->assertSame(['Magento_Webapi', 'Magento_Customer'], $twin->getRequiredModules());
        $this->assertSame('Magento\Customer\Api\AccountManagementInterface', $twin->getServiceClass());
        $this->assertSame('createAccount', $twin->getServiceMethod());
        $this->assertNull($twin->getMutation());
        $this->assertSame('Also blocks the SOAP operation.', $twin->getComment());
        $this->assertSame(10, $twin->getSortOrder());
    }

    public function testMutationTwinExposesItsValues(): void
    {
        $twin = new TwinDefinition('gql_contact_us', 'GraphQL: contactUs', ['Magento_GraphQl', 'Magento_ContactGraphQl'], null, null, 'contactUs');

        $this->assertNull($twin->getServiceClass());
        $this->assertNull($twin->getServiceMethod());
        $this->assertSame('contactUs', $twin->getMutation());
        $this->assertSame('', $twin->getComment());
        $this->assertSame(100, $twin->getSortOrder());
    }

    /**
     * @dataProvider invalidTargets
     */
    public function testRejectsAnythingButExactlyOneTarget(?string $class, ?string $method, ?string $mutation): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must target either a service method or a GraphQL mutation');

        new TwinDefinition('twin', 'Twin', ['Magento_Webapi'], $class, $method, $mutation);
    }

    /**
     * @return array<string, array{?string, ?string, ?string}>
     */
    public static function invalidTargets(): array
    {
        return [
            'nothing' => [null, null, null],
            'class without method' => ['Magento\Customer\Api\AccountManagementInterface', null, null],
            'method without class' => [null, 'createAccount', null],
            'both kinds' => ['Magento\Customer\Api\AccountManagementInterface', 'createAccount', 'createCustomer'],
            'empty strings' => ['', '', ''],
        ];
    }
}
