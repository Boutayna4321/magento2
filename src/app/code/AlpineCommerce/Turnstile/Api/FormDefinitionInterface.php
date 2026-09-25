<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Api;

use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataPersisterInterface;

/**
 * A form that Turnstile can protect. Declared in di.xml and collected by the form registry.
 *
 * @api
 */
interface FormDefinitionInterface
{
    public const FAILURE_RESPONSE_AUTO = 'auto';
    public const FAILURE_RESPONSE_REDIRECT = 'redirect';
    public const FAILURE_RESPONSE_JSON = 'json';

    /**
     * Unique id, also sent to Cloudflare as the Turnstile "action" (format ^[a-z0-9_]{1,32}$).
     */
    public function getId(): string;

    public function getLabel(): string;

    /**
     * @return string[] Full action names protected by this form (route_controller_action), lowercase
     */
    public function getActions(): array;

    /**
     * @return string|null Magento module the form belongs to; the form is ignored while it is disabled
     */
    public function getRequiredModule(): ?string;

    /**
     * @return string One of the FAILURE_RESPONSE_* constants
     */
    public function getFailureResponse(): string;

    /**
     * @return string|null Route path to redirect to after a rejection; null means the previous page
     */
    public function getRedirectPath(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getRedirectParams(): array;

    public function getPersister(): ?FormDataPersisterInterface;

    /**
     * @return string|null Config path of Magento's reCAPTCHA setting for the same form, used to warn about conflicts
     */
    public function getRecaptchaConfigPath(): ?string;

    public function getSortOrder(): int;
}
