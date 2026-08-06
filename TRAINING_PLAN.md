# Magento 2.4.8 Advanced Training Plan

## Environment
- **URL:** http://localhost:8080
- **Admin:** http://localhost:8080/admin (admin / admin123)
- **MySQL (DBeaver):** localhost:3306 / magento / magento123
- **Elasticsearch:** localhost:9200

---

## Module 1: Multi-Store Setup (2-3 days)

### Concept
Magento supports **multiple websites**, **stores**, and **store views** under one installation.
- **Website** = isolated scope (separate customer accounts, orders)
- **Store** = catalog scope (shared catalog per website)
- **Store View** = language/currency scope

### Tasks

#### 1.1 Create Store Structure
```
Default Website
├── Default Store (English)
└── French Store (French)

Morocco Website
├── Morocco Store (Arabic)
└── Morocco Store (French)
```

**Admin path:** Stores > Settings > All Stores > Create Store / Create Store View

#### 1.2 Create Store Views
- Create "French" store view under Default Website
- Create "Arabic" store view under Morocco Website

#### 1.3 Assign Different Themes Per Store View
- Admin > Content > Design > Configuration
- Assign different theme per store view (or use Blank/Luma)

#### 1.4 Configure Store View Specific Settings
```bash
# Per store view config
bin/magento config:set web/unsecure/base_url http://localhost:8080/ --scope=stores --scope-id=2
bin/magento config:set general/locale/code fr_FR --scope=stores --scope-id=2
bin/magento config:set currency/options/default EUR --scope=stores --scope-id=2
bin/magento config:set general/country/default MA --scope=websites --scope-id=2
```

#### 1.5 Create Store-Specific CMS Pages
- Create a homepage CMS page for French store
- Create a homepage CMS page for Arabic store
- Admin > Content > Pages > click on page > edit > Store View tab

#### 1.6 Verify
- Switch store view from frontend header dropdown
- Check different URLs per store
- Check different currencies

### Key Concepts to Understand
- Scope hierarchy: Global > Website > Store > Store View
- `core_config_data` table: how config differs per scope
- `store` and `store_group` tables in DBeaver
- Base URL vs Secure Base URL per store view

---

## Module 2: Multi-Source Inventory (MSI) (3-4 days)

### Concept
MSI decouples inventory from a single "default stock" to **multiple sources** + **stocks**.
- **Source** = physical location (warehouse, store, dropshipper)
- **Stock** = virtual aggregation of sources per sales channel (website)
- **Salable Quantity** = stock - reserved items (items in carts/orders)

### Tasks

#### 2.1 Create Sources
Admin > Stores > Inventory > Sources

| Source Code | Name | City | Country |
|-------------|------|------|---------|
| warehouse_marrakech | Warehouse Marrakech | Marrakech | Morocco |
| warehouse_casablanca | Warehouse Casablanca | Casablanca | Morocco |
| store_front | Store Front | Paris | France |
| dropship_uk | Dropship UK | London | UK |

#### 2.2 Create Stocks
Admin > Stores > Inventory > Stocks

| Stock Name | Website(s) Assigned |
|------------|---------------------|
| Morocco Stock | Default Website |
| France Stock | Default Website |
| UK Stock | Default Website |

#### 2.3 Assign Sources to Stocks
- Morocco Stock → warehouse_marrakech, warehouse_casablanca
- France Stock → store_front
- UK Stock → dropship_uk

#### 2.4 Configure Stock Priority
- Each stock has a priority (1 = highest)
- Magento checks source with highest priority first

#### 2.5 Set Product Source Quantities
Admin > Catalog > Products > edit product > Sources tab

For each product:
| Product | Source | Qty | Status |
|---------|--------|-----|--------|
| Simple 1 | warehouse_marrakech | 100 | In Stock |
| Simple 1 | warehouse_casablanca | 50 | In Stock |
| Simple 1 | store_front | 20 | In Stock |
| Simple 1 | dropship_uk | 0 | Out of Stock |

#### 2.6 Test Salable Quantity
- Check salable quantity in admin
- Add to cart → watch reserved qty increase
- Place order → watch stock decrease
- Check DBeaver: `inventory_source_stock`, `inventory_source_item` tables

#### 2.7 Configure Source Selection Algorithm
- Admin > Stores > Configuration > Catalog > Inventory
- Source Selection Algorithm: "Prioritized" or "Source Priority"

