<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Plugin\Shipping;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\OfflineShipping\Model\Carrier\Freeshipping;
use Magento\Quote\Model\Quote\Address\RateRequest;

class FilterFreeShipping
{
    private const FREE_SHIPPING_THRESHOLD = 50.0;

    public function __construct(
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function aroundCollectRates(
        Freeshipping $subject,
        \Closure $proceed,
        RateRequest $request
    ) {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote) {
            return $proceed($request);
        }

        $grandTotal = (float) $quote->getGrandTotal();
        if ($grandTotal < self::FREE_SHIPPING_THRESHOLD) {
            return false;
        }

        return $proceed($request);
    }
}
