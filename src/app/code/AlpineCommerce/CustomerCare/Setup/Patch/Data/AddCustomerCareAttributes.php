<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Setup\Patch\Data;

use AlpineCommerce\CustomerCare\Model\Attribute\Source\CustomerType;
use AlpineCommerce\CustomerCare\Model\Attribute\Source\VipLevelSource;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Adds customer EAV attributes used by Customer Care.
 *
 * - customer_type     : select (B2C / B2B / Wholesale)
 * - customer_notes    : textarea (admin only)
 * - vip_level         : select (None / Bronze / Silver / Gold) — computed
 * - lifetime_spent    : decimal — computed from completed orders
 */
class AddCustomerCareAttributes implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory
    ) {
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->addCustomerType($customerSetup);
        $this->addCustomerNotes($customerSetup);
        $this->addVipLevel($customerSetup);
        $this->addLifetimeSpent($customerSetup);
    }

    private function addCustomerType(CustomerSetup $setup): void
    {
        $setup->addAttribute(Customer::ENTITY, 'customer_type', [
            'type' => 'varchar',
            'label' => 'Customer Type',
            'input' => 'select',
            'source' => CustomerType::class,
            'required' => false,
            'default' => CustomerType::B2C,
            'sort_order' => 100,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
            'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            'adminhtml_only' => false,
        ]);
    }

    private function addCustomerNotes(CustomerSetup $setup): void
    {
        $setup->addAttribute(Customer::ENTITY, 'customer_notes', [
            'type' => 'text',
            'label' => 'Internal Notes',
            'input' => 'textarea',
            'required' => false,
            'sort_order' => 110,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'used_in_forms' => ['adminhtml_customer'],
            'adminhtml_only' => true,
        ]);
    }

    private function addVipLevel(CustomerSetup $setup): void
    {
        $setup->addAttribute(Customer::ENTITY, 'vip_level', [
            'type' => 'varchar',
            'label' => 'VIP Level',
            'input' => 'select',
            'source' => VipLevelSource::class,
            'required' => false,
            'default' => 'none',
            'sort_order' => 120,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
            'used_in_forms' => ['adminhtml_customer'],
            'adminhtml_only' => true,
        ]);
    }

    private function addLifetimeSpent(CustomerSetup $setup): void
    {
        $setup->addAttribute(Customer::ENTITY, 'lifetime_spent', [
            'type' => 'decimal',
            'label' => 'Lifetime Spent',
            'input' => 'text',
            'required' => false,
            'sort_order' => 130,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
            'used_in_forms' => ['adminhtml_customer'],
            'adminhtml_only' => true,
        ]);
    }
}
