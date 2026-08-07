<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Adminhtml\Label;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    protected $_blockGroup = 'AlpineCommerce_ProductLabels';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        $this->_objectId = 'entity_id';
        $this->_controller = 'label';
        $this->_moduleName = 'AlpineCommerce_ProductLabels';
        parent::_construct();
    }

    public function getHeaderText(): string
    {
        $labelRegistry = $this->_coreRegistry->registry('productlabels_label');
        if ($labelRegistry && $labelRegistry->getEntityId()) {
            return __('Edit Product Label') . ' - ' . $labelRegistry->getName();
        }
        return __('New Product Label');
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/');
    }

    public function getSaveAndContinueUrl(): string
    {
        return $this->getUrl('*/*/edit', ['_current' => true, 'entity_id' => $this->getRequest()->getParam('entity_id')]);
    }
}
