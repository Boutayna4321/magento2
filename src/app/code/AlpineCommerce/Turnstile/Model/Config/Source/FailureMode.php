<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FailureMode implements OptionSourceInterface
{
    public const CLOSED = 'closed';
    public const OPEN = 'open';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::CLOSED, 'label' => __('Closed (reject the form)')],
            ['value' => self::OPEN, 'label' => __('Open (accept without verification)')],
        ];
    }
}
