<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Model;

use AlpineCommerce\Turnstile\Model\Twin\TwinRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The eleven API twins (decision D4 = B) are declared in di.xml, all switched off by default (S26).
 *
 * @magentoAppIsolation enabled
 */
class TwinRegistryPresetsTest extends TestCase
{
    private const ACCOUNT_MANAGEMENT = 'Magento\Customer\Api\AccountManagementInterface';
    private const CUSTOMER_TOKEN = 'Magento\Integration\Api\CustomerTokenServiceInterface';

    /**
     * id => [required modules, service class, service method, mutation]
     */
    private const TWINS = [
        'customer_create_account' => [['Magento_Webapi', 'Magento_Customer'], self::ACCOUNT_MANAGEMENT, 'createAccount', null],
        'customer_password_reset' => [['Magento_Webapi', 'Magento_Customer'], self::ACCOUNT_MANAGEMENT, 'initiatePasswordReset', null],
        'customer_token' => [['Magento_Webapi', 'Magento_Integration'], self::CUSTOMER_TOKEN, 'createCustomerAccessToken', null],
        'gql_create_customer' => [['Magento_GraphQl', 'Magento_CustomerGraphQl'], null, null, 'createCustomer'],
        'gql_create_customer_v2' => [['Magento_GraphQl', 'Magento_CustomerGraphQl'], null, null, 'createCustomerV2'],
        'gql_generate_customer_token' => [['Magento_GraphQl', 'Magento_CustomerGraphQl'], null, null, 'generateCustomerToken'],
        'gql_request_password_reset_email' => [
            ['Magento_GraphQl', 'Magento_CustomerGraphQl'],
            null,
            null,
            'requestPasswordResetEmail',
        ],
        'gql_subscribe_newsletter' => [['Magento_GraphQl', 'Magento_NewsletterGraphQl'], null, null, 'subscribeEmailToNewsletter'],
        'gql_contact_us' => [['Magento_GraphQl', 'Magento_ContactGraphQl'], null, null, 'contactUs'],
        'gql_create_product_review' => [['Magento_GraphQl', 'Magento_ReviewGraphQl'], null, null, 'createProductReview'],
        'gql_send_email_to_friend' => [['Magento_GraphQl', 'Magento_SendFriendGraphQl'], null, null, 'sendEmailToFriend'],
    ];

    public function testElevenTwinsAreRegisteredInDisplayOrder(): void
    {
        $registry = Bootstrap::getObjectManager()->get(TwinRegistry::class);

        $this->assertSame(array_keys(self::TWINS), array_keys($registry->getAll()));
    }

    public function testEachTwinTargetsItsEndpointAndDeclaresItsModules(): void
    {
        $registry = Bootstrap::getObjectManager()->get(TwinRegistry::class);

        foreach (self::TWINS as $id => [$modules, $class, $method, $mutation]) {
            $twin = $mutation === null ? $registry->findByService($class, $method) : $registry->findByMutation($mutation);
            $this->assertNotNull($twin, $id);
            $this->assertSame($id, $twin->getId());
            $this->assertSame($modules, $twin->getRequiredModules(), $id);
            $this->assertNotSame('', $twin->getLabel(), $id);
            $this->assertNotSame('', $twin->getComment(), $id);
        }
    }

    public function testHyvaReviewExceptionIsDocumentedOnItsSwitch(): void
    {
        $twin = Bootstrap::getObjectManager()->get(TwinRegistry::class)->findByMutation('createProductReview');

        $this->assertStringContainsString('Hyvä', (string) $twin?->getComment());
    }

    public function testEveryTwinIsSwitchedOffByDefault(): void
    {
        $scopeConfig = Bootstrap::getObjectManager()->get(ScopeConfigInterface::class);

        foreach (array_keys(self::TWINS) as $id) {
            $this->assertSame('0', (string) $scopeConfig->getValue('alpinecommerce_turnstile/api_twins/' . $id), $id);
        }
    }
}
