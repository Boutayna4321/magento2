<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Webapi;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Request as WebapiRequest;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Bootstrap as TestBootstrap;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Request as TestFrameworkRequest;
use Magento\Webapi\Controller\Soap\Request\Handler;
use PHPUnit\Framework\TestCase;

/**
 * API twins over SOAP (S27b): the same switch as REST blocks the SOAP operation of the same service for
 * anonymous callers, per store view (S29), never for authenticated callers (S30). The SOAP request
 * handler runs in process, as in Magento's own Webapi SoapTest.
 *
 * @magentoAppArea webapi_soap
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ApiTwinSoapTest extends TestCase
{
    private const SERVICE = 'customerAccountManagementV1';
    private const OPERATION = 'customerAccountManagementV1CreateAccount';
    private const PASSWORD = 'Str0ng!Passw0rd-2026';

    protected function tearDown(): void
    {
        Bootstrap::getObjectManager()->get(ReinitableConfigInterface::class)->reinit();
    }

    private function blockCreateAccount(string $storeCode = 'default'): void
    {
        Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)->setValue(
            'alpinecommerce_turnstile/api_twins/customer_create_account',
            '1',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * In a real SOAP call every request object reads the same URL and headers; in the integration framework
     * the handler, the plugin and the token user context may receive different request objects.
     *
     * @return object[]
     */
    private function requests(): array
    {
        $objectManager = Bootstrap::getObjectManager();

        return [$objectManager->get(WebapiRequest::class), $objectManager->get(TestFrameworkRequest::class)];
    }

    private function createAccount(string $email, ?string $bearer = null): mixed
    {
        foreach ($this->requests() as $request) {
            $request->setParam('services', self::SERVICE);
            if ($bearer !== null) {
                $request->getHeaders()->addHeaderLine('Authorization', 'Bearer ' . $bearer);
            }
        }

        $arguments = (object) [
            'customer' => (object) ['email' => $email, 'firstname' => 'Jane', 'lastname' => 'Doe'],
            'password' => self::PASSWORD,
        ];

        return Bootstrap::getObjectManager()->create(Handler::class)->__call(self::OPERATION, [$arguments]);
    }

    private function customerExists(string $email): bool
    {
        try {
            Bootstrap::getObjectManager()->get(CustomerRepositoryInterface::class)->get($email);
            return true;
        } catch (NoSuchEntityException) {
            return false;
        }
    }

    /**
     * Control for S29 / S30: Turnstile lets the call through. A pre-existing, unrelated defect of this
     * installation makes Magento's SOAP layer fail later, while converting the arguments of
     * customerAccountManagementV1 ("The \"array\" class doesn't exist...", also on the live WSDL). Until it is
     * fixed that exact error proves the call went past the guard; once fixed, the customer must be created.
     * Any other outcome, including the Turnstile refusal, fails the test.
     */
    private function assertLetThroughByTurnstile(string $email, ?string $bearer = null): void
    {
        try {
            $this->createAccount($email, $bearer);
        } catch (WebapiException $exception) {
            $this->fail(sprintf('Refused by the web API layer: %s (HTTP %s)', $exception->getMessage(), $exception->getHttpCode()));
        } catch (\LogicException $exception) {
            $this->assertStringContainsString(
                'class doesn\'t exist and the namespace must be specified',
                $exception->getMessage(),
                'Only the known, pre-existing SOAP defect may stop the call after the guard.'
            );
            return;
        }
        $this->assertTrue($this->customerExists($email));
    }

    public function testAnonymousSoapCallIsRefusedAndNoCustomerIsCreated(): void
    {
        $this->blockCreateAccount();

        try {
            $this->createAccount('soap@example.com');
            $this->fail('The SOAP operation must be refused.');
        } catch (WebapiException $exception) {
            $this->assertSame(403, $exception->getHttpCode());
            $this->assertSame('This operation is not available.', $exception->getMessage());
        }
        $this->assertFalse($this->customerExists('soap@example.com'));
    }

    public function testSoapCallIsLetThroughWhenTheSwitchIsOff(): void
    {
        $this->assertLetThroughByTurnstile('soap-open@example.com');
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_store.php
     */
    public function testSwitchOnAnotherStoreViewDoesNotBlock(): void
    {
        $this->blockCreateAccount('fixture_second_store');

        $this->assertLetThroughByTurnstile('soap-other-store@example.com');
    }

    public function testAuthenticatedSoapCallIsNotBlocked(): void
    {
        $this->blockCreateAccount();
        $token = Bootstrap::getObjectManager()->get(AdminTokenServiceInterface::class)
            ->createAdminAccessToken(TestBootstrap::ADMIN_NAME, TestBootstrap::ADMIN_PASSWORD);
        foreach ([UserContextInterface::class, 'Magento\Authorization\Model\CompositeUserContext',
                     'Magento\Webapi\Model\Authorization\TokenUserContext'] as $type) {
            Bootstrap::getObjectManager()->removeSharedInstance($type);
        }

        $this->assertLetThroughByTurnstile('soap-admin@example.com', $token);

        $this->assertSame(
            UserContextInterface::USER_TYPE_ADMIN,
            (int) Bootstrap::getObjectManager()->get(UserContextInterface::class)->getUserType(),
            'The admin token must be recognised, otherwise this test proves nothing.'
        );
    }
}
