<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataFilter;
use AlpineCommerce\Turnstile\Model\FormDataPersister\SessionFormDataPersister;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AlpineCommerce\Turnstile\Test\Unit\Stub\RecordingLogger;
use AlpineCommerce\Turnstile\Test\Unit\Stub\RecordingSession;

class SessionFormDataPersisterTest extends TestCase
{
    private const POST = [
        'login' => ['username' => 'jane@example.com', 'password' => 'Secret123!'],
        'firstname' => 'Jane',
        'password' => 'Secret123!',
        'password_confirmation' => 'Secret123!',
        'form_key' => 'abcd1234',
        'cf-turnstile-response' => 'token',
    ];

    private ModuleManager&MockObject $moduleManager;
    private ObjectManagerInterface&MockObject $objectManager;
    private ObjectManagerConfig&MockObject $objectManagerConfig;
    private State&MockObject $appState;
    private RecordingLogger $logger;
    private RecordingSession $session;

    protected function setUp(): void
    {
        $this->moduleManager = $this->createMock(ModuleManager::class);
        $this->moduleManager->method('isEnabled')->willReturn(true);
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->objectManagerConfig = $this->createMock(ObjectManagerConfig::class);
        $this->appState = $this->createMock(State::class);
        $this->appState->method('getMode')->willReturn(State::MODE_PRODUCTION);
        $this->logger = new RecordingLogger();
        $this->session = new RecordingSession();
    }

    private function persister(string $sessionType, string $setter, ?string $valueField = null): SessionFormDataPersister
    {
        return new SessionFormDataPersister(
            $this->moduleManager,
            $this->objectManager,
            $this->objectManagerConfig,
            new FormDataFilter(),
            $this->appState,
            $this->logger,
            'Magento_Customer',
            $sessionType,
            $setter,
            $valueField
        );
    }

    private function request(array $post = self::POST): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->willReturn($post);

        return $request;
    }

    private function sessionResolvesTo(string $type): void
    {
        $this->objectManagerConfig->method('getInstanceType')->with($type)->willReturn(get_class($this->session));
        $this->objectManager->method('get')->with($type)->willReturn($this->session);
    }

    public function testWholeFilteredFormIsPassedToTheSetterWithoutPasswords(): void
    {
        $this->sessionResolvesTo('Magento\Customer\Model\Session');

        $this->persister('Magento\Customer\Model\Session', 'setCustomerFormData')->persist($this->request());

        $this->assertSame(
            [['setCustomerFormData', [['login' => ['username' => 'jane@example.com'], 'firstname' => 'Jane']]]],
            $this->session->calls
        );
    }

    public function testSingleFieldIsPassedToTheSetter(): void
    {
        $this->sessionResolvesTo('Magento\Customer\Model\Session');

        $this->persister('Magento\Customer\Model\Session', 'setUsername', 'login/username')->persist($this->request());

        $this->assertSame([['setUsername', ['jane@example.com']]], $this->session->calls);
    }

    public function testMissingSingleFieldKeepsNothing(): void
    {
        $this->sessionResolvesTo('Magento\Customer\Model\Session');

        $this->persister('Magento\Customer\Model\Session', 'setForgottenEmail', 'email')->persist($this->request());

        $this->assertSame([], $this->session->calls);
    }

    public function testPasswordFieldCanNeverBeKeptAsSingleValue(): void
    {
        $this->sessionResolvesTo('Magento\Customer\Model\Session');

        $this->persister('Magento\Customer\Model\Session', 'setUsername', 'login/password')->persist($this->request());

        $this->assertSame([], $this->session->calls);
    }

    public function testVirtualSessionTypeIsFetchedByItsOwnName(): void
    {
        // Magento\Wishlist\Model\Session is a virtualType of Generic: the shared instance is keyed by the virtual name.
        $this->sessionResolvesTo('Magento\Wishlist\Model\Session');

        $this->persister('Magento\Wishlist\Model\Session', 'setSharingForm')->persist($this->request(['emails' => 'a@example.com']));

        $this->assertSame([['setSharingForm', [['emails' => 'a@example.com']]]], $this->session->calls);
    }

    public function testMissingModuleSkipsPersistenceWithoutError(): void
    {
        $this->moduleManager = $this->createMock(ModuleManager::class);
        $this->moduleManager->method('isEnabled')->with('Magento_Customer')->willReturn(false);
        $this->objectManager->expects($this->never())->method('get');
        $this->objectManagerConfig->expects($this->never())->method('getInstanceType');

        $this->persister('Magento\Customer\Model\Session', 'setCustomerFormData')->persist($this->request());

        $this->assertSame([], $this->logger->records);
    }

    /**
     * A request that passes Turnstile never calls persist(); building the persister (done by DI for every
     * request) must therefore create no session and touch no module class.
     */
    public function testNothingPersistedWhenRequestPasses(): void
    {
        $this->objectManager->expects($this->never())->method('get');
        $this->objectManagerConfig->expects($this->never())->method('getInstanceType');
        $this->moduleManager = $this->createMock(ModuleManager::class);
        $this->moduleManager->expects($this->never())->method('isEnabled');

        $this->persister('Magento\Customer\Model\Session', 'setCustomerFormData');

        $this->assertSame([], $this->session->calls);
    }

    public function testUnknownSessionTypeIsReported(): void
    {
        $this->objectManagerConfig->method('getInstanceType')->willReturn('Magento\Missing\Model\Session');
        $this->objectManager->expects($this->never())->method('get');

        $this->persister('Magento\Missing\Model\Session', 'setFormData')->persist($this->request());

        $this->assertReportedWithoutFormData('unknown session type');
    }

    public function testNonSessionTypeIsReported(): void
    {
        $this->objectManagerConfig->method('getInstanceType')->willReturn(\stdClass::class);
        $this->objectManager->expects($this->never())->method('get');

        $this->persister(\stdClass::class, 'setFormData')->persist($this->request());

        $this->assertReportedWithoutFormData('type is not a session');
    }

    /**
     * @dataProvider malformedSetters
     */
    public function testMalformedSetterIsReported(string $setter): void
    {
        $this->objectManager->expects($this->never())->method('get');

        $this->persister('Magento\Customer\Model\Session', $setter)->persist($this->request());

        $this->assertReportedWithoutFormData('malformed setter');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedSetters(): array
    {
        return [
            'getter' => ['getCustomerFormData'],
            'lowercase' => ['setcustomerFormData'],
            'magic method' => ['__destruct'],
            'space' => ['set Customer'],
            'empty' => [''],
        ];
    }

    public function testMisconfigurationThrowsInDeveloperMode(): void
    {
        $this->appState = $this->createMock(State::class);
        $this->appState->method('getMode')->willReturn(State::MODE_DEVELOPER);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('malformed setter');

        $this->persister('Magento\Customer\Model\Session', 'getCustomerFormData')->persist($this->request());
    }

    private function assertReportedWithoutFormData(string $problem): void
    {
        $this->assertSame([], $this->session->calls);
        $this->assertCount(1, $this->logger->records);
        $this->assertSame('error', $this->logger->records[0]['level']);
        $this->assertStringContainsString($problem, $this->logger->records[0]['message']);
        $dump = json_encode($this->logger->records);
        foreach (['jane@example.com', 'Secret123!', 'Jane', 'abcd1234', 'token'] as $value) {
            $this->assertStringNotContainsString($value, (string) $dump);
        }
    }
}
