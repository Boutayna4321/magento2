<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Block\Adminhtml\Store\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * "Back" button of the store form: returns to the grid.
 */
class BackButton implements ButtonProviderInterface
{
    public function __construct(private readonly Context $context)
    {
    }

    public function getButtonData(): array
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->context->getUrlBuilder()->getUrl('*/*/')),
            'class' => 'back',
            'sort_order' => 10,
        ];
    }
}
