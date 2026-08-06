<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Adminhtml\Label\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
    protected function _construct(): void
    {
        parent::_construct();
        $this->setId("productlabels_label_edit_tabs");
        $this->setDestElementId("content");
    }

    protected function _prepareLayout(): self
    {
        $this->addTab("label_information", [
            "label" => __("Label Information"),
            "title" => __("Label Information"),
            "active" => true,
            "content" => $this->getChildHtml("form")
        ]);

        $this->addTab("related_products", [
            "label" => __("Related Products"),
            "title" => __("Related Products"),
            "content" => $this->getChildHtml("products_tab")
        ]);

        return $this;
    }
}
