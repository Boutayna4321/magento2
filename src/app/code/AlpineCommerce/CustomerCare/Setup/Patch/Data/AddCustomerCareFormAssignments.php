<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Assigns the Customer Care attributes to the admin/frontend customer forms.
 *
 * Magento only populates customer_form_attribute for its default entities, so
 * custom attributes must be wired to forms manually.
 */
class AddCustomerCareFormAssignments implements DataPatchInterface
{
    public function __construct(private readonly ModuleDataSetupInterface $moduleDataSetup)
    {
    }

    public static function getDependencies(): array
    {
        return [AddCustomerCareAttributes::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $formTable = $this->moduleDataSetup->getTable('customer_form_attribute');

        $customerEntityTypeId = (int) $connection->fetchOne(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('eav_entity_type'), ['entity_type_id'])
                ->where('entity_type_code = ?', Customer::ENTITY)
        );
        if (!$customerEntityTypeId) {
            return;
        }

        $formMap = [
            'customer_type' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            'customer_notes' => ['adminhtml_customer'],
            'vip_level' => ['adminhtml_customer'],
            'lifetime_spent' => ['adminhtml_customer'],
        ];

        foreach ($formMap as $code => $forms) {
            $attributeId = (int) $connection->fetchOne(
                $connection->select()
                    ->from($this->moduleDataSetup->getTable('eav_attribute'), ['attribute_id'])
                    ->where('attribute_code = ?', $code)
                    ->where('entity_type_id = ?', $customerEntityTypeId)
            );
            if (!$attributeId) {
                continue;
            }
            foreach ($forms as $formCode) {
                $connection->insertOnDuplicate($formTable, [
                    'form_code' => $formCode,
                    'attribute_id' => $attributeId,
                ]);
            }
        }
    }
}
