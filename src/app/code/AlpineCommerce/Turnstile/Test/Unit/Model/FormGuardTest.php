<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Model\FormDefinition;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use AlpineCommerce\Turnstile\Model\Guard\TokenReader;
use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormGuardTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private Config&MockObject $config;
    private RemoteAddress&MockObject $remoteAddress;
    private ManagerInterface&MockObject $messageManager;
    private ActionFlag&MockObject $actionFlag;
    private RequestInterface&MockObject $request;
    private HttpInterface&MockObject $response;
    private FormGuard $guard;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->config = $this->createMock(Config::class);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(6);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(HttpInterface::class);

        $this->guard = new FormGuard(
            $this->validator,
            $this->config,
            $storeManager,
            $this->remoteAddress,
            $this->messageManager,
            $this->actionFlag,
            new TokenReader(),
            new MethodPolicy()
        );
    }

    private function token(mixed $value): void
    {
        $this->request->method('getParam')->with(FormGuard::TOKEN_FIELD)->willReturn($value);
    }

    private function expectRejection(string $message): void
    {
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($message);
        $this->actionFlag->expects($this->once())->method('set')->with('', 'no-dispatch', true);
        $this->response->expects($this->once())->method('setRedirect')->with('https://shop.test/contact/index/');
    }

    public function testDisabledFormPassesWithoutValidation(): void
    {
        $this->config->method('isEnabledFor')->with('contact', 6)->willReturn(false);
        $this->validator->expects($this->never())->method('validate');

        $this->assertTrue($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testValidTokenPasses(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(' tok ');
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.5');
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', '203.0.113.5', 'contact', 6)
            ->willReturn(ValidationResult::success());
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertTrue($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testMissingTokenIsRejectedWithCompleteMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(null);
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));
        $this->expectRejection('Please complete the security check.');

        $this->assertFalse($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testArrayTokenIsTreatedAsEmpty(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(['x']);
        $this->validator->expects($this->once())->method('validate')
            ->with('', $this->anything(), 'contact', 6)
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));

        $this->assertFalse($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testMissingRemoteAddressPassesNull(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', null, 'contact', 6)
            ->willReturn(ValidationResult::success());

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    public function testInvalidTokenMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['invalid-input-response']));
        $this->expectRejection('The security check failed. Please try again.');

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    public function testConfigErrorMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_CONFIG, ['invalid-input-secret']));
        $this->expectRejection('We could not verify your request. Please try again later.');

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    public function testUnavailableMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_UNAVAILABLE));
        $this->expectRejection('The security check is temporarily unavailable. Please try again later.');

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    // ----- check(): decision for a registered form (D3, S12) -----

    private function contactForm(): FormDefinition
    {
        return new FormDefinition('contact', 'Contact Us', ['contact_index_post']);
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $query
     */
    private function httpRequest(string $method, array $post = [], string|false $header = false, array $query = []): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getPostValue')->willReturnCallback(
            static fn (?string $key = null, mixed $default = null): mixed =>
                $key === null ? $post : ($post[$key] ?? $default)
        );
        $request->method('getHeader')->willReturnCallback(
            static fn (string $name): string|false => strcasecmp($name, TokenReader::HEADER) === 0 ? $header : false
        );
        $request->method('getParam')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $query[$key] ?? $post[$key] ?? $default
        );

        return $request;
    }

    public function testCheckSkipsDisabledFormWithoutValidation(): void
    {
        $this->config->method('isEnabledFor')->with('contact', 6)->willReturn(false);
        $this->validator->expects($this->never())->method('validate');

        $result = $this->guard->check($this->contactForm(), $this->httpRequest('POST'), $this->createMock(ActionInterface::class));

        $this->assertTrue($result->isValid());
    }

    public function testCheckValidatesPostTokenForTheFormAndStore(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.5');
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', '203.0.113.5', 'contact', 6)
            ->willReturn(ValidationResult::success());

        $result = $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('POST', [FormGuard::TOKEN_FIELD => ' tok ']),
            $this->createMock(ActionInterface::class)
        );

        $this->assertTrue($result->isValid());
    }

    public function testCheckReturnsTheValidatorFailure(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $failure = ValidationResult::failure(ValidationResult::ERROR_USER, ['invalid-input-response']);
        $this->validator->method('validate')->willReturn($failure);

        $result = $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('POST', [FormGuard::TOKEN_FIELD => 'tok']),
            $this->createMock(ActionInterface::class)
        );

        $this->assertSame($failure, $result);
    }

    public function testQueryTokenIsIgnored(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->validator->expects($this->once())->method('validate')
            ->with('', $this->anything(), 'contact', 6)
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));

        $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('POST', [], false, [FormGuard::TOKEN_FIELD => 'query-tok']),
            $this->createMock(ActionInterface::class)
        );
    }

    public function testHeaderTokenIsAccepted(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->validator->expects($this->once())->method('validate')
            ->with('head-tok', $this->anything(), 'contact', 6)
            ->willReturn(ValidationResult::success());

        $result = $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('POST', [], 'head-tok'),
            $this->createMock(ActionInterface::class)
        );

        $this->assertTrue($result->isValid());
    }

    public function testCheckRejectsGetOnActionWithoutMethodInterfaceWithoutCallingCloudflare(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->validator->expects($this->never())->method('validate');

        $result = $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('GET', [], 'head-tok'),
            $this->createMock(ActionInterface::class)
        );

        $this->assertFalse($result->isValid());
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
        $this->assertSame([MethodPolicy::ERROR_CODE], $result->getErrorCodes());
    }

    public function testCheckLetsDeclaredGetPageThroughWithoutValidation(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->validator->expects($this->never())->method('validate');

        $result = $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('GET'),
            $this->createMock(HttpGetActionInterface::class)
        );

        $this->assertTrue($result->isValid());
    }

    public function testCheckPassesNullWhenRemoteAddressIsUnknown(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', null, 'contact', 6)
            ->willReturn(ValidationResult::success());

        $this->guard->check(
            $this->contactForm(),
            $this->httpRequest('POST', [FormGuard::TOKEN_FIELD => 'tok']),
            $this->createMock(ActionInterface::class)
        );
    }
}
