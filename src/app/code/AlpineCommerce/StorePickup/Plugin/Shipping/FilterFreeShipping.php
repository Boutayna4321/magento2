<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Plugin\Shipping;

use Magento\OfflineShipping\Model\Carrier\Freeshipping;
use Magento\Quote\Model\Quote\Address\RateRequest;

class FilterFreeShipping
{
    private const FREE_SHIPPING_THRESHOLD = 50.0;

    public function aroundCollectRates(
        Freeshipping $subject,
        \Closure $proceed,
        RateRequest $request
    ) {
        $packageValue = (float) $request->getPackageValueWithDiscount();
        if ($packageValue < self::FREE_SHIPPING_THRESHOLD) {
            return false;
        }

        return $proceed($request);
    }
}
