<?php
declare(strict_types=1);

namespace AlpineCommerce\Hreflang\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Data for the Hreflang admin information page.
 */
class SettingsInfo implements ArgumentInterface
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Active store views, formatted as "Name (code)".
     *
     * @return string[]
     */
    public function getActiveStoreLabels(): array
    {
        $labels = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($store->getIsActive()) {
                $labels[] = $store->getName() . ' (' . $store->getCode() . ')';
            }
        }

        return $labels;
    }
}
