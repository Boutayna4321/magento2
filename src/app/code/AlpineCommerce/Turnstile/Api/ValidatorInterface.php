<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Api;

use AlpineCommerce\Turnstile\Model\ValidationResult;

/**
 * Validates a Turnstile token server-side for a given form and store.
 *
 * @api
 */
interface ValidatorInterface
{
    public function validate(string $token, ?string $remoteIp, string $formId, ?int $storeId = null): ValidationResult;
}
