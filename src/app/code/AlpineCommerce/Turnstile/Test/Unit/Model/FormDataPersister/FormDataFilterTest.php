<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataFilter;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class FormDataFilterTest extends TestCase
{
    private function request(mixed $post, array $query = []): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->willReturnCallback(
            static fn (?string $key = null, mixed $default = null): mixed =>
                $key === null ? $post : (is_array($post) ? ($post[$key] ?? $default) : $default)
        );
        $request->method('getParams')->willReturn(array_merge($query, is_array($post) ? $post : []));
        $request->method('getQuery')->willReturn($query);

        return $request;
    }

    public function testSensitiveAndTokenFieldsRemoved(): void
    {
        $filtered = (new FormDataFilter())->filter($this->request([
            'firstname' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'current_password' => 'Old123!',
            'form_key' => 'abcd1234',
            'cf-turnstile-response' => 'token',
        ]));

        $this->assertSame(['firstname' => 'Jane', 'email' => 'jane@example.com'], $filtered);
    }

    public function testSensitiveFieldsAreRemovedAtAnyDepthAndInAnyCase(): void
    {
        $filtered = (new FormDataFilter())->filter($this->request([
            'login' => ['username' => 'jane@example.com', 'password' => 'Secret123!'],
            'account' => ['Password' => 'Secret123!', 'PASSWORD_CONFIRMATION' => 'x', 'name' => 'Jane'],
        ]));

        $this->assertSame(
            ['login' => ['username' => 'jane@example.com'], 'account' => ['name' => 'Jane']],
            $filtered
        );
    }

    public function testExcludedPathsAreRemoved(): void
    {
        $filtered = (new FormDataFilter())->filter(
            $this->request(['recipients' => ['email' => ['a@example.com'], 'name' => ['A']], 'sender' => ['message' => 'Hi']]),
            ['recipients/email', 'sender']
        );

        $this->assertSame(['recipients' => ['name' => ['A']]], $filtered);
    }

    public function testQueryStringParametersAreNeverKept(): void
    {
        $filtered = (new FormDataFilter())->filter($this->request(['name' => 'Jane'], ['utm_source' => 'mail', 'name' => 'Query']));

        $this->assertSame(['name' => 'Jane'], $filtered);
    }

    public function testNonArrayBodyGivesNothing(): void
    {
        $this->assertSame([], (new FormDataFilter())->filter($this->request(null)));
        $this->assertSame([], (new FormDataFilter())->filter($this->request('raw body')));
    }
}