#### 2.8 Test Stock Per Website
- Assign different stocks to different websites
- Verify frontend shows correct salable quantity per store view

### Key Concepts to Understand
- `inventory_source_item` table: actual qty per source
- `inventory_reservation` table: items reserved in carts/orders
- Salable quantity = SUM(source qty) - SUM(reservations)
- How MSI interacts with order processing (Source Selection Algorithm)

---

## Module 3: Payment Methods (2-3 days)

### Concept
Magento has built-in payment methods and supports extensions. Each payment method has:
- **Config model** (`ConfigInterface`)
- **Gateway** (actual payment processing)
- **Renderer** (frontend UI component)

### Tasks

#### 3.1 Configure Built-in Methods

**Check/Money Order:**
```bash
bin/magento config:set payment/checkmo/active 1
bin/magento config:set payment/checkmo/title "Check / Money Order"
bin/magento config:set payment/checkmo/order_status processing
```

**Bank Transfer:**
```bash
bin/magento config:set payment/banktransfer/active 1
bin/magento config:set payment/banktransfer/title "Bank Transfer"
```

**Purchase Order:**
```bash
bin/magento config:set payment/purchaseorder/active 1
bin/magento config:set payment/purchaseorder/title "Purchase Order"
```

**Free Shipping (payment):**
```bash
bin/magento config:set payment/free/active 1
bin/magento config:set payment/free/title "Free"
```

#### 3.2 Test Each Payment Method
- Add product to cart
- Go to checkout
- Verify each payment method appears
- Place order with each method
- Check order status in admin

#### 3.3 Check Payment Tables in DBeaver
```sql
-- Payment methods configured
SELECT * FROM core_config_data WHERE path LIKE 'payment/%';

-- Payment info per order
SELECT * FROM sales_payment_transaction;

-- Order payment details
SELECT o.entity_id, o.increment_id, pi.method, pi.po_number 
FROM sales_order o 
JOIN sales_order_payment pi ON o.entity_id = pi.parent_id;
```

#### 3.4 Create Custom Payment Module (Advanced)
Create a simple custom payment module `Training_SimplePayment`:

```bash
mkdir -p src/app/code/Training/SimplePayment/{Block,etc,Model,view/frontend}
```

**etc/module.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Training_SimplePayment">
        <sequence>
            <module name="Magento_Checkout"/>
        </sequence>
    </module>
</config>
```

**etc/config.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <training_simplepayment>
            <active>0</active>
            <title>Cash on Delivery</title>
            <order_status>pending_payment</order_status>
            <sort_order>50</sort_order>
        </training_simplepayment>
    </default>
</config>
```

**etc/di.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <virtualType name="TrainingSimplePaymentConfig" type="Magento\Payment\Model\Config">
        <arguments>
            <argument name="methodCodes" xsi:type="array">
                <item name="training_simplepayment" xsi:type="string">training_simplepayment</item>
            </argument>
            <argument name="defaultConfig" xsi:type="array">
                <item name="training_simplepayment" xsi:type="array">
                    <item name="model" xsi:type="string">Training\SimplePayment\Model\PaymentMethod</item>
                </item>
            </argument>
        </arguments>
    </virtualType>
</config>
```

**Model/PaymentMethod.php:**
```php
<?php
namespace Training\SimplePayment\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class PaymentMethod extends AbstractMethod
{
    protected $_code = 'training_simplepayment';
    protected $_isOffline = true;
    protected $_canOrder = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
}
```

**etc/frontend/di.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Magento\Checkout\Model\CompositeConfigProvider">
        <arguments>
            <argument name="configProviders" xsi:type="array">
                <item name="training_simplepayment" xsi:type="object">Training\SimplePayment\Model\Payment\ConfigProvider</item>
            </argument>
        </arguments>
    </type>
</config>
```

**Model/Payment/ConfigProvider.php:**
```php
<?php
namespace Training\SimplePayment\Model\Payment;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public function getCode()
    {
        return 'training_simplepayment';
    }

    public function getAvailableMethods()
    {
        return [$this->getCode()];
    }

    public function getConfig()
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'title' => 'Cash on Delivery',
                    'description' => 'Pay when you receive your order',
                ],
            ],
        ];
    }
}
```

**view/frontend/web/js/view/payment/method-renderer.js:**
```javascript
define([
    'Magento_Checkout/js/view/payment/default',
    'jquery'
], function (Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Training_SimplePayment/payment/simple'
        },
        getTitle: function () {
            return 'Cash on Delivery';
        }
    });
});
```

