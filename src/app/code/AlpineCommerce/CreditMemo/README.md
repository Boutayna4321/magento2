# AlpineCommerce_CreditMemo

Auto Credit Memo module for Magento 2. Automatically creates credit memos when orders are cancelled.

## Features

- Auto-create credit memo when order is cancelled
- Filter by payment method
- Optional auto-refund to payment gateway
- Admin dashboard to view auto-generated credit memos

## Installation

```bash
bin/magento module:enable AlpineCommerce_CreditMemo
bin/magento setup:upgrade
bin/magento cache:clean
```

## Configuration

Go to **Stores → Configuration → Sales → Auto Credit Memo**:

- **Enable Auto Credit Memo**: Yes/No
- **Payment Methods**: Comma-separated list (empty = all methods)
- **Auto Refund to Payment Gateway**: Yes/No

## Events

- `sales_order_cancel_after` - Creates credit memo automatically
