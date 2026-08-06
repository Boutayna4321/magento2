<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Adminhtml\Label\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

class Form extends Generic
{
    private readonly Store $store;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Store $store, array $data = []) {
        $this->store = $store;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm(): self
    {
        $label = $this->_coreRegistry->registry("productlabels_label");
        $form = $this->_formFactory->create(["data" => ["id" => "edit_form", "action" => $this->getData("action"), "method" => "post"]]);
        $fieldset = $form->addFieldset("base_fieldset", ["legend" => __("Label Information"), "class" => "fieldset-wide"]);
        $fieldset->addField("entity_id", "hidden", ["name" => "entity_id"]);
        $fieldset->addField("name", "text", ["name" => "name", "label" => __("Label Name"), "title" => __("Label Name"), "required" => true]);
        $fieldset->addField("code", "text", ["name" => "code", "label" => __("Code"), "title" => __("Code"), "required" => true, "class" => "validate-alphanum"]);
        $fieldset->addField("color", "text", ["name" => "color", "label" => __("Background Color"), "title" => __("Background Color"), "class" => "color"]);
        $fieldset->addField("text_color", "text", ["name" => "text_color", "label" => __("Text Color"), "title" => __("Text Color"), "class" => "color"]);
        $fieldset->addField("priority", "text", ["name" => "priority", "label" => __("Priority"), "title" => __("Priority"), "class" => "validate-digits", "value" => 0]);
        $fieldset->addField("position", "select", ["name" => "position", "label" => __("Position"), "title" => __("Position"), "values" => [["value" => "top-left", "label" => __("Top Left")], ["value" => "top-right", "label" => __("Top Right")], ["value" => "bottom-left", "label" => __("Bottom Left")], ["value" => "bottom-right", "label" => __("Bottom Right")]]]);
        $fieldset->addField("icon", "text", ["name" => "icon", "label" => __("Icon"), "title" => __("Icon"), "note" => __("Icon URL or CSS class (optional)")]);
        $fieldset->addField("start_date", "date", ["name" => "start_date", "label" => __("Start Date"), "title" => __("Start Date"), "date_format" => "yyyy-MM-dd"]);
        $fieldset->addField("end_date", "date", ["name" => "end_date", "label" => __("End Date"), "title" => __("End Date"), "date_format" => "yyyy-MM-dd"]);
        $fieldset->addField("is_active", "select", ["name" => "is_active", "label" => __("Status"), "title" => __("Status"), "values" => ["1" => __("Enabled"), "0" => __("Disabled")]]);
        $fieldset->addField("store_ids", "multiselect", ["name" => "store_ids[]", "label" => __("Store Views"), "title" => __("Store Views"), "values" => $this->store->getStoreValuesForForm(false, true), "note" => __("Leave empty for all store views")]);

        if ($label && $label->getEntityId()) {
            $form->addValues($label->getData());
        }
        $this->setForm($form);
        return $this;
    }

    public function getTabLabel(): string { return (string)__("Label Information"); }
    public function getTabTitle(): string { return (string)__("Label Information"); }
    public function canShowTab(): bool { return true; }
    public function isHidden(): bool { return false; }
}
