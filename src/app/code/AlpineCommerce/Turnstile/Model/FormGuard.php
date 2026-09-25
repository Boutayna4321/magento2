<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use AlpineCommerce\Turnstile\Model\Guard\TokenReader;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turnstile decision for a protected form: HTTP method rule, token reading, server-side validation.
 *
 * check() only decides; the observer responds (FailureResponder) and keeps the input (persister).
 */
class FormGuard
{
    public const TOKEN_FIELD = TokenReader::FIELD;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly RemoteAddress $remoteAddress,
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
}
