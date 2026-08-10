<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require '/var/www/html/app/bootstrap.php';

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(State::class);
$state->setAreaCode('adminhtml');

$class = 'AlpineCommerce\StoreLocator\Ui\DataProvider\StoreListingDataProvider';
$p = $om->create($class, [
    'name' => 'alphacommerce_store_locator_store_listing_data_source',
    'primaryFieldName' => 'entity_id',
    'requestFieldName' => 'entity_id',
    'meta' => [],
    'data' => [],
]);
$data = $p->getData();
echo "totalRecords: " . ($data['totalRecords'] ?? '?') . "\n";
echo "items: " . json_encode($data['items'] ?? [], JSON_PRETTY_PRINT) . "\n";
