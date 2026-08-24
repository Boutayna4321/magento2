# AlpineCommerce_PartialInvoice

Partial Invoice module for Magento 2. Automatically creates partial invoices when orders are placed.

## Features

- Auto-create partial invoice for available items only
- Skip items that are out of stock / backordered
- Configurable minimum quantity per item
- Optional backorder allowance
- Filter by payment method
- Admin dashboard to view partial invoices

## Installation

```bash
bin/magento module:enable AlpineCommerce_PartialInvoice
bin/magento setup:upgrade
bin/magento cache:clean
```

## Configuration

Go to **Stores → Configuration → Sales → Partial Invoice**:

- **Enable Partial Invoice**: Yes/No
- **Payment Methods**: Comma-separated list (empty = all methods)
- **Allow Backorders**: Yes/No
- **Minimum Qty to Invoice**: Minimum quantity per item to invoice

## Events

- `checkout_onepage_controller_success_action` - Creates partial invoice automatically

## Compatibility

Works alongside AutoInvoice module. If AutoInvoice creates a full invoice first, PartialInvoice will skip.
