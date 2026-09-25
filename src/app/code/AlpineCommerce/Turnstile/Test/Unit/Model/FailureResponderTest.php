<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Model\FailureResponder;
use AlpineCommerce\Turnstile\Model\FailureResponderInterface;
use AlpineCommerce\Turnstile\Model\FormDefinition;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FailureResponderTest extends TestCase
{
    private ActionFlag&MockObject $actionFlag;
    private ManagerInterface&MockObject $messageManager;
    private UrlInterface&MockObject $url;
    private RedirectInterface&MockObject $redirect;

    protected function setUp(): void
    {
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->url = $this->createMock(UrlInterface::class);
        $this->redirect = $this->createMock(RedirectInterface::class);
    }

    private function responder(ResponseInterface $response): FailureResponder
    {
        return new FailureResponder(
            $this->actionFlag,
            $response,
            $this->messageManager,
            $this->url,
            $this->redirect,
            new Json()
        );
    }

    private function form(
        string $failureResponse = FormDefinitionInterface::FAILURE_RESPONSE_AUTO,
        ?string $redirectPath = 'contact/index',
        array $redirectParams = []
    ): FormDefinition {
        return new FormDefinition('contact', 'Contact Us', ['contact_index_post'], null, $failureResponse, $redirectPath, $redirectParams);
    }

    private function request(bool $ajax = false, string|false $accept = false): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('isXmlHttpRequest')->willReturn($ajax);
        $request->method('getHeader')->willReturnCallback(
            static fn (string $name): string|false => strcasecmp($name, 'Accept') === 0 ? $accept : false
        );

        return $request;
    }

    private function missingToken(): ValidationResult
    {
        return ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']);
    }

    private function expectDispatchBlocked(): void
    {
        $this->actionFlag->expects($this->once())->method('set')
            ->with('', ActionInterface::FLAG_NO_DISPATCH, true);
    }

    // ----- S13: the controller never runs -----

    public function testNonHttpResponseStillBlocksDispatch(): void
    {
        $this->expectDispatchBlocked();
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $mode = $this->responder($this->createMock(ResponseInterface::class))
            ->respond($this->form(), $this->request(), $this->missingToken());

        $this->assertSame(FailureResponderInterface::MODE_NONE, $mode);
    }

    // ----- classic form: redirect + message -----

    public function testClassicPostRedirectsToTheFormPathWithTheMessage(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $this->expectDispatchBlocked();
        $this->url->expects($this->once())->method('getUrl')->with('contact/index', [])->willReturn('https://shop.test/contact/index/');
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with('Please complete the security check.');
        $response->expects($this->once())->method('setRedirect')->with('https://shop.test/contact/index/');
        $response->expects($this->never())->method('setHttpResponseCode');

        $mode = $this->responder($response)->respond($this->form(), $this->request(), $this->missingToken());

        $this->assertSame(FailureResponderInterface::MODE_REDIRECT, $mode);
    }

    public function testRedirectParamsArePassedToTheUrlBuilder(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $this->url->expects($this->once())->method('getUrl')
            ->with('customer/account/create', ['_secure' => true])
            ->willReturn('https://shop.test/customer/account/create/');

        $this->responder($response)->respond(
            $this->form('auto', 'customer/account/create', ['_secure' => true]),
            $this->request(),
            $this->missingToken()
        );
    }

    public function testPreviousPageGoesThroughMagentoRefererCheck(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $this->url->expects($this->never())->method('getUrl');
        // Magento's RedirectInterface returns the store base URL for an external or missing referer.
        $this->redirect->expects($this->once())->method('getRefererUrl')->willReturn('https://shop.test/');
        $response->expects($this->once())->method('setRedirect')->with('https://shop.test/');

        $this->responder($response)->respond($this->form('auto', null), $this->request(), $this->missingToken());
    }

    /**
     * @dataProvider messages
     */
    public function testRedirectMessageMatchesTheFailure(ValidationResult $result, string $message): void
    {
        $this->url->method('getUrl')->willReturn('https://shop.test/contact/index/');
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($message);

        $this->responder($this->createMock(HttpInterface::class))->respond($this->form(), $this->request(), $result);
    }

    /**
     * @return array<string, array{ValidationResult, string}>
     */
    public static function messages(): array
    {
        return [
            'missing token' => [
                ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']),
                'Please complete the security check.',
            ],
            'invalid token' => [
                ValidationResult::failure(ValidationResult::ERROR_USER, ['invalid-input-response']),
                'The security check failed. Please try again.',
            ],
            'method not allowed' => [
                ValidationResult::failure(ValidationResult::ERROR_USER, [MethodPolicy::ERROR_CODE]),
                'The security check failed. Please try again.',
            ],
            'configuration error' => [
                ValidationResult::failure(ValidationResult::ERROR_CONFIG, ['invalid-input-secret']),
                'We could not verify your request. Please try again later.',
            ],
            'unavailable' => [
                ValidationResult::failure(ValidationResult::ERROR_UNAVAILABLE),
                'The security check is temporarily unavailable. Please try again later.',
            ],
        ];
    }

    // ----- AJAX / fetch: HTTP 400 JSON -----

    public function testAjaxRequestGetsJson400(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $this->expectDispatchBlocked();
        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $response->expects($this->never())->method('setRedirect');
        $response->expects($this->once())->method('setHttpResponseCode')->with(400);
        $response->expects($this->once())->method('setHeader')->with('Content-Type', 'application/json', true);
        $response->expects($this->once())->method('setBody')->with(json_encode([
            'success' => false,
            'error' => 'turnstile',
            'code' => 'missing',
            'message' => 'Please complete the security check.',
        ]));

        $mode = $this->responder($response)->respond($this->form(), $this->request(true), $this->missingToken());

        $this->assertSame(FailureResponderInterface::MODE_JSON, $mode);
    }

    public function testAcceptJsonHeaderGetsJson400(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $response->expects($this->once())->method('setHttpResponseCode')->with(400);

        $mode = $this->responder($response)->respond(
            $this->form(),
            $this->request(false, 'application/json, text/plain, */*'),
            $this->missingToken()
        );

        $this->assertSame(FailureResponderInterface::MODE_JSON, $mode);
    }

    public function testBrowserAcceptHeaderKeepsTheRedirect(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $this->url->method('getUrl')->willReturn('https://shop.test/contact/index/');
        $response->expects($this->never())->method('setHttpResponseCode');

        $mode = $this->responder($response)->respond(
            $this->form(),
            $this->request(false, 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'),
            $this->missingToken()
        );

        $this->assertSame(FailureResponderInterface::MODE_REDIRECT, $mode);
    }

    public function testFormForcingJsonAnswersJsonToAClassicPost(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $response->expects($this->once())->method('setHttpResponseCode')->with(400);

        $mode = $this->responder($response)->respond(
            $this->form(FormDefinitionInterface::FAILURE_RESPONSE_JSON),
            $this->request(),
            $this->missingToken()
        );

        $this->assertSame(FailureResponderInterface::MODE_JSON, $mode);
    }

    public function testFormForcingRedirectRedirectsAnAjaxRequest(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $this->url->method('getUrl')->willReturn('https://shop.test/contact/index/');
        $response->expects($this->never())->method('setHttpResponseCode');

        $mode = $this->responder($response)->respond(
            $this->form(FormDefinitionInterface::FAILURE_RESPONSE_REDIRECT),
            $this->request(true),
            $this->missingToken()
        );

        $this->assertSame(FailureResponderInterface::MODE_REDIRECT, $mode);
    }

    /**
     * @dataProvider jsonCodes
     */
    public function testJsonCodeNeverExposesCloudflareErrorCodes(ValidationResult $result, string $code): void
    {
        $response = $this->createMock(HttpInterface::class);
        $body = null;
        $response->method('setBody')->willReturnCallback(static function (string $value) use (&$body, $response) {
            $body = $value;
            return $response;
        });

        $this->responder($response)->respond($this->form(), $this->request(true), $result);

        $decoded = json_decode((string) $body, true);
        $this->assertSame($code, $decoded['code']);
        foreach ($result->getErrorCodes() as $cloudflareCode) {
            $this->assertStringNotContainsString($cloudflareCode, (string) $body);
        }
    }

    /**
     * @return array<string, array{ValidationResult, string}>
     */
    public static function jsonCodes(): array
    {
        return [
            'missing' => [ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']), 'missing'],
            'invalid' => [ValidationResult::failure(ValidationResult::ERROR_USER, ['invalid-input-response', 'timeout-or-duplicate']), 'invalid'],
            'method' => [ValidationResult::failure(ValidationResult::ERROR_USER, [MethodPolicy::ERROR_CODE]), 'method'],
            'config' => [ValidationResult::failure(ValidationResult::ERROR_CONFIG, ['invalid-input-secret']), 'config'],
            'unavailable' => [ValidationResult::failure(ValidationResult::ERROR_UNAVAILABLE, ['internal-error']), 'unavailable'],
        ];
    }
}
