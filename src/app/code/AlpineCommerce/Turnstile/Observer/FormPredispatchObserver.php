<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Observer;

use AlpineCommerce\Turnstile\Model\FailureResponderInterface;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Model\FormRegistry;
use AlpineCommerce\Turnstile\Model\Guard\MethodPolicy;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Single observer on controller_action_predispatch for every protected form.
 *
 * The protected form is identified by the router's full action name, never by a request parameter.
 * Magento runs the CSRF (form key) check before this event, so a request that fails it never gets here.
 */
class FormPredispatchObserver implements ObserverInterface
{
    public function __construct(
        private readonly FormRegistry $formRegistry,
        private readonly FormGuard $formGuard,
        private readonly FailureResponderInterface $failureResponder
    ) {
    }

    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $request = $event->getData('request');
        if (!$request instanceof Http) {
            return;
        }

        $form = $this->formRegistry->findByAction((string) $request->getFullActionName());
        if ($form === null) {
            return;
        }

        $action = $event->getData('controller_action');
        // Magento always passes the action; if it is ever missing on a protected route, fail closed.
        $result = $action instanceof ActionInterface
            ? $this->formGuard->check($form, $request, $action)
            : ValidationResult::failure(ValidationResult::ERROR_USER, [MethodPolicy::ERROR_CODE]);
        if ($result->isValid()) {
            return;
        }

        if ($this->failureResponder->respond($form, $request, $result) === FailureResponderInterface::MODE_REDIRECT) {
            $form->getPersister()?->persist($request);
        }
    }
}
