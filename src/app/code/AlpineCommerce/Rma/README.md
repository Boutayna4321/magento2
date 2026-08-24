# AlpineCommerce_Rma

RMA (Return Merchandise Authorization) module for Magento 2. Manages product return requests from customers.

## Features

- Customers can request returns from their account
- Configurable return period (days after order)
- Admin approval workflow
- RMA number auto-generation
- Customer and admin notifications
- Return reasons configuration
- Admin dashboard to manage return requests

## Installation

```bash
bin/magento module:enable AlpineCommerce_Rma
bin/magento setup:upgrade
bin/magento cache:clean
```

## Configuration

Go to **Stores → Configuration → Sales → RMA (Returns)**:

- **Enable RMA**: Yes/No
- **Allow Returns Within (Days)**: Number of days after order
- **Require Admin Approval**: Yes/No
- **Auto Generate RMA Number**: Yes/No
- **RMA Number Prefix**: Prefix for RMA numbers
- **Notify Customer on Status Change**: Yes/No
- **Notify Admin on New Return Request**: Yes/No

## Frontend URL

- Customer return request: `/rma/request`

## Admin Menu

- Sales → RMA
