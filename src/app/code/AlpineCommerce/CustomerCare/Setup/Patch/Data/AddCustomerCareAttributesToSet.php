<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Adds the Customer Care attributes to the default customer attribute set.
 *
 * EavSetup::addAttribute only auto-assigns a set when the attribute is not
 * user-defined; without set membership, EAV saves silently drop the value.
 */
class AddCustomerCareAttributesToSet implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory
    ) {
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
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $setId = (int) $customerSetup->getDefaultAttributeSetId(Customer::ENTITY);
        if (!$setId) {
            return;
        }
        $groupId = (int) $customerSetup->getDefaultAttributeGroupId(Customer::ENTITY, $setId);

        foreach (['customer_type', 'customer_notes', 'vip_level', 'lifetime_spent'] as $code) {
            if (!$customerSetup->getAttributeId(Customer::ENTITY, $code)) {
                continue;
            }
            $customerSetup->addAttributeToSet(Customer::ENTITY, $setId, $groupId, $code);
        }
    }
}
