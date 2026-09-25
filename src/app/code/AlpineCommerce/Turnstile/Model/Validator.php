<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use AlpineCommerce\Turnstile\Model\Config\Source\FailureMode;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;

/**
 * Server-side Turnstile rules. Log records never contain the token or the secret.
 */
class Validator implements ValidatorInterface
{
    public const MAX_TOKEN_LENGTH = 2048;

    private const CONFIG_ERRORS = ['missing-input-secret', 'invalid-input-secret', 'bad-request'];
    private const UNAVAILABLE_ERRORS = ['internal-error'];

    public function __construct(
        private readonly SiteVerifyClient $client,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly State $appState
    ) {
    }

    public function validate(string $token, ?string $remoteIp, string $formId, ?int $storeId = null): ValidationResult
    {
        if ($token === '') {
            return ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']);
        }
        if (strlen($token) > self::MAX_TOKEN_LENGTH) {
            return ValidationResult::failure(ValidationResult::ERROR_USER, ['token-too-long']);
        }

        try {
            $response = $this->client->verify(
                $this->config->getSecretKey($storeId),
                $token,
                $remoteIp,
                $this->config->getTimeout($storeId)
            );
        } catch (SiteVerifyUnavailableException $e) {
            return $this->unavailable($formId, $storeId, [], $e->getHttpStatus(), $e->getMessage());
        }

        $errorCodes = array_values(array_filter((array) ($response['error-codes'] ?? []), 'is_string'));

        if (($response['success'] ?? null) === true) {
            $isTestingKey = ($response['metadata']['result_with_testing_key'] ?? null) === true;
            if ($isTestingKey && $this->appState->getMode() === State::MODE_PRODUCTION) {
                $this->logger->critical('Turnstile test key used in production mode: request rejected.', [
                    'form_id' => $formId,
                    'store_id' => $storeId,
                ]);
                return ValidationResult::failure(ValidationResult::ERROR_CONFIG, ['testing-key-in-production']);
            }
            if ($isTestingKey) {
                $this->logger->warning('Turnstile test key answer accepted (not in production mode).', [
                    'form_id' => $formId,
                    'store_id' => $storeId,
                ]);
            }
            if (!$this->isExpectedAction($response, $formId)) {
                $this->logger->warning('Turnstile action mismatch.', [
                    'form_id' => $formId,
                    'store_id' => $storeId,
                    'received_action' => is_string($response['action'] ?? null) ? $response['action'] : null,
                ]);
                return ValidationResult::failure(ValidationResult::ERROR_USER, ['action-mismatch']);
            }
            return ValidationResult::success();
        }

        if (array_intersect($errorCodes, self::CONFIG_ERRORS)) {
            $this->logger->critical('Turnstile configuration error.', [
                'form_id' => $formId,
                'store_id' => $storeId,
                'error_codes' => $errorCodes,
            ]);
            return ValidationResult::failure(ValidationResult::ERROR_CONFIG, $errorCodes);
        }

        if (array_intersect($errorCodes, self::UNAVAILABLE_ERRORS)) {
            return $this->unavailable($formId, $storeId, $errorCodes, 200, 'Siteverify internal error');
        }

        return ValidationResult::failure(ValidationResult::ERROR_USER, $errorCodes);
    }

    /**
     * Cloudflare's official test secrets answer without "action" and flag the answer with
     * metadata.result_with_testing_key; only that answer may omit the action.
     *
     * @param array<string, mixed> $response
     */
    private function isExpectedAction(array $response, string $formId): bool
    {
        if (!array_key_exists('action', $response)) {
            return ($response['metadata']['result_with_testing_key'] ?? null) === true;
        }

        return $response['action'] === $formId;
    }

    /**
     * @param string[] $errorCodes
     */
    private function unavailable(
        string $formId,
        ?int $storeId,
        array $errorCodes,
        ?int $httpStatus,
        string $reason
    ): ValidationResult {
        $context = [
            'form_id' => $formId,
            'store_id' => $storeId,
            'error_codes' => $errorCodes,
            'http_status' => $httpStatus,
            'reason' => $reason,
        ];

        if ($this->config->getFailureMode($storeId, $formId) === FailureMode::OPEN) {
            $this->logger->warning('Turnstile unavailable: request accepted (failure mode open).', $context);
            return ValidationResult::success();
        }

        $this->logger->error('Turnstile unavailable: request rejected (failure mode closed).', $context);
        return ValidationResult::failure(ValidationResult::ERROR_UNAVAILABLE, $errorCodes);
    }
}
