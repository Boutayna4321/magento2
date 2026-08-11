<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Cron;

use AlpineCommerce\CustomerCare\Api\CustomerCareInterface;
use AlpineCommerce\CustomerCare\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;

/**
 * Nightly recompute of VIP status for all customers.
 */
class UpdateVipLevels
{
    public function __construct(
        private readonly CustomerCareInterface $customerCare,
        private readonly Config $config,
        private readonly State $state,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $oldArea = null;
        try {
            $oldArea = $this->state->getAreaCode();
        } catch (\Exception $e) {
            // no area set yet
        }
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // already set
        }

        try {
            if (!$this->config->isEnabled()) {
                $this->logger->info('CustomerCare: VIP program disabled, cron skipped.');
                return;
            }

            $updated = $this->customerCare->recalculateAll();
            $this->logger->info(sprintf('CustomerCare: cron updated VIP status for %d customers.', $updated));
        } finally {
            if ($oldArea !== null) {
                try {
                    $this->state->setAreaCode($oldArea);
                } catch (\Exception $e) {
                    // ignore restore failure
                }
            }
        }
    }
}
