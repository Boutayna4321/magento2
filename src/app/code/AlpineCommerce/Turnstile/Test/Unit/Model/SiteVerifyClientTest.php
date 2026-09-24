<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use AlpineCommerce\Turnstile\Model\SiteVerifyClient;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SiteVerifyClientTest extends TestCase
{
    private const URL = 'https://verify.test/siteverify';

    private Curl&MockObject $curl;
    private SiteVerifyClient $client;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $factory = $this->createMock(ClientFactory::class);
        $factory->method('create')->willReturn($this->curl);
        $this->client = new SiteVerifyClient($factory, new Json(), self::URL);
    }

    public function testPostsFieldsAndReturnsDecodedBody(): void
    {
        $this->curl->expects($this->once())->method('setTimeout')->with(7);
        $this->curl->expects($this->once())->method('post')->with(
            self::URL,
            ['secret' => 's3cr3t', 'response' => 'tok', 'remoteip' => '203.0.113.5']
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"success":true,"action":"contact"}');

        $this->assertSame(
            ['success' => true, 'action' => 'contact'],
            $this->client->verify('s3cr3t', 'tok', '203.0.113.5', 7)
        );
    }

    public function testRemoteIpOmittedWhenNull(): void
    {
        $this->curl->expects($this->once())->method('post')->with(
            self::URL,
            ['secret' => 's3cr3t', 'response' => 'tok']
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"success":false}');

        $this->client->verify('s3cr3t', 'tok', null, 5);
    }

    public function testTransportErrorThrowsWithoutLeakingSecret(): void
    {
        $this->curl->method('post')->willThrowException(new \Exception('Connection refused'));

        try {
            $this->client->verify('s3cr3t', 'tok', null, 5);
            $this->fail('Exception expected');
        } catch (SiteVerifyUnavailableException $e) {
            $this->assertStringNotContainsString('s3cr3t', $e->getMessage());
            $this->assertStringNotContainsString('tok', $e->getMessage());
            $this->assertNull($e->getHttpStatus());
        }
    }

    public function testHttp400WithSiteverifyJsonReturnsBody(): void
    {
        // Real Siteverify answer for an invalid secret: HTTP 400 with a regular JSON body.
        $this->curl->method('getStatus')->willReturn(400);
        $this->curl->method('getBody')->willReturn('{"error-codes":["invalid-input-secret"],"success":false,"messages":[]}');

        $this->assertSame(
            ['error-codes' => ['invalid-input-secret'], 'success' => false, 'messages' => []],
            $this->client->verify('bad-secret', 'tok', null, 5)
        );
    }

    public function testNon200Throws(): void
    {
        $this->curl->method('getStatus')->willReturn(503);
        $this->curl->method('getBody')->willReturn('Service Unavailable');

        try {
            $this->client->verify('s3cr3t', 'tok', null, 5);
            $this->fail('Exception expected');
        } catch (SiteVerifyUnavailableException $e) {
            $this->assertSame(503, $e->getHttpStatus());
        }
    }

    public function testServerErrorWithoutSiteverifyJsonThrows(): void
    {
        $this->curl->method('getStatus')->willReturn(502);
        $this->curl->method('getBody')->willReturn('{"error":"bad gateway"}');

        try {
            $this->client->verify('s3cr3t', 'tok', null, 5);
            $this->fail('Exception expected');
        } catch (SiteVerifyUnavailableException $e) {
            $this->assertSame(502, $e->getHttpStatus());
        }
    }

    public function testInvalidJsonThrows(): void
    {
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('<html>');

        $this->expectException(SiteVerifyUnavailableException::class);
        $this->client->verify('s3cr3t', 'tok', null, 5);
    }

    public function testJsonWithoutSuccessThrows(): void
    {
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"foo":1}');

        $this->expectException(SiteVerifyUnavailableException::class);
        $this->client->verify('s3cr3t', 'tok', null, 5);
    }
}
