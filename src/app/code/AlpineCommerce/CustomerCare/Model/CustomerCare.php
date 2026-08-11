<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model;

use AlpineCommerce\CustomerCare\Api\CustomerCareInterface;
use AlpineCommerce\CustomerCare\Api\Data\VipStatusInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Customer Care service: reads/writes VIP attributes and computes lifetime spend.
 */
class CustomerCare implements CustomerCareInterface
{
    public function __construct(
        private readonly CustomerFactory $customerFactory,
        private readonly CustomerResource $customerResource,
        private readonly ResourceModel\LifetimeSpent $lifetimeSpent,
        private readonly VipLevelCalculator $vipLevelCalculator,
        private readonly Config $config
    ) {
    }

    public function getVipStatus(int $customerId): VipStatusInterface
    {
        $customer = $this->loadCustomer($customerId);
        $status = new VipStatus();
        $status->setCustomerId($customerId)
            ->setVipLevel((string) $customer->getData(Config::ATTR_VIP_LEVEL) ?: VipLevel::NONE)
            ->setLifetimeSpent((float) $customer->getData(Config::ATTR_LIFETIME_SPENT));

        $websiteId = (int) $customer->getWebsiteId();
        $status->setBronzeThreshold($this->config->getBronzeThreshold($websiteId))
            ->setSilverThreshold($this->config->getSilverThreshold($websiteId))
            ->setGoldThreshold($this->config->getGoldThreshold($websiteId));

        return $status;
    }

    public function recalculateVipStatus(int $customerId): VipStatusInterface
    {
        $customer = $this->loadCustomer($customerId);
        $websiteId = (int) $customer->getWebsiteId();

        $lifetime = $this->lifetimeSpent->sumCompletedOrders($customerId);
        $level = $this->vipLevelCalculator->calculate($lifetime, $websiteId);

        $customer->setData(Config::ATTR_LIFETIME_SPENT, $lifetime);
        $customer->setData(Config::ATTR_VIP_LEVEL, $level);
        $this->customerResource->save($customer);

        return $this->getVipStatus($customerId);
    }

    public function recalculateAll(): int
    {
        $customerIds = $this->customerResource->getConnection()
            ->fetchCol($this->customerResource->getConnection()->select()
                ->from($this->customerResource->getTable('customer_entity'), ['entity_id'])
                ->order('entity_id', 'ASC'));

        $updated = 0;
        foreach ($customerIds as $customerId) {
            $this->recalculateVipStatus((int) $customerId);
            $updated++;
        }
        return $updated;
    }

    public function resetAll(): void
    {
        $connection = $this->customerResource->getConnection();
        $connection->update(
            $this->customerResource->getTable('customer_entity_varchar'),
            ['value' => VipLevel::NONE],
            ['attribute_id = (?)' => $this->getAttributeId(Config::ATTR_VIP_LEVEL)]
        );
    }

    private function loadCustomer(int $customerId): \Magento\Customer\Model\Customer
    {
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $customerId);
        if (!$customer->getId()) {
            throw new NoSuchEntityException(
                __('No such entity with customerId = %1', $customerId)
            );
        }
        return $customer;
    }

    private function getAttributeId(string $attributeCode): int
    {
        $connection = $this->customerResource->getConnection();
        $entityTypeId = (int) $connection->fetchOne(
            $connection->select()
                ->from($this->customerResource->getTable('eav_entity_type'), ['entity_type_id'])
                ->where('entity_type_code = ?', \Magento\Customer\Model\Customer::ENTITY)
        );
        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->customerResource->getTable('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', $attributeCode)
                ->where('entity_type_id = ?', $entityTypeId)
        );
    }
}
