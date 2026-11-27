<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Plugin\Webapi;

use AlpineCommerce\Turnstile\Model\Twin\TwinBlocker;
use AlpineCommerce\Turnstile\Model\Twin\TwinDefinition;
use AlpineCommerce\Turnstile\Model\Twin\TwinRegistry;
use AlpineCommerce\Turnstile\Plugin\Webapi\SoapTwinGuard;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Request;
use Magento\Webapi\Controller\Soap\Request\Handler;
use Magento\Webapi\Model\Soap\Config as SoapConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SoapTwinGuardTest extends TestCase
{
    private const OPERATION = 'customerAccountManagementV1CreateAccount';

    private Request&MockObject $request;
    private SoapConfig&MockObject $soapConfig;
    private UserContextInterface&MockObject $userContext;
    private TwinBlocker&MockObject $blocker;
    private TwinRegistry $registry;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->request->method('getRequestedServices')->willReturn(['customerAccountManagementV1']);
        $this->soapConfig = $this->createMock(SoapConfig::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->userContext->method('getUserType')->willReturn(null);
        $this->blocker = $this->createMock(TwinBlocker::class);
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn(true);
        $this->registry = new TwinRegistry($moduleManager, [
            'customer_create_account' => new TwinDefinition(
                'customer_create_account',
                'REST/SOAP',
                [],
                'Magento\Customer\Api\AccountManagementInterface',
                'createAccount'
            ),
        ]);
    }

    private function guard(array $collaborators = null): SoapTwinGuard
    {
        return new SoapTwinGuard(
            $this->registry,
            $this->blocker,
            $this->request,
            $collaborators ?? ['soapConfig' => $this->soapConfig, 'userContext' => $this->userContext]
        );
    }

    private function operationIs(string $class, string $method): void
    {
        $this->soapConfig->method('getServiceMethodInfo')
            ->with(self::OPERATION, ['customerAccountManagementV1'])
            ->willReturn(['class' => $class, 'method' => $method, 'isSecure' => false, 'resources' => ['anonymous']]);
    }

    public function testBlockedTwinIsRefusedWithHttp403(): void
    {
        $this->operationIs('Magento\Customer\Api\AccountManagementInterface', 'createAccount');
        $this->blocker->expects($this->once())->method('mustBlock')
            ->with($this->callback(static fn ($twin): bool => $twin->getId() === 'customer_create_account'), null, 'soap')
            ->willReturn(true);

        try {
            $this->guard()->before__call($this->createMock(Handler::class), self::OPERATION, [[]]);
            $this->fail('The twin must be refused.');
        } catch (WebapiException $exception) {
            $this->assertSame(403, $exception->getHttpCode());
        }
    }

    public function testTwinLetThroughByTheBlockerIsNotRefused(): void
    {
        $this->operationIs('Magento\Customer\Api\AccountManagementInterface', 'createAccount');
        $this->blocker->method('mustBlock')->willReturn(false);

        $this->assertNull($this->guard()->before__call($this->createMock(Handler::class), self::OPERATION, [[]]));
    }

    public function testOperationThatIsNotATwinIsIgnored(): void
    {
        $this->operationIs('Magento\Catalog\Api\ProductRepositoryInterface', 'get');
        $this->blocker->expects($this->never())->method('mustBlock');

        $this->guard()->before__call($this->createMock(Handler::class), 'catalogProductRepositoryV1Get', [[]]);
    }

    public function testUnknownOperationIsLeftToMagento(): void
    {
        $this->soapConfig->method('getServiceMethodInfo')
            ->willThrowException(new WebapiException(__('Operation "%1" not found.', 'x'), 0, 404));
        $this->blocker->expects($this->never())->method('mustBlock');

        $this->guard()->before__call($this->createMock(Handler::class), 'unknownOperation', [[]]);
    }

    /**
     * @dataProvider incompleteCollaborators
     */
    public function testMissingOrWrongAreaConfigurationIsAnExplicitError(array $collaborators): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('etc/webapi_soap/di.xml');

        $this->guard($collaborators)->before__call($this->createMock(Handler::class), self::OPERATION, [[]]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function incompleteCollaborators(): array
    {
        return [
            'none' => [[]],
            'unresolved configuration' => [['soapConfig' => ['instance' => 'X'], 'userContext' => ['instance' => 'Y']]],
            'wrong objects' => [['soapConfig' => new \stdClass(), 'userContext' => new \stdClass()]],
        ];
    }
}
