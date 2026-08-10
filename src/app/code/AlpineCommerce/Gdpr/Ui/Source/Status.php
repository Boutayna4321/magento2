<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Ui\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Granted')],
            ['value' => '0', 'label' => __('Revoked')],
        ];
    }
}
