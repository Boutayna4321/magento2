<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Adminhtml\Label;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Store\Model\StoreRepository;
use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Grid extends Extended
{
    private readonly CollectionFactory $collectionFactory;
    private readonly StoreRepository $storeRepository;
    private readonly TimezoneInterface $timezone;

    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        StoreRepository $storeRepository,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeRepository = $storeRepository;
        $this->timezone = $timezone;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->setId("productLabelGrid");
        $this->setDefaultSort("entity_id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection(): self
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): self
    {
        $this->addColumn("entity_id", [
            "header" => __("ID"),
            "index" => "entity_id",
            "type" => "number",
            "header_css_class" => "col-id",
            "column_css_class" => "col-id"
        ]);

        $this->addColumn("name", [
            "header" => __("Label Name"),
            "index" => "name"
        ]);

        $this->addColumn("code", [
            "header" => __("Code"),
            "index" => "code"
        ]);

        $this->addColumn("color", [
            "header" => __("Color"),
            "index" => "color",
            "renderer" => \Magento\Backend\Block\Widget\Grid\Renderer\Color::class,
            "filter" => false
        ]);

        $this->addColumn("priority", [
            "header" => __("Priority"),
            "index" => "priority",
            "type" => "number"
        ]);

        $this->addColumn("position", [
            "header" => __("Position"),
            "index" => "position"
        ]);

        $this->addColumn("is_active", [
            "header" => __("Status"),
            "index" => "is_active",
            "type" => "options",
            "options" => ["1" => __("Enabled"), "0" => __("Disabled")]
        ]);

        $this->addColumn("start_date", [
            "header" => __("Start Date"),
            "index" => "start_date",
            "type" => "datetime",
            "timezone" => $this->timezone
        ]);

        $this->addColumn("end_date", [
            "header" => __("End Date"),
            "index" => "end_date",
            "type" => "datetime",
            "timezone" => $this->timezone
        ]);

        $this->addColumn("edit", [
            "header" => __("Edit"),
            "type" => "action",
            "getter" => "entity_id",
            "actions" => [
                [
                    "caption" => __("Edit"),
                    "url" => ["base" => "*/*/edit"],
                    "field" => "entity_id"
                ]
            ],
            "filter" => false,
            "sortable" => false,
            "header_css_class" => "col-action",
            "column_css_class" => "col-action"
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction(): self
    {
        $this->setMassactionIdField("entity_id");
        $this->getMassactionBlock()->setFormFieldName("selected");

        $statuses = ["1" => __("Enabled"), "0" => __("Disabled")];
        $this->getMassactionBlock()->addItem(
            "status",
            [
                "label" => __("Change status"),
                "url" => $this->getUrl("productlabels/label/massStatus"),
                "additional" => [
                    "status" => ["name" => "status", "type" => "select", "class" => "required-entry", "label" => __("Status"), "values" => $statuses]
                ]
            ]
        );

        $this->getMassactionBlock()->addItem(
            "delete",
            [
                "label" => __("Delete"),
                "url" => $this->getUrl("productlabels/label/massDelete"),
                "confirm" => __("Are you sure?")
            ]
        );

        return $this;
    }

    public function getGridUrl(): string
    {
        return $this->getUrl("*/*/grid", ["_current" => true]);
    }
}
