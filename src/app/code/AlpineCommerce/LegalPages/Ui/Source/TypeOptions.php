<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Ui\Source;

use AlpineCommerce\LegalPages\Api\Data\LegalPageInterface;
use Magento\Framework\Data\OptionSourceInterface;

class TypeOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => LegalPageInterface::TYPE_CGV, 'label' => __('Terms and Conditions (CGV)')],
            ['value' => LegalPageInterface::TYPE_MENTIONS, 'label' => __('Legal Notice')],
            ['value' => LegalPageInterface::TYPE_RETURNS, 'label' => __('Returns & Refunds')],
            ['value' => LegalPageInterface::TYPE_PRIVACY, 'label' => __('Privacy Policy')],
            ['value' => LegalPageInterface::TYPE_SHIPPING, 'label' => __('Shipping')],
        ];
    }
}
