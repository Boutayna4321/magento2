<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Ui\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConsentType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'cookies', 'label' => __('Cookies')],
            ['value' => 'newsletter', 'label' => __('Newsletter')],
            ['value' => 'terms', 'label' => __('Terms')],
            ['value' => 'privacy', 'label' => __('Privacy')],
        ];
    }
}
