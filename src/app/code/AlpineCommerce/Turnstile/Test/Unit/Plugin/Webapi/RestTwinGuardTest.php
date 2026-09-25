<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Plugin\Webapi;

use AlpineCommerce\Turnstile\Model\Twin\TwinBlocker;
use AlpineCommerce\Turnstile\Model\Twin\TwinDefinition;
use AlpineCommerce\Turnstile\Model\Twin\TwinRegistry;
use AlpineCommerce\Turnstile\Plugin\Webapi\RestTwinGuard;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest\RequestValidator;
use Magento\Webapi\Controller\Rest\Router;
use Magento\Webapi\Controller\Rest\Router\Route;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestTwinGuardTest extends TestCase
{
    private Request&MockObject $request;
    private Router&MockObject $router;
    private UserContextInterface&MockObject $userContext;
    private TwinBlocker&MockObject $blocker;
    private TwinRegistry $registry;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->router = $this->createMock(Router::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->userContext->method('getUserType')->willReturn(null);
        $this->blocker = $this->createMock(TwinBlocker::class);
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn(true);
        $this->registry = new TwinRegistry($moduleManager, [
            'customer_create_account' => new TwinDefinition(
                'customer_create_account',
                'REST',
                [],
                'Magento\Customer\Api\AccountManagementInterface',
                'createAccount'
            ),
        ]);
    }

    private function guard(): RestTwinGuard
    {
        return new RestTwinGuard(
            $this->registry,
            $this->blocker,
            $this->request,
            ['router' => $this->router, 'userContext' => $this->userContext]
        );
    }

    private function routeTo(string $class, string $method): void
    {
        $route = $this->createMock(Route::class);
        $route->method('getServiceClass')->willReturn($class);
        $route->method('getServiceMethod')->willReturn($method);
        $this->router->method('match')->with($this->request)->willReturn($route);
    }

    public function testBlockedTwinIsRefusedWithHttp403(): void
    {
        $this->routeTo('Magento\Customer\Api\AccountManagementInterface', 'createAccount');
        $this->blocker->expects($this->once())->method('mustBlock')
            ->with($this->callback(static fn ($twin): bool => $twin->getId() === 'customer_create_account'), null, 'rest')
            ->willReturn(true);

        try {
            $this->guard()->beforeValidate($this->createMock(RequestValidator::class));
            $this->fail('The twin must be refused.');
        } catch (WebapiException $exception) {
            $this->assertSame(403, $exception->getHttpCode());
        }
    }

    public function testTwinLetThroughByTheBlockerIsNotRefused(): void
    {
        $this->routeTo('Magento\Customer\Api\AccountManagementInterface', 'createAccount');
        $this->blocker->method('mustBlock')->willReturn(false);

        $this->guard()->beforeValidate($this->createMock(RequestValidator::class));
        $this->addToAssertionCount(1);
    }

    public function testRouteThatIsNotATwinIsIgnored(): void
    {
        $this->routeTo('Magento\Catalog\Api\ProductRepositoryInterface', 'get');
        $this->blocker->expects($this->never())->method('mustBlock');

        $this->guard()->beforeValidate($this->createMock(RequestValidator::class));
    }

    public function testUnknownRouteIsLeftToMagento(): void
    {
        $this->router->method('match')->willThrowException(new WebapiException(__('Request does not match any route.'), 0, 404));
        $this->blocker->expects($this->never())->method('mustBlock');

        $this->guard()->beforeValidate($this->createMock(RequestValidator::class));
    }

    /**
     * @dataProvider incompleteCollaborators
     */
    public function testMissingOrWrongAreaConfigurationIsAnExplicitError(array $collaborators): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('etc/webapi_rest/di.xml');

        (new RestTwinGuard($this->registry, $this->blocker, $this->request, $collaborators))
            ->beforeValidate($this->createMock(RequestValidator::class));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function incompleteCollaborators(): array
    {
        return [
            'none' => [[]],
            'unresolved configuration' => [['router' => ['instance' => 'Router'], 'userContext' => ['instance' => 'X']]],
            'wrong objects' => [['router' => new \stdClass(), 'userContext' => new \stdClass()]],
        ];
    }
}
