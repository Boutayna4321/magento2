<?php
declare(strict_types=1);

namespace Cartware\Training\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Psr\Log\LoggerInterface;

class CreateStores implements DataPatchInterface
{
    private $setup;
    private $storeFactory;
    private $storeResource;
    private $config;
    private $cacheTypeList;
    private $logger;

    public function __construct(
        ModuleDataSetupInterface $setup,
        StoreFactory $storeFactory,
        StoreResource $storeResource,
        ReinitableConfigInterface $config,
        TypeListInterface $cacheTypeList,
        LoggerInterface $logger
    ) {
        $this->setup = $setup;
        $this->storeFactory = $storeFactory;
        $this->storeResource = $storeResource;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
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
        $this->setup->startSetup();

        $this->createStore('french', 'French Store View', 1, 1, 3);
        $this->createStore('german', 'German Store View', 1, 1, 4);
        $this->createStore('spanish', 'Spanish Store View', 1, 1, 5);
        $this->createStore('default_fr', 'Default French', 1, 1, 6);

        $this->config->reinit();
        $this->cacheTypeList->cleanType('config');

        $this->setup->endSetup();
    }

    private function createStore(
        string $code,
        string $name,
        int $websiteId,
        int $groupId,
        int $sortOrder
    ): void {
        $existing = $this->storeFactory->create()->load($code, 'code');
        if ($existing->getId()) {
            $this->logger->info("Training DataPatch: Store '$code' already exists (ID: {$existing->getId()})");
            $this->assignTheme($existing->getId(), $code);
            return;
        }

        $store = $this->storeFactory->create();
        $store->setData('name', $name);
        $store->setData('code', $code);
        $store->setData('website_id', $websiteId);
        $store->setData('group_id', $groupId);
        $store->setData('is_active', 1);
        $store->setData('sort_order', $sortOrder);
        $this->storeResource->save($store);

        $this->logger->info("Training DataPatch: Created store '$code' (ID: {$store->getId()})");
        $this->assignTheme($store->getId(), $code);
    }

    private function assignTheme(int $storeId, string $storeCode): void
    {
        $connection = $this->setup->getConnection();
        $configTable = $this->setup->getTable('core_config_data');
        $themeTable = $this->setup->getTable('theme');

        $themeId = $connection->fetchOne(
            $connection->select()->from($themeTable, ['theme_id'])->where('code = ?', 'Cartware/Training')
        );

        if (!$themeId) {
            $this->logger->info("Training DataPatch: Theme 'Cartware/Training' not found - skipping assignment for $storeCode");
            return;
        }

        $existing = $connection->fetchOne(
            $connection->select()->from($configTable, ['config_id'])
                ->where('scope = ?', 'stores')
                ->where('scope_id = ?', $storeId)
                ->where('path = ?', 'design/theme/theme_id')
        );

        if ($existing) {
            $connection->update(
                $configTable,
                ['value' => $themeId],
                ['scope = ?' => 'stores', 'scope_id = ?' => $storeId, 'path = ?' => 'design/theme/theme_id']
            );
            $this->logger->info("Training DataPatch: Theme assigned for '$storeCode' - updated");
        } else {
            $connection->insert($configTable, [
                'scope' => 'stores',
                'scope_id' => $storeId,
                'path' => 'design/theme/theme_id',
                'value' => $themeId,
            ]);
            $this->logger->info("Training DataPatch: Theme assigned for '$storeCode' - created");
        }
    }
}
