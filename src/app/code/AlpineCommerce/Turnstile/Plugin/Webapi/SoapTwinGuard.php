<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Plugin\Webapi;

use AlpineCommerce\Turnstile\Model\Twin\TwinBlocker;
use AlpineCommerce\Turnstile\Model\Twin\TwinRegistry;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Request;
use Magento\Webapi\Controller\Soap\Request\Handler;

/**
 * Refuses anonymous SOAP calls to a blocked API twin (S27b): the operation is mapped to its service class
 * and method, the same switch as REST applies, and the refusal (HTTP 403, a SOAP fault) happens before
 * the handler checks authorization, converts the arguments or runs the service.
 *
 * The SOAP configuration and the user context belong to optional modules; they come as items of the
 * "collaborators" array argument of etc/webapi_soap/di.xml (rule R1).
 */
class SoapTwinGuard
{
    /**
     * @param array<string, mixed> $collaborators soapConfig: Magento\Webapi\Model\Soap\Config,
     *                                            userContext: Magento\Authorization\Model\UserContextInterface
     */
    public function __construct(
        private readonly TwinRegistry $twinRegistry,
        private readonly TwinBlocker $twinBlocker,
        private readonly Request $request,
        private readonly array $collaborators = []
    ) {
    }

    /**
     * @param string $operation SOAP operation name (for example customerAccountManagementV1CreateAccount)
     * @param array<mixed> $arguments
     * @return null Arguments are left unchanged
     * @throws WebapiException HTTP 403 for a blocked anonymous call
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function before__call(Handler $subject, $operation, $arguments)
    {
        $soapConfig = $this->collaborator('soapConfig', 'getServiceMethodInfo');
        $userContext = $this->collaborator('userContext', 'getUserType');

        try {
            $info = $soapConfig->getServiceMethodInfo((string) $operation, $this->request->getRequestedServices());
        } catch (\Exception $exception) {
            // Unknown operation: Magento's own handler answers it.
            return null;
        }

        $twin = $this->twinRegistry->findByService((string) ($info['class'] ?? ''), (string) ($info['method'] ?? ''));
        if ($twin !== null && $this->twinBlocker->mustBlock($twin, $userContext->getUserType(), 'soap')) {
            throw new WebapiException(__('This operation is not available.'), 0, WebapiException::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function collaborator(string $name, string $method): object
    {
        $collaborator = $this->collaborators[$name] ?? null;
        if (!is_object($collaborator) || !method_exists($collaborator, $method)) {
            throw new \LogicException(sprintf(
                'SoapTwinGuard needs the "%s" collaborator from etc/webapi_soap/di.xml.',
                $name
            ));
        }

        return $collaborator;
    }
}
