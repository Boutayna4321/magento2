<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\Guard;

use AlpineCommerce\Turnstile\Model\Guard\TokenReader;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class TokenReaderTest extends TestCase
{
    /**
     * @param array<string, mixed> $post
     */
    private function request(array $post = [], string|false $header = false, array $query = []): Http
    {
        $request = $this->createMock(Http::class);
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
        $request->method('getQuery')->willReturnCallback(
            static fn (?string $key = null, mixed $default = null): mixed => $query[$key] ?? $default
        );

        return $request;
    }

    public function testReadsTrimmedTokenFromPostBody(): void
    {
        $this->assertSame('tok', (new TokenReader())->read($this->request([TokenReader::FIELD => '  tok  '])));
    }

    public function testReadsHeaderWhenBodyHasNoToken(): void
    {
        $this->assertSame('head-tok', (new TokenReader())->read($this->request([], ' head-tok ')));
    }

    public function testBodyTokenWinsOverHeader(): void
    {
        $this->assertSame('body-tok', (new TokenReader())->read($this->request([TokenReader::FIELD => 'body-tok'], 'head-tok')));
    }

    public function testBlankBodyTokenFallsBackToHeader(): void
    {
        $this->assertSame('head-tok', (new TokenReader())->read($this->request([TokenReader::FIELD => '   '], 'head-tok')));
    }

    public function testQueryStringTokenIsIgnored(): void
    {
        $this->assertSame('', (new TokenReader())->read($this->request([], false, [TokenReader::FIELD => 'query-tok'])));
    }

    public function testNonStringBodyValueIsIgnored(): void
    {
        $this->assertSame('', (new TokenReader())->read($this->request([TokenReader::FIELD => ['tok']])));
    }

    public function testEmptyWhenNothingIsProvided(): void
    {
        $this->assertSame('', (new TokenReader())->read($this->request()));
    }

    public function testFieldAndHeaderNamesAreTheDocumentedOnes(): void
    {
        $this->assertSame('cf-turnstile-response', TokenReader::FIELD);
        $this->assertSame('X-Turnstile-Token', TokenReader::HEADER);
    }
}
