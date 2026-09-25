<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Guard;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;

/**
 * HTTP method rule for a protected route (decision D3 = A).
 *
 * A controller that declares no Http*ActionInterface accepts every method, so a protection limited to
 * POST could be bypassed by sending the same parameters with GET. Rule: POST is always validated;
 * GET/HEAD pass without validation only when the action explicitly declares HttpGetActionInterface
 * (a page its developer meant to display); every other method is rejected.
 */
class MethodPolicy
{
    public const VALIDATE = 'validate';
    public const SKIP = 'skip';
    public const REJECT = 'reject';

    public const ERROR_CODE = 'method-not-allowed';

    public function decide(Http $request, ActionInterface $action): string
    {
        $method = strtoupper((string) $request->getMethod());
        if ($method === 'POST') {
            return self::VALIDATE;
        }
        if (($method === 'GET' || $method === 'HEAD') && $action instanceof HttpGetActionInterface) {
            return self::SKIP;
        }

        return self::REJECT;
    }
}
