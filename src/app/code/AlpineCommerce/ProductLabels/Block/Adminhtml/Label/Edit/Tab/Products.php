<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Adminhtml\Label\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Registry;

class Products extends Extended
{
    private readonly ProductFactory $productFactory;
    private readonly Registry $coreRegistry;

    public function __construct(
        Context $context,
        Data $backendHelper,
        ProductFactory $productFactory,
        Registry $registry,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        $this->coreRegistry = $registry;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct(): void {
        parent::_construct();
        $this->setId("productLabelProductGrid");
        $this->setDefaultSort("entity_id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection(): self {
        $collection = $this->productFactory->create()->getCollection();
        $label = $this->coreRegistry->registry("productlabels_label");
        if ($label && $label->getEntityId()) {
            $collection->addFieldToFilter("entity_id", ["in" => $label->getProductIds() ?? []]);
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): self {
        $this->addColumn("entity_id", ["header" => __("ID"), "index" => "entity_id", "type" => "number"]);
        $this->addColumn("name", ["header" => __("Name"), "index" => "name"]);
        $this->addColumn("sku", ["header" => __("SKU"), "index" => "sku"]);
        return parent::_prepareColumns();
    }

    public function getGridUrl(): string { return $this->getUrl("productlabels/label/productGrid", ["_current" => true]); }
}
