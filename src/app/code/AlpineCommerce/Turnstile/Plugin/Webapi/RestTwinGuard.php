<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Plugin\Webapi;

use AlpineCommerce\Turnstile\Model\Twin\TwinBlocker;
use AlpineCommerce\Turnstile\Model\Twin\TwinRegistry;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest\RequestValidator;

/**
 * Refuses anonymous REST calls to a blocked API twin (S27) with HTTP 403, before the request is
 * validated or the service runs. Synchronous, asynchronous and bulk REST all go through
 * RequestValidator::validate() with the endpoint path, so one plugin covers them (S27c).
 *
 * The router and the user context belong to optional modules (Magento_Webapi, Magento_Authorization).
 * They come as items of the "collaborators" array argument of etc/webapi_rest/di.xml: Magento resolves
 * object items inside array arguments, the constructor names no optional class (rule R1), and that
 * di.xml is only read when the webapi_rest area exists.
 */
class RestTwinGuard
{
    /**
     * @param array<string, mixed> $collaborators router: Magento\Webapi\Controller\Rest\Router,
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
     * @throws WebapiException HTTP 403 for a blocked anonymous call
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeValidate(RequestValidator $subject): void
    {
        $router = $this->collaborator('router', 'match');
        $userContext = $this->collaborator('userContext', 'getUserType');

        try {
            $route = $router->match($this->request);
        } catch (\Exception $exception) {
            // Unknown route: Magento's own validation answers it.
            return;
        }

        $twin = $this->twinRegistry->findByService((string) $route->getServiceClass(), (string) $route->getServiceMethod());
        if ($twin !== null && $this->twinBlocker->mustBlock($twin, $userContext->getUserType(), 'rest')) {
            throw new WebapiException(__('This operation is not available.'), 0, WebapiException::HTTP_FORBIDDEN);
        }
    }

    private function collaborator(string $name, string $method): object
    {
        $collaborator = $this->collaborators[$name] ?? null;
        if (!is_object($collaborator) || !method_exists($collaborator, $method)) {
            throw new \LogicException(sprintf(
                'RestTwinGuard needs the "%s" collaborator from etc/webapi_rest/di.xml.',
                $name
            ));
        }

        return $collaborator;
    }
}