**view/frontend/web/template/payment/simple.html:**
```html
<div class="payment-method" data-bind="attr: {'id': 'payment_method_' + getCode()}">
    <div class="payment-method-title field choice">
        <input type="radio"
               class="radio"
               data-bind="attr: {'id': getCode(), 'value': getCode(), 'name': 'payment[method]'}"
               />
        <label data-bind="attr: {'for': getCode()}">
            <span data-bind="text: getTitle()"></span>
        </label>
    </div>
    <div class="payment-method-content">
        <p>Cash on Delivery - Pay when your order arrives.</p>
        <div class="actions-toolbar">
            <div class="primary">
                <button class="action primary checkout"
                        data-bind="click: placeOrder, attr: {title: $t('Place Order')}"
                        type="button">
                    <span data-bind="i18n: 'Place Order'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
```

### After creating the module:
```bash
docker exec magento2-php bash -c "
  php bin/magento module:enable Training_SimplePayment &&
  php bin/magento setup:upgrade &&
  php bin/magento setup:di:compile &&
  php bin/magento setup:static-content:deploy -f &&
  php bin/magento cache:flush &&
  chown -R www-data:www-data /var/www/html/var /var/www/html/generated /var/www/html/pub/static
"
```

### Key Concepts to Understand
- Payment method lifecycle: authorize → capture → refund
- Payment gateway interface vs offline payment
- `sales_order_payment` table
- How payment methods appear in checkout UI (RequireJS + KnockoutJS)

---

## Module 4: Shipping Methods (2-3 days)

### Concept
Shipping in Magento has:
- **Carriers** (shipping method providers)
- **Shipping rates** (price calculation)
- **Shipping labels** (optional)
- **Table Rates** (CSV-based rules)

### Tasks

#### 4.1 Configure Built-in Shipping Methods

**Flat Rate:**
```bash
bin/magento config:set shipping/methods/flatrate/active 1
bin/magento config:set shipping/methods/flatrate/title "Flat Rate"
bin/magento config:set shipping/methods/flatrate/type O
bin_magento config:set shipping/methods/flatrate/price 5.00
```

**Free Shipping:**
```bash
bin/magento config:set shipping/methods/freeshipping/active 1
bin/magento config:set shipping/methods/freeshipping/title "Free Shipping"
bin/magento config:set shipping/methods/freeshipping/free_shipping_subtract 1
```

**Table Rates:**
```bash
bin/magento config:set shipping/methods/tablerates/active 1
bin/magento config:set shipping/methods/tablerates/title "Table Rates"
```

Export CSV and edit:
```
Country,Region/State,Zip/Postal Code,Weight (and above),Shipping Price
*,*,*,0,5.00
*,*,*,10,10.00
*,*,*,20,15.00
```

Import back via Admin > Stores > Configuration > Sales > Shipping Methods > Table Rates > Import

#### 4.2 Configure Table Rates CSV
Admin > Stores > Configuration > Sales > Shipping Methods
1. Set "Condition" to "Weight vs. Destination"
2. Export CSV
3. Edit in spreadsheet
4. Import back

#### 4.3 Test Each Method
```bash
# Check available shipping methods
bin/magento shipping:methods
```

- Add product with weight to cart
- Go to checkout, enter different addresses
- Verify shipping methods appear correctly
- Test free shipping threshold

#### 4.4 Check Shipping in Database
```sql
-- Shipping configuration
SELECT * FROM core_config_data WHERE path LIKE 'shipping/%';

-- Order shipping info
SELECT o.entity_id, o.increment_id, o.shipping_description, 
       o.shipping_amount, o.shipping_method
FROM sales_order o;

-- Quote shipping
SELECT q.entity_id, q.shipping_method, q.shipping_description,
       q.shipping_amount
FROM sales_flat_quote q;
```

#### 4.5 Create Custom Shipping Module (Advanced)
Create `Training_CustomShipping` - a shipping method based on delivery time:

```
src/app/code/Training/CustomShipping/
├── Block/
│   └── Adminhtml/
│       └── System/
│           └── Config/
│               └── Source/
│                   └── DeliveryTimes.php
├── Model/
│   └── Carrier.php
├── etc/
│   ├── config.xml
│   ├── di.xml
│   ├── module.xml
│   └── system.xml
└── view/
    └── frontend/
        └── web/
            └── template/
                └── shipping/
                    └── custom.phtml
```

