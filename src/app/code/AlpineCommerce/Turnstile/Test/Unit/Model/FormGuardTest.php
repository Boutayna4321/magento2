<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use Magento\Framework\App\ActionFlag;
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
            $this->actionFlag
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
}
