<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model\Attribute\Source;

use AlpineCommerce\CustomerCare\Model\VipLevel;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class VipLevelSource extends AbstractSource
{
    public function getAllOptions(): array
    {
        return [
            ['value' => VipLevel::NONE, 'label' => __('None')],
            ['value' => VipLevel::BRONZE, 'label' => __('Bronze')],
            ['value' => VipLevel::SILVER, 'label' => __('Silver')],
            ['value' => VipLevel::GOLD, 'label' => __('Gold')],
        ];
    }
}
