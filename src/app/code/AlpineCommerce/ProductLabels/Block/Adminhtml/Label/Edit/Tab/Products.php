<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Adminhtml\Label\Edit\Tab;

use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Registry;

class Products extends Extended
{
    public function __construct(
        Context $context,
        Data $backendHelper,
        private readonly ProductFactory $productFactory,
        private readonly Registry $coreRegistry,
        private readonly ProductLabelRepositoryInterface $labelRepository,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('productLabelProductGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection(): self
    {
        $collection = $this->productFactory->create()->getCollection();
        $collection->addAttributeToSelect(['name', 'sku']);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): self
    {
        $this->addColumn('product_ids', [
            'header' => __('Select'),
            'index' => 'entity_id',
            'type' => 'checkbox',
            'field_name' => 'product_ids[]',
            'values' => $this->getSelectedProducts(),
            'sortable' => false,
            'filter' => false,
            'header_css_class' => 'col-select',
            'column_css_class' => 'col-select',
        ]);

        $this->addColumn('entity_id', ['header' => __('ID'), 'index' => 'entity_id', 'type' => 'number']);
        $this->addColumn('name', ['header' => __('Name'), 'index' => 'name']);
        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);

        return parent::_prepareColumns();
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('productlabels/label/edit', ['entity_id' => $this->getLabelId()]);
    }

    /**
     * @return int[]
     */
    private function getSelectedProducts(): array
    {
        $submitted = $this->getRequest()->getPost('product_ids');
        if (is_array($submitted)) {
            return array_map('intval', $submitted);
        }

        $labelId = $this->getLabelId();
        if (!$labelId) {
            return [];
        }

        return $this->labelRepository->getProductIdsByLabel($labelId);
    }

    private function getLabelId(): int
    {
        $label = $this->coreRegistry->registry('productlabels_label');
        return $label ? (int) $label->getEntityId() : 0;
    }
}
