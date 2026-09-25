<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Webapi;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Webapi\Request as WebapiRequest;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Bootstrap as TestBootstrap;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Request as TestFrameworkRequest;
use Magento\Webapi\Controller\Rest;
use PHPUnit\Framework\TestCase;

/**
 * API twins over REST (decision D4 = B): synchronous (S27), asynchronous and bulk (S27c), per store
 * view (S29) and anonymous callers only (S30). The REST controller runs in process, as in Magento's own
 * Webapi integration tests, with the shared REST request read by the router, the validator and the
 * user context.
 *
 * @magentoAppArea webapi_rest
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ApiTwinRestTest extends TestCase
{
    private const PASSWORD = 'Str0ng!Passw0rd-2026';

    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->request = $objectManager->get(Request::class);
        $this->response = $objectManager->create(Response::class);
    }

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
     * @param array<mixed> $body
     */
    private function post(string $path, array $body, ?string $bearer = null, string $storeCode = 'default'): void
    {
        $this->request->setMethod('POST');
        // As in a real call: /rest/<store code>/<endpoint>; PathProcessor drops "rest" and reads the store code.
        $this->request->setPathInfo('/rest/' . $storeCode . $path);
        $this->request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        if ($bearer !== null) {
            // In a real HTTP call every request object reads the same headers. In the integration framework the
            // REST controller reads Magento\Framework\Webapi\Rest\Request while Magento's token user context
            // receives the test framework request: the header goes on each of them.
            foreach ([$this->request, Bootstrap::getObjectManager()->get(WebapiRequest::class),
                         Bootstrap::getObjectManager()->get(TestFrameworkRequest::class)] as $request) {
                $request->getHeaders()->addHeaderLine('Authorization', 'Bearer ' . $bearer);
            }
            // Magento's user contexts resolve the caller once and keep it: rebuild them for this request.
            foreach ([
                UserContextInterface::class,
                'Magento\Authorization\Model\CompositeUserContext',
                'Magento\Webapi\Model\Authorization\TokenUserContext',
            ] as $type) {
                Bootstrap::getObjectManager()->removeSharedInstance($type);
            }
        }
        $this->request->setContent((string) json_encode($body));

        Bootstrap::getObjectManager()
            ->create(Rest::class, ['request' => $this->request, 'response' => $this->response])
            ->dispatch($this->request);
    }

    /**
     * @return array<string, mixed>
     */
    private function customer(string $email): array
    {
        return ['customer' => ['email' => $email, 'firstname' => 'Jane', 'lastname' => 'Doe'], 'password' => self::PASSWORD];
    }

    private function assertRefusedWith403(): void
    {
        $this->assertTrue($this->response->isException(), 'The call must be refused.');
        $this->assertSame(403, $this->response->getException()[0]->getHttpCode(), $this->exceptionMessage());
    }

    private function exceptionMessage(): string
    {
        if (!$this->response->isException()) {
            return 'no exception';
        }
        $exception = $this->response->getException()[0];

        return sprintf('%s (HTTP %s)', $exception->getMessage(), method_exists($exception, 'getHttpCode') ? $exception->getHttpCode() : '?');
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

    private function queuedOperations(): int
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);

        return (int) $resource->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM ' . $resource->getTableName('magento_operation')
        );
    }

    /**
     * S27c, run first: the asynchronous endpoint is refused at request time and nothing is queued.
     */
    public function testAsynchronousAnonymousCallIsRefusedAndNothingIsQueued(): void
    {
        $this->blockCreateAccount();
        $before = $this->queuedOperations();

        $this->post('/async/V1/customers', $this->customer('async@example.com'));

        $this->assertRefusedWith403();
        $this->assertSame($before, $this->queuedOperations());
    }

    /**
     * S27c: the bulk endpoint (several customers in one call) is refused too.
     */
    public function testBulkAnonymousCallIsRefusedAndNothingIsQueued(): void
    {
        $this->blockCreateAccount();
        $before = $this->queuedOperations();

        $this->post('/async/bulk/V1/customers', [$this->customer('bulk1@example.com'), $this->customer('bulk2@example.com')]);

        $this->assertRefusedWith403();
        $this->assertSame($before, $this->queuedOperations());
    }

    /**
     * Control for S27c: with the switch off the asynchronous call is accepted and queued.
     */
    public function testAsynchronousCallIsQueuedWhenTheSwitchIsOff(): void
    {
        $before = $this->queuedOperations();

        $this->post('/async/V1/customers', $this->customer('async-open@example.com'));

        $this->assertFalse($this->response->isException(), $this->exceptionMessage());
        $this->assertSame($before + 1, $this->queuedOperations());
    }

    public function testSynchronousAnonymousCallIsRefusedAndNoCustomerIsCreated(): void
    {
        $this->blockCreateAccount();

        $this->post('/V1/customers', $this->customer('sync@example.com'));

        $this->assertRefusedWith403();
        $this->assertFalse($this->customerExists('sync@example.com'));
    }

    public function testSynchronousCallCreatesTheCustomerWhenTheSwitchIsOff(): void
    {
        $this->post('/V1/customers', $this->customer('sync-open@example.com'));

        $this->assertFalse($this->response->isException(), $this->exceptionMessage());
        $this->assertTrue($this->customerExists('sync-open@example.com'));
    }

    /**
     * S29: a switch on for another store view does not block this one.
     *
     * @magentoDataFixture Magento/Store/_files/second_store.php
     */
    public function testSwitchOnAnotherStoreViewDoesNotBlock(): void
    {
        $this->blockCreateAccount('fixture_second_store');

        $this->post('/V1/customers', $this->customer('other-store@example.com'));

        $this->assertFalse($this->response->isException(), $this->exceptionMessage());
        $this->assertTrue($this->customerExists('other-store@example.com'));
    }

    /**
     * S30: an authenticated caller (admin token) is never blocked.
     */
    public function testAuthenticatedCallIsNotBlocked(): void
    {
        $this->blockCreateAccount();
        $token = Bootstrap::getObjectManager()->get(AdminTokenServiceInterface::class)
            ->createAdminAccessToken(TestBootstrap::ADMIN_NAME, TestBootstrap::ADMIN_PASSWORD);

        $this->post('/V1/customers', $this->customer('admin-made@example.com'), $token);

        $this->assertSame(
            UserContextInterface::USER_TYPE_ADMIN,
            (int) Bootstrap::getObjectManager()->get(UserContextInterface::class)->getUserType(),
            'The admin token must be recognised, otherwise this test proves nothing.'
        );
        $this->assertFalse($this->response->isException(), $this->exceptionMessage());
        $this->assertTrue($this->customerExists('admin-made@example.com'));
    }
}
