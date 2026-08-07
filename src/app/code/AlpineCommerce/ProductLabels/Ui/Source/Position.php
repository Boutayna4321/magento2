<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Ui\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Position implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'top-left', 'label' => __('Top Left')],
            ['value' => 'top-right', 'label' => __('Top Right')],
            ['value' => 'bottom-left', 'label' => __('Bottom Left')],
            ['value' => 'bottom-right', 'label' => __('Bottom Right')],
        ];
    }
}
