<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

/**
 * Immutable outcome of a Turnstile validation.
 */
final class ValidationResult
{
    public const ERROR_NONE = 'none';
    public const ERROR_USER = 'user';
    public const ERROR_CONFIG = 'config';
    public const ERROR_UNAVAILABLE = 'unavailable';

    /**
     * @param string[] $errorCodes
     */
    private function __construct(
        private readonly bool $valid,
        private readonly string $errorType,
        private readonly array $errorCodes
    ) {
    }

    public static function success(): self
    {
        return new self(true, self::ERROR_NONE, []);
    }

    /**
     * @param string[] $errorCodes
     */
    public static function failure(string $errorType, array $errorCodes = []): self
    {
        return new self(false, $errorType, array_values($errorCodes));
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * @return string[]
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }
}
