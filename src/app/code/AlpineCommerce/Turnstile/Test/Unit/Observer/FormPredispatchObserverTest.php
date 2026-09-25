<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Observer;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\FailureResponderInterface;
use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataPersisterInterface;
use AlpineCommerce\Turnstile\Model\FormDefinition;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Model\FormRegistry;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use AlpineCommerce\Turnstile\Model\Guard\TokenReader;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use AlpineCommerce\Turnstile\Observer\FormPredispatchObserver;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The observer is tested with the real registry, guard, method policy and token reader;
 * only the validator (Cloudflare), the configuration and the failure responder are doubles.
 */
class FormPredispatchObserverTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private FailureResponderInterface&MockObject $responder;
    private FormDataPersisterInterface&MockObject $persister;
    private FormPredispatchObserver $observer;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->responder = $this->createMock(FailureResponderInterface::class);
        $this->persister = $this->createMock(FormDataPersisterInterface::class);

        $config = $this->createMock(Config::class);
        $config->method('isEnabledFor')->willReturn(true);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(6);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn(true);

        $contact = new FormDefinition(
            'contact',
            'Contact Us',
            ['contact_index_post'],
            'Magento_Contact',
            'auto',
            'contact/index',
            [],
            $this->persister
        );
        $guard = new FormGuard(
            $this->validator,
            $config,
            $storeManager,
            $this->createMock(RemoteAddress::class),
            $this->createMock(ManagerInterface::class),
            $this->createMock(ActionFlag::class),
            new TokenReader(),
            new MethodPolicy()
        );

        $this->observer = new FormPredispatchObserver(
            new FormRegistry($moduleManager, ['contact' => $contact]),
            $guard,
            $this->responder
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function request(string $fullActionName, string $method = 'POST', array $post = []): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getFullActionName')->willReturn($fullActionName);
        $request->method('getMethod')->willReturn($method);
        $request->method('getPostValue')->willReturnCallback(
            static fn (?string $key = null, mixed $default = null): mixed =>
                $key === null ? $post : ($post[$key] ?? $default)
        );
        $request->method('getParam')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $post[$key] ?? $default
        );
        $request->method('getHeader')->willReturn(false);

        return $request;
    }

    private function dispatch(mixed $request, mixed $action): void
    {
        $event = new Event(['request' => $request, 'controller_action' => $action]);
        $this->observer->execute(new Observer(['event' => $event]));
    }

    private function postAction(): ActionInterface
    {
        return $this->createMock(HttpPostActionInterface::class);
    }

    private function expectRejection(string $errorCode): void
    {
        $this->responder->expects($this->once())->method('respond')
            ->with(
                $this->callback(static fn ($form): bool => $form->getId() === 'contact'),
                $this->anything(),
                $this->callback(
                    static fn (ValidationResult $result): bool =>
                        !$result->isValid() && $result->getErrorCodes() === [$errorCode]
                )
            )
            ->willReturn(FailureResponderInterface::MODE_REDIRECT);
    }

    public function testRequestToUnprotectedRouteIsIgnored(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->responder->expects($this->never())->method('respond');

        $this->dispatch($this->request('catalogsearch_result_index', 'GET'), $this->postAction());
    }

    public function testClientFormIdParamIsIgnored(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->responder->expects($this->never())->method('respond');

        // An unprotected route stays unprotected whatever the client claims in its parameters.
        $this->dispatch(
            $this->request('newsletter_manage_save', 'POST', [
                'form_id' => 'contact',
                'action' => 'contact_index_post',
                'full_action_name' => 'contact_index_post',
            ]),
            $this->postAction()
        );
    }

    public function testProtectedRouteIsValidatedAsItsOwnFormWhateverTheClientClaims(): void
    {
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', null, 'contact', 6)
            ->willReturn(ValidationResult::success());

        $this->dispatch(
            $this->request('contact_index_post', 'POST', [TokenReader::FIELD => 'tok', 'form_id' => 'customer_login']),
            $this->postAction()
        );
    }

    public function testRouteMatchIsCaseInsensitive(): void
    {
        $this->validator->expects($this->once())->method('validate')
            ->with('', null, 'contact', 6)
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));
        $this->expectRejection('missing-input-response');

        $this->dispatch($this->request('Contact_INDEX_Post'), $this->postAction());
    }

    public function testGetOnRouteWithoutMethodInterfaceIsRejected(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->expectRejection(MethodPolicy::ERROR_CODE);

        $this->dispatch($this->request('contact_index_post', 'GET'), $this->createMock(ActionInterface::class));
    }

    public function testPutOnRouteWithoutMethodInterfaceIsRejected(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->expectRejection(MethodPolicy::ERROR_CODE);

        $this->dispatch($this->request('contact_index_post', 'PUT'), $this->createMock(ActionInterface::class));
    }

    public function testGetOnRouteWithExplicitGetInterfaceIsNotValidated(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->responder->expects($this->never())->method('respond');

        $this->dispatch($this->request('contact_index_post', 'GET'), $this->createMock(HttpGetActionInterface::class));
    }

    public function testPostStillValidatedWhenActionAlsoDeclaresGet(): void
    {
        $action = new class implements HttpGetActionInterface, HttpPostActionInterface {
            public function execute(): \Magento\Framework\Controller\ResultInterface
            {
                throw new \LogicException('The observer never executes the action.');
            }
        };
        $this->validator->expects($this->once())->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['invalid-input-response']));
        $this->expectRejection('invalid-input-response');

        $this->dispatch($this->request('contact_index_post', 'POST', [TokenReader::FIELD => 'bad']), $action);
    }

    public function testValidTokenReachesTheControllerWithoutResponseOrPersistence(): void
    {
        $this->validator->method('validate')->willReturn(ValidationResult::success());
        $this->responder->expects($this->never())->method('respond');
        $this->persister->expects($this->never())->method('persist');

        $this->dispatch($this->request('contact_index_post', 'POST', [TokenReader::FIELD => 'tok']), $this->postAction());
    }

    public function testRedirectFailureKeepsTheInputThroughThePersister(): void
    {
        $request = $this->request('contact_index_post', 'POST', ['name' => 'Jane']);
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));
        $this->responder->method('respond')->willReturn(FailureResponderInterface::MODE_REDIRECT);
        $this->persister->expects($this->once())->method('persist')->with($request);

        $this->dispatch($request, $this->postAction());
    }

    public function testJsonFailureDoesNotKeepTheInput(): void
    {
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));
        $this->responder->method('respond')->willReturn(FailureResponderInterface::MODE_JSON);
        $this->persister->expects($this->never())->method('persist');

        $this->dispatch($this->request('contact_index_post'), $this->postAction());
    }

    public function testNonHttpRequestIsIgnored(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->responder->expects($this->never())->method('respond');

        $this->dispatch($this->createMock(RequestInterface::class), $this->postAction());
    }

    public function testProtectedRouteWithoutControllerActionFailsClosed(): void
    {
        $this->validator->expects($this->never())->method('validate');
        $this->expectRejection(MethodPolicy::ERROR_CODE);

        $this->dispatch($this->request('contact_index_post'), null);
    }
}
