# AlpineCommerce_AutoInvoice

Automatically creates invoices when orders are placed in Magento 2.

## Features

- Auto-create invoice on order placement
- Filter by payment method
- Enable/disable per website/store view
- Admin UI for configuration and status
- Detailed logging

## Installation

```bash
bin/magento module:enable AlpineCommerce_AutoInvoice
bin/magento setup:upgrade
bin/magento cache:clean
```

## Configuration

1. Go to **Stores → Configuration → Sales → Auto Invoice**
2. Set **Enable Auto Invoice** to `Yes`
3. Optionally specify payment methods (comma separated): `checkmo, banktransfer`
4. Save configuration

## Usage

Once enabled, the module automatically creates invoices for all new orders.

### Admin Menu

- **Sales → Auto Invoice** — View module status and recent orders

### Events

The module listens to `checkout_onepage_controller_success_action` and creates invoices automatically.

## Requirements

- Magento 2.4.x
- PHP 8.1+
- MySQL 8.0+

## License

MIT
