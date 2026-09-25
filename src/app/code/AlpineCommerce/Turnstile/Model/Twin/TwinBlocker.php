<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Twin;

use AlpineCommerce\Turnstile\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Decides whether a call to an API twin must be refused (decision D4 = B): only anonymous callers
 * (no user context, or guest) and only when the twin's switch is on for the current store view.
 * The request payload is never logged.
 */
class TwinBlocker
{
    /** Magento\Authorization\Model\UserContextInterface::USER_TYPE_GUEST */
    private const USER_TYPE_GUEST = 4;

    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param mixed $userType User type from Magento's user context (null when there is none)
     * @param string $channel rest, soap or graphql (logged only)
     */
    public function mustBlock(TwinDefinition $twin, mixed $userType, string $channel): bool
    {
        if ($userType !== null && (int) $userType !== self::USER_TYPE_GUEST) {
            return false;
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isTwinBlocked($twin->getId(), $storeId)) {
            return false;
        }

        $this->logger->warning('Turnstile API twin blocked.', [
            'twin_id' => $twin->getId(),
            'channel' => $channel,
            'store_id' => $storeId,
        ]);

        return true;
    }
}