**Model/Carrier.php:**
```php
<?php
namespace Training\CustomShipping\Model;

use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\RateResult\ErrorFactory as RateErrorFactory;
use Magento\Shipping\Model\RateResult\MethodFactory;

class Carrier extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'training_custom';
    protected $_isFixed = false;
    
    private $rateResultFactory;
    private $rateMethodFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        RateResult\ErrorFactory $rateResultErrorFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultErrorFactory;
        $this->rateMethodFactory = $rateMethodFactory;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();

        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod('standard');
        $method->setMethodTitle('Standard Delivery (3-5 days)');

        $price = $this->getConfigData('price');
        $method->setPrice($price);
        $method->setCost($price);

        $result->append($method);

        return $result;
    }

    public function getAllowedMethods()
    {
        return ['standard' => 'Standard Delivery'];
    }
}
```

**etc/module.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Training_CustomShipping">
        <sequence>
            <module name="Magento_Shipping"/>
        </sequence>
    </module>
</config>
```

**etc/config.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <carriers>
            <training_custom>
                <active>1</active>
                <sallowspecific>0</sallowspecific>
                <model>Training\CustomShipping\Model\Carrier</model>
                <name>Training Custom Shipping</name>
                <title>Custom Shipping</title>
                <price>10.00</price>
                <sort_order>5</sort_order>
            </training_custom>
        </carriers>
    </default>
</config>
```

**etc/di.xml:**
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Magento\Shipping\Model\CarrierFactory">
        <arguments>
            <argument name="configurations" xsi:type="array">
                <item name="training_custom" xsi:type="string">Training\CustomShipping\Model\Carrier</item>
            </argument>
        </arguments>
    </type>
</config>
```

### Key Concepts to Understand
- Shipping carrier interface
- Rate collection lifecycle
- `shipping_tablerates` CSV format
- How shipping integrates with order processing
- `core_config_data` shipping config rows

---

## Module 5: Full Integration Project (3-4 days)

### Task: Complete Store Setup
Combine all modules into a working multi-store with MSI + payment + shipping:

```
Morocco Website
├── Store: Marrakech (Arabic)
│   ├── Source: warehouse_marrakech (100 items)
│   ├── Payment: Check/Money Order
│   └── Shipping: Flat Rate 15 MAD
│
├── Store: Casablanca (French)
│   ├── Source: warehouse_casablanca (50 items)
│   ├── Payment: Bank Transfer
│   └── Shipping: Free Shipping (orders > 200 MAD)

Default Website
├── Store: UK (English)
│   ├── Source: dropship_uk (200 items)
│   ├── Payment: Custom COD
│   └── Shipping: Custom Shipping 10 GBP
```

### Steps
1. Create websites, stores, store views
2. Create sources and assign stocks
3. Set product quantities per source
4. Configure payment methods per website
5. Configure shipping methods per website
6. Test checkout flow per store
7. Verify orders in admin and DBeaver

### SQL Queries to Verify
```sql
-- All store hierarchy
SELECT w.code AS website, s.name AS store, sv.name AS store_view
FROM store_website w
JOIN store s ON w.website_id = s.website_id
JOIN store_group sg ON s.group_id = sg.group_id
JOIN store sv ON sg.group_id = sv.group_id;

-- Inventory per source
SELECT si.sku, si.source_code, si.quantity, si.status
FROM inventory_source_item si
ORDER BY si.sku, si.source_code;

-- Stock per website
SELECT st.name AS stock, st.website_ids
FROM inventory_stock st;

-- Recent orders with payment + shipping
SELECT o.increment_id, o.status, o.payment_method, o.shipping_method,
       o.grand_total, o.store_id
FROM sales_order o
ORDER BY o.created_at DESC;
```

---

## Commands Cheat Sheet

```bash
# Enter PHP container
docker exec -it magento2-php bash

# From inside container:
php bin/magento cache:flush
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento module:enable Training_CustomShipping
php bin/magento module:status

# Fix permissions after changes:
docker exec magento2-php bash -c "chown -R www-data:www-data /var/www/html/var /var/www/html/generated /var/www/html/pub/static"

# Check logs:
docker logs magento2-php --tail 50
docker logs magento2-nginx --tail 50
```
