<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\Guard;

use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

/**
 * Decision D3 = A: on a protected route, POST is validated, GET/HEAD pass only for an action that
 * explicitly declares HttpGetActionInterface, every other method is rejected.
 */
class MethodPolicyTest extends TestCase
{
    private function request(string $method): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getMethod')->willReturn($method);

        return $request;
    }

    public function testPostIsValidated(): void
    {
        $action = $this->createMock(ActionInterface::class);

        $this->assertSame(MethodPolicy::VALIDATE, (new MethodPolicy())->decide($this->request('POST'), $action));
    }

    public function testLowercasePostIsValidated(): void
    {
        $action = $this->createMock(ActionInterface::class);

        $this->assertSame(MethodPolicy::VALIDATE, (new MethodPolicy())->decide($this->request('post'), $action));
    }

    public function testPostIsValidatedEvenWhenTheActionAlsoDeclaresGet(): void
    {
        // A controller that both displays (GET) and submits (POST) on the same URL.
        $action = new class implements HttpGetActionInterface, HttpPostActionInterface {
            public function execute(): \Magento\Framework\Controller\ResultInterface
            {
                throw new \LogicException('The method policy never executes the action.');
            }
        };

        $this->assertSame(MethodPolicy::VALIDATE, (new MethodPolicy())->decide($this->request('POST'), $action));
    }

    public function testGetOnActionDeclaringGetIsSkipped(): void
    {
        $action = $this->createMock(HttpGetActionInterface::class);

        $this->assertSame(MethodPolicy::SKIP, (new MethodPolicy())->decide($this->request('GET'), $action));
    }

    public function testHeadOnActionDeclaringGetIsSkipped(): void
    {
        $action = $this->createMock(HttpGetActionInterface::class);

        $this->assertSame(MethodPolicy::SKIP, (new MethodPolicy())->decide($this->request('HEAD'), $action));
    }

    public function testGetOnActionWithoutMethodInterfaceIsRejected(): void
    {
        $action = $this->createMock(ActionInterface::class);

        $this->assertSame(MethodPolicy::REJECT, (new MethodPolicy())->decide($this->request('GET'), $action));
    }

    /**
     * @dataProvider unsafeMethods
     */
    public function testOtherMethodsAreRejectedEvenWhenTheActionDeclaresGet(string $method): void
    {
        $action = $this->createMock(HttpGetActionInterface::class);

        $this->assertSame(MethodPolicy::REJECT, (new MethodPolicy())->decide($this->request($method), $action));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeMethods(): array
    {
        return ['PUT' => ['PUT'], 'DELETE' => ['DELETE'], 'PATCH' => ['PATCH'], 'OPTIONS' => ['OPTIONS'], 'empty' => ['']];
    }
}
