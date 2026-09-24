<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Theme implements OptionSourceInterface
{
    public const AUTO = 'auto';
    public const LIGHT = 'light';
    public const DARK = 'dark';
    public const VALUES = [self::AUTO, self::LIGHT, self::DARK];

    public function toOptionArray(): array
    {
        return [
            ['value' => self::AUTO, 'label' => __('Auto')],
            ['value' => self::LIGHT, 'label' => __('Light')],
            ['value' => self::DARK, 'label' => __('Dark')],
        ];
    }
}
