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

        // Siteverify answers some validation errors (e.g. invalid-input-secret) with HTTP 400 and a
        // regular JSON body: any status carrying a Siteverify answer is returned to the caller.
        $status = (int) $client->getStatus();
        try {
            $data = $this->json->unserialize((string) $client->getBody());
        } catch (\InvalidArgumentException $e) {
            $data = null;
        }

        if (is_array($data) && array_key_exists('success', $data)) {
            return $data;
        }

        throw new SiteVerifyUnavailableException(
            'Siteverify returned HTTP ' . $status . ' without a Siteverify answer',
            $status
        );
    }
}
