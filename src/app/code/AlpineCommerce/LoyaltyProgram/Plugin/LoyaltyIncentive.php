<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Plugin;

use AlpineCommerce\LoyaltyProgram\Service\PointsCalculator;
use Magento\Checkout\Block\Cart\Sidebar;
use Magento\Customer\Model\Session as CustomerSession;

class LoyaltyIncentive
{
    public const CHILD_ALIAS = 'minicart.addons';

    public function __construct(
        private readonly PointsCalculator $pointsHelper,
        private readonly CustomerSession $customerSession
    ) {
    }

    /**
     * Append a loyalty incentive message inside the mini-cart.
     *
     * @param Sidebar $subject
     * @param string $result
     * @param string|null $alias
     * @return string
     */
    public function afterGetChildHtml(Sidebar $subject, string $result, ?string $alias = null): string
    {
        if ($alias !== self::CHILD_ALIAS) {
            return $result;
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result;
        }

        $quote = $subject->getQuote();
        if (!$quote || !$quote->getItemsCount()) {
            return $result;
        }

        $points = max(0, $this->pointsHelper->calculatePoints((float) $quote->getGrandTotal()));
        if ($points <= 0) {
            return $result;
        }

        $message = __('You will earn %1 points with this order', $points);

        return $result
            . '<div class="loyalty-minicart-message">'
            . $subject->escapeHtml($message)
            . '</div>';
    }
}
