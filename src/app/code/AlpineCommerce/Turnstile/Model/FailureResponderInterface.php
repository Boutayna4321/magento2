<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use Magento\Framework\App\Request\Http;

/**
 * Answers a request rejected by Turnstile and always stops the controller.
 */
interface FailureResponderInterface
{
    /** An error message was added and the response redirects: the caller keeps the customer's input. */
    public const MODE_REDIRECT = 'redirect';

    /** An HTTP 400 JSON body was written (AJAX / fetch): nothing is kept. */
    public const MODE_JSON = 'json';

    /** The response is not an HTTP response: only the controller was stopped. */
    public const MODE_NONE = 'none';

    /**
     * @return string One of the MODE_* constants
     */
    public function respond(FormDefinitionInterface $form, Http $request, ValidationResult $result): string;
}
