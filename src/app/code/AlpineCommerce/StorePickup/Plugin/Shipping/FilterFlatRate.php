<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Plugin\Shipping;

use Magento\OfflineShipping\Model\Carrier\Flatrate;
use Magento\Quote\Model\Quote\Address\RateRequest;

class FilterFlatRate
{
    private const FREE_SHIPPING_THRESHOLD = 50.0;

    public function aroundCollectRates(
        Flatrate $subject,
        \Closure $proceed,
        RateRequest $request
    ) {
        $packageValue = (float) $request->getPackageValueWithDiscount();
        if ($packageValue >= self::FREE_SHIPPING_THRESHOLD) {
            return false;
        }

        return $proceed($request);
    }
}
