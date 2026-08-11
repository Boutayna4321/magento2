<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Api\Data;

/**
 * VIP customer status for a given customer.
 *
 * @api
 */
interface VipStatusInterface
{
    /**
     * Get customer id.
     *
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * Set customer id.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Get VIP level.
     *
     * @return string
     */
    public function getVipLevel(): string;

    /**
     * Set VIP level.
     *
     * @param string $vipLevel
     * @return $this
     */
    public function setVipLevel(string $vipLevel): self;

    /**
     * Get lifetime spent.
     *
     * @return float
     */
    public function getLifetimeSpent(): float;

    /**
     * Set lifetime spent.
     *
     * @param float $lifetimeSpent
     * @return $this
     */
    public function setLifetimeSpent(float $lifetimeSpent): self;

    /**
     * Get bronze threshold.
     *
     * @return float
     */
    public function getBronzeThreshold(): float;

    /**
     * Set bronze threshold.
     *
     * @param float $threshold
     * @return $this
     */
    public function setBronzeThreshold(float $threshold): self;

    /**
     * Get silver threshold.
     *
     * @return float
     */
    public function getSilverThreshold(): float;

    /**
     * Set silver threshold.
     *
     * @param float $threshold
     * @return $this
     */
    public function setSilverThreshold(float $threshold): self;

    /**
     * Get gold threshold.
     *
     * @return float
     */
    public function getGoldThreshold(): float;

    /**
     * Set gold threshold.
     *
     * @param float $threshold
     * @return $this
     */
    public function setGoldThreshold(float $threshold): self;
}
