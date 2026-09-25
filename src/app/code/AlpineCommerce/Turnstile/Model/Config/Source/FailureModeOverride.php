<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Per-form failure mode: inherit the general setting, or force closed / open.
 */
class FailureModeOverride implements OptionSourceInterface
{
    public const INHERIT = '';
    public const CLOSED = FailureMode::CLOSED;
    public const OPEN = FailureMode::OPEN;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::INHERIT, 'label' => __('Use General Setting')],
            ['value' => self::CLOSED, 'label' => __('Closed (reject the form)')],
            ['value' => self::OPEN, 'label' => __('Open (accept without verification)')],
        ];
    }
}
