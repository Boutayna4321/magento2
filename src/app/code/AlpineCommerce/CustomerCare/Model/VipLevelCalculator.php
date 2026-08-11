<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model;

/**
 * Computes the VIP level for a lifetime spend against configured thresholds.
 */
class VipLevelCalculator
{
    public function __construct(private readonly Config $config)
    {
    }

    public function calculate(float $lifetimeSpent, ?int $websiteId = null): string
    {
        $gold = $this->config->getGoldThreshold($websiteId);
        $silver = $this->config->getSilverThreshold($websiteId);
        $bronze = $this->config->getBronzeThreshold($websiteId);

        if ($lifetimeSpent >= $gold) {
            return VipLevel::GOLD;
        }
        if ($lifetimeSpent >= $silver) {
            return VipLevel::SILVER;
        }
        if ($lifetimeSpent >= $bronze) {
            return VipLevel::BRONZE;
        }
        return VipLevel::NONE;
    }
}
