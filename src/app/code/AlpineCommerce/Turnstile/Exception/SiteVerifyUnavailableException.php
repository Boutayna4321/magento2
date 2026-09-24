<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Exception;

/**
 * Siteverify could not be reached or returned an unusable answer.
 * Messages never contain the secret or the token.
 */
class SiteVerifyUnavailableException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatus = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }
}
