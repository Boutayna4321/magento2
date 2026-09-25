<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Stub;

use AlpineCommerce\Turnstile\Model\SiteVerifyClient;

/**
 * Integration test double for Cloudflare Siteverify: no network call, a configurable answer and a call
 * counter (to prove when Cloudflare is, or is not, asked).
 */
class FakeSiteVerifyClient extends SiteVerifyClient
{
    public int $calls = 0;

    /**
     * @var array<string, mixed>
     */
    public array $answer = ['success' => true, 'action' => 'contact'];

    /**
     * The parent needs an HTTP client factory; the double never calls Cloudflare.
     */
    public function __construct()
    {
    }

    public function verify(string $secret, string $token, ?string $remoteIp, int $timeout): array
    {
        $this->calls++;

        return $this->answer;
    }
}
