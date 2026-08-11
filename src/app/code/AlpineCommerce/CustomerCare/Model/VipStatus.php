<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model;

use AlpineCommerce\CustomerCare\Api\Data\VipStatusInterface;

/**
 * VIP status data object.
 */
class VipStatus implements VipStatusInterface
{
    public function __construct(
        private int $customerId = 0,
        private string $vipLevel = VipLevel::NONE,
        private float $lifetimeSpent = 0.0,
        private float $bronzeThreshold = 0.0,
        private float $silverThreshold = 0.0,
        private float $goldThreshold = 0.0
    ) {
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function setCustomerId(int $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getVipLevel(): string
    {
        return $this->vipLevel;
    }

    public function setVipLevel(string $vipLevel): self
    {
        $this->vipLevel = $vipLevel;
        return $this;
    }

    public function getLifetimeSpent(): float
    {
        return $this->lifetimeSpent;
    }

    public function setLifetimeSpent(float $lifetimeSpent): self
    {
        $this->lifetimeSpent = $lifetimeSpent;
        return $this;
    }

    public function getBronzeThreshold(): float
    {
        return $this->bronzeThreshold;
    }

    public function setBronzeThreshold(float $threshold): self
    {
        $this->bronzeThreshold = $threshold;
        return $this;
    }

    public function getSilverThreshold(): float
    {
        return $this->silverThreshold;
    }

    public function setSilverThreshold(float $threshold): self
    {
        $this->silverThreshold = $threshold;
        return $this;
    }

    public function getGoldThreshold(): float
    {
        return $this->goldThreshold;
    }

    public function setGoldThreshold(float $threshold): self
    {
        $this->goldThreshold = $threshold;
        return $this;
    }
}
