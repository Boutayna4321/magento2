<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Observer;

use AlpineCommerce\Turnstile\Model\FormGuard;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;

/**
 * Runs before contact/index/post: no e-mail is sent when Turnstile fails.
 */
class ContactFormObserver implements ObserverInterface
{
    public const FORM_ID = 'contact';

    public function __construct(
        private readonly FormGuard $formGuard,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly UrlInterface $url
    ) {
    }

    public function execute(Observer $observer): void
    {
        $action = $observer->getEvent()->getData('controller_action');
        if (!$action instanceof AbstractAction) {
            return;
        }
        $response = $action->getResponse();
        if (!$response instanceof HttpInterface) {
            return;
        }

        $request = $action->getRequest();
        $passed = $this->formGuard->guard(self::FORM_ID, $request, $response, $this->url->getUrl('contact/index'));

        if (!$passed) {
            // Keep the customer's input for the pre-filled form, never the token.
            $params = $request->getParams();
            unset($params[FormGuard::TOKEN_FIELD]);
            $this->dataPersistor->set('contact_us', $params);
        }
    }
}
