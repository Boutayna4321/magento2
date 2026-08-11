<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the Customer Care system configuration.
 */
class Config
{
    public const XML_PATH_ENABLED = 'customercare/vip/enabled';
    public const XML_PATH_BRONZE_THRESHOLD = 'customercare/vip/bronze_threshold';
    public const XML_PATH_SILVER_THRESHOLD = 'customercare/vip/silver_threshold';
    public const XML_PATH_GOLD_THRESHOLD = 'customercare/vip/gold_threshold';

    public const ATTR_VIP_LEVEL = 'vip_level';
    public const ATTR_LIFETIME_SPENT = 'lifetime_spent';
    public const ATTR_CUSTOMER_TYPE = 'customer_type';
    public const ATTR_CUSTOMER_NOTES = 'customer_notes';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $websiteId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            $websiteId ? ScopeInterface::SCOPE_WEBSITE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }

    public function getBronzeThreshold(?int $websiteId = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_BRONZE_THRESHOLD,
            $websiteId ? ScopeInterface::SCOPE_WEBSITE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }

    public function getSilverThreshold(?int $websiteId = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_SILVER_THRESHOLD,
            $websiteId ? ScopeInterface::SCOPE_WEBSITE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }

    public function getGoldThreshold(?int $websiteId = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_GOLD_THRESHOLD,
            $websiteId ? ScopeInterface::SCOPE_WEBSITE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }
}
