<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * The only place that talks to Cloudflare Siteverify.
 */
class SiteVerifyClient
{
    public const DEFAULT_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly ClientFactory $clientFactory,
        private readonly Json $json,
        private readonly string $verifyUrl = self::DEFAULT_URL
    ) {
    }

    /**
     * @return array<string, mixed> Decoded Siteverify response (always contains "success")
     * @throws SiteVerifyUnavailableException
     */
    public function verify(string $secret, string $token, ?string $remoteIp, int $timeout): array
    {
        $params = ['secret' => $secret, 'response' => $token];
        if ($remoteIp !== null && $remoteIp !== '') {
            $params['remoteip'] = $remoteIp;
        }

        $client = $this->clientFactory->create();
        $client->setTimeout($timeout);

        try {
            $client->post($this->verifyUrl, $params);
        } catch (\Exception $e) {
            throw new SiteVerifyUnavailableException('Siteverify transport error: ' . $e->getMessage(), null, $e);
        }

        $status = (int) $client->getStatus();
        if ($status !== 200) {
            throw new SiteVerifyUnavailableException('Siteverify returned HTTP ' . $status, $status);
        }

        try {
            $data = $this->json->unserialize((string) $client->getBody());
        } catch (\InvalidArgumentException $e) {
            throw new SiteVerifyUnavailableException('Siteverify returned invalid JSON', $status, $e);
        }

        if (!is_array($data) || !array_key_exists('success', $data)) {
            throw new SiteVerifyUnavailableException('Siteverify response has no "success" field', $status);
        }

        return $data;
    }
}
