<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model;

/**
 * VIP level codes. Mirrors the adminhtml select options (see VipLevel source).
 */
class VipLevel
{
    public const NONE = 'none';
    public const BRONZE = 'bronze';
    public const SILVER = 'silver';
    public const GOLD = 'gold';
}
