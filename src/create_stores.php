<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/html/app/bootstrap.php';

try {
    $bootstrap = Bootstrap::create(BP, $_SERVER);
    $objectManager = $bootstrap->getObjectManager();
    
    $state = $objectManager->get(\Magento\Framework\App\State::class);
    $state->setAreaCode('adminhtml');
    
    $websiteFactory = $objectManager->get(\Magento\Store\Model\WebsiteFactory::class);
    $groupFactory = $objectManager->get(\Magento\Store\Model\GroupFactory::class);
    $storeFactory = $objectManager->get(\Magento\Store\Model\StoreFactory::class);
    $resourceWebsite = $objectManager->get(\Magento\Store\Model\ResourceModel\Website::class);
    $resourceGroup = $objectManager->get(\Magento\Store\Model\ResourceModel\Group::class);
    $resourceStore = $objectManager->get(\Magento\Store\Model\ResourceModel\Store::class);

    // Check if morocco website already exists
    $existingWebsite = $objectManager->create(\Magento\Store\Model\Website::class)->load('morocco', 'code');
    if ($existingWebsite->getId()) {
        echo "Morocco Website already exists (ID: " . $existingWebsite->getId() . "), skipping...\n";
        $websiteId = $existingWebsite->getId();
    } else {
        echo "1. Creating Morocco Website...\n";
        $website = $websiteFactory->create();
        $website->setData('name', 'Morocco Website');
        $website->setData('code', 'morocco');
        $website->setData('is_active', 1);
        $website->setData('default_group_id', 0);
        $resourceWebsite->save($website);
        $websiteId = $website->getId();
        echo "   Website ID: $websiteId\n";
    }

    // Check if group already exists
    $existingGroup = $objectManager->create(\Magento\Store\Model\Group::class)->load('Morocco Store Group', 'name');
    if ($existingGroup->getId()) {
        echo "Morocco Store Group already exists (ID: " . $existingGroup->getId() . "), skipping...\n";
        $groupId = $existingGroup->getId();
    } else {
        echo "2. Creating Morocco Store Group...\n";
        $group = $groupFactory->create();
        $group->setData('name', 'Morocco Store Group');
        $group->setData('website_id', (int)$websiteId);
        $group->setData('root_category_id', (int)2);
        $group->setData('is_active', 1);
        $resourceGroup->save($group);
        $groupId = $group->getId();
        echo "   Group ID: $groupId\n";

        // Update website default group
        $website->load($websiteId);
        $website->setData('default_group_id', (int)$groupId);
        $resourceWebsite->save($website);
    }

    // Create stores
    $storesToCreate = [
        ['name' => 'Morocco Arabic',  'code' => 'morocco_ar', 'website_id' => $websiteId, 'group_id' => $groupId, 'sort_order' => 1, 'locale' => 'ar_MA'],
        ['name' => 'Morocco French',  'code' => 'morocco_fr', 'website_id' => $websiteId, 'group_id' => $groupId, 'sort_order' => 2, 'locale' => 'fr_FR'],
        ['name' => 'Default French',  'code' => 'default_fr', 'website_id' => 1, 'group_id' => 1, 'sort_order' => 2, 'locale' => 'fr_FR'],
    ];

    foreach ($storesToCreate as $i => $data) {
        $existing = $objectManager->create(\Magento\Store\Model\Store::class)->load($data['code'], 'code');
        if ($existing->getId()) {
            echo ($i + 3) . ". Store '{$data['name']}' already exists (ID: " . $existing->getId() . "), skipping...\n";
            continue;
        }
        echo ($i + 3) . ". Creating {$data['name']}...\n";
        $store = $storeFactory->create();
        $store->setData('name', $data['name']);
        $store->setData('code', $data['code']);
        $store->setData('website_id', (int)$data['website_id']);
        $store->setData('group_id', (int)$data['group_id']);
        $store->setData('is_active', 1);
        $store->setData('sort_order', $data['sort_order']);
        $resourceStore->save($store);
        echo "   Store ID: " . $store->getId() . "\n";
    }

    echo "\n=== DONE ===\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
