<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use AlpineCommerce\Turnstile\Model\Guard\TokenReader;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turnstile decision for a protected form: HTTP method rule, token reading, server-side validation.
 *
 * check() only decides; the caller responds (FailureResponder) and keeps the input (persister).
 * guard() is the previous contact-only entry point, kept until the generic observer replaces it.
 */
class FormGuard
{
    public const TOKEN_FIELD = TokenReader::FIELD;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly RemoteAddress $remoteAddress,
        private readonly ManagerInterface $messageManager,
        private readonly ActionFlag $actionFlag,
        private readonly TokenReader $tokenReader,
        private readonly MethodPolicy $methodPolicy
    ) {
    }

    /**
     * @return ValidationResult Valid when the request may reach the controller
     *                          (form disabled, declared GET page, or token accepted)
     */
    public function check(FormDefinitionInterface $form, Http $request, ActionInterface $action): ValidationResult
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isEnabledFor($form->getId(), $storeId)) {
            return ValidationResult::success();
        }

        $decision = $this->methodPolicy->decide($request, $action);
        if ($decision === MethodPolicy::SKIP) {
            return ValidationResult::success();
        }
        if ($decision === MethodPolicy::REJECT) {
            return ValidationResult::failure(ValidationResult::ERROR_USER, [MethodPolicy::ERROR_CODE]);
        }

        $remoteIp = $this->remoteAddress->getRemoteAddress();

        return $this->validator->validate(
            $this->tokenReader->read($request),
            is_string($remoteIp) && $remoteIp !== '' ? $remoteIp : null,
            $form->getId(),
            $storeId
        );
    }

    /**
     * @return bool true when the request may continue to the controller
     */
    public function guard(
        string $formId,
        RequestInterface $request,
        HttpInterface $response,
        string $redirectUrl
    ): bool {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isEnabledFor($formId, $storeId)) {
            return true;
        }

        $rawToken = $request->getParam(self::TOKEN_FIELD);
        $token = is_string($rawToken) ? trim($rawToken) : '';
        $remoteIp = $this->remoteAddress->getRemoteAddress();

        $result = $this->validator->validate(
            $token,
            is_string($remoteIp) && $remoteIp !== '' ? $remoteIp : null,
            $formId,
            $storeId
        );
        if ($result->isValid()) {
            return true;
        }

        $this->messageManager->addErrorMessage((string) $this->messageFor($result));
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        $response->setRedirect($redirectUrl);

        return false;
    }

    private function messageFor(ValidationResult $result): Phrase
    {
        return match ($result->getErrorType()) {
            ValidationResult::ERROR_CONFIG => __('We could not verify your request. Please try again later.'),
            ValidationResult::ERROR_UNAVAILABLE => __('The security check is temporarily unavailable. Please try again later.'),
            default => in_array('missing-input-response', $result->getErrorCodes(), true)
                ? __('Please complete the security check.')
                : __('The security check failed. Please try again.'),
        };
    }
}
