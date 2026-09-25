<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;

/**
 * Answers a request rejected by Turnstile.
 *
 * The controller is always stopped first (no-dispatch flag), whatever the response type. Then:
 * - AJAX/fetch (X-Requested-With or Accept: application/json), or a form forcing JSON: HTTP 400 JSON;
 * - otherwise: error message + redirect to the form's path, or to the previous page through Magento's
 *   referer check, which falls back to the store base URL for an external or missing referer.
 * The JSON body never contains Cloudflare's raw error codes.
 */
class FailureResponder implements FailureResponderInterface
{
    public function __construct(
        private readonly ActionFlag $actionFlag,
        private readonly ResponseInterface $response,
        private readonly ManagerInterface $messageManager,
        private readonly UrlInterface $url,
        private readonly RedirectInterface $redirect,
        private readonly Json $json
    ) {
    }

    public function respond(FormDefinitionInterface $form, Http $request, ValidationResult $result): string
    {
        // Magento's own controllers pass true here; ActionFlag::set() documents a string by mistake.
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true); // @phpstan-ignore-line

        if (!$this->response instanceof HttpInterface) {
            return self::MODE_NONE;
        }

        $message = (string) $this->messageFor($result);
        if ($this->wantsJson($form, $request)) {
            $this->response->setHttpResponseCode(400);
            $this->response->setHeader('Content-Type', 'application/json', true);
            $this->response->setBody($this->json->serialize([
                'success' => false,
                'error' => 'turnstile',
                'code' => $this->codeFor($result),
                'message' => $message,
            ]));

            return self::MODE_JSON;
        }

        $this->messageManager->addErrorMessage($message);
        $this->response->setRedirect($this->redirectUrl($form));

        return self::MODE_REDIRECT;
    }

    private function wantsJson(FormDefinitionInterface $form, Http $request): bool
    {
        return match ($form->getFailureResponse()) {
            FormDefinitionInterface::FAILURE_RESPONSE_JSON => true,
            FormDefinitionInterface::FAILURE_RESPONSE_REDIRECT => false,
            default => $request->isXmlHttpRequest()
                || str_contains(strtolower((string) $request->getHeader('Accept')), 'application/json'),
        };
    }

    private function redirectUrl(FormDefinitionInterface $form): string
    {
        $path = $form->getRedirectPath();

        return $path === null || $path === ''
            ? (string) $this->redirect->getRefererUrl()
            : $this->url->getUrl($path, $form->getRedirectParams());
    }

    private function codeFor(ValidationResult $result): string
    {
        return match ($result->getErrorType()) {
            ValidationResult::ERROR_CONFIG => 'config',
            ValidationResult::ERROR_UNAVAILABLE => 'unavailable',
            default => match (true) {
                in_array(MethodPolicy::ERROR_CODE, $result->getErrorCodes(), true) => 'method',
                in_array('missing-input-response', $result->getErrorCodes(), true) => 'missing',
                default => 'invalid',
            },
        };
    }

    private function messageFor(ValidationResult $result): Phrase
    {
        return match ($this->codeFor($result)) {
            'config' => __('We could not verify your request. Please try again later.'),
            'unavailable' => __('The security check is temporarily unavailable. Please try again later.'),
            'missing' => __('Please complete the security check.'),
            default => __('The security check failed. Please try again.'),
        };
    }
}
