<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class CustomerType extends AbstractSource
{
    public const B2C = 'b2c';
    public const B2B = 'b2b';
    public const WHOLESALE = 'wholesale';

    public function getAllOptions(): array
    {
        return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => self::B2C, 'label' => __('B2C (Individual)')],
            ['value' => self::B2B, 'label' => __('B2B (Business)')],
            ['value' => self::WHOLESALE, 'label' => __('Wholesale')],
        ];
    }
}
