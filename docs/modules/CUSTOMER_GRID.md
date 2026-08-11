# AlpineCommerce_CustomerGrid Module — Customers Grid

> **Status**: ✅ Done — module enabled, compiled, and grid verified live

## 1. Responsibility

Customise the admin **Customers** listing (`Customers > All Customers`) so it
matches the shop's operational needs, **without touching Magento core**. The
module re-declares the relevant columns of the native `customer_listing`
UI component and forces their visibility and label.

## 2. Scope & features

### Included (v1.0)

| Feature | Description |
|---|---|
| **Hide `dob`** | Date of Birth column hidden (`<visible>false</visible>`) |
| **Hide `taxvat`** | Tax VAT Number column hidden |
| **Hide `gender`** | Gender column hidden |
| **Relabel `billing_telephone`** | Shown as "Phone" with a text filter |

### Assumed exclusions

- No new columns added, no data-provider change, no new fields in the
  customer entity.
- Column **ordering / visibility** per admin user (the UI `columns controls`
  / bookmarks) still applies on top of the defaults set here.

## 3. Architecture

```
AlpineCommerce/CustomerGrid/
├── registration.php
├── etc/
│   └── module.xml                        # sequence: Magento_Customer, Magento_Ui
└── view/
    └── adminhtml/
        └── ui_component/
            └── customer_listing.xml      # column override (label + visibility)
```

The module ships **no PHP classes**. It only declares the module and merges a
UI component XML override into the native `customer_listing`. Magento's UI
component config merging concatenates `view/*/ui_component/*.xml` from every
enabled module (later modules win per-element), so re-declaring the columns
with the same `name` and `<settings>` is enough — no core file is edited.

## 4. Database

None. No schema, no tables, no data.

## 5. REST API

None.

## 6. Admin

`Customers > All Customers` — the listing now defaults to:

- **Phone** column (was `Billing Telephone`) with a text filter, sortOrder 60.
- **Date of Birth**, **Tax VAT Number**, **Gender** hidden (sortOrder 170/180/190).

Existing admin bookmarks may still show a previously-saved column layout;
resetting the user's grid bookmark (or using the `Columns` control) applies the
new defaults.

## 7. Frontend

None.

## 8. CLI

```bash
php bin/magento module:enable AlpineCommerce_CustomerGrid
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## 9. Architecture decisions

- **Override instead of edit**: re-declaring `customer_listing.xml` columns is
  upgrade-safe — core files stay pristine and the behaviour survives
  `vendor/` updates.
- **No preferences/plugins**: a pure UI-component override carries zero
  runtime cost and no DI interference.
- **Re-assert `<visible>false</visible>`**: even though core already marks
  these columns as optional, an explicit `visible=false` guarantees they are
  hidden out of the box regardless of theme or bookmark state.

## 10. Known bugs / limitations

- A user's **saved grid bookmark** can override the new visibility until the
  bookmark is reset.
- Relabelling affects the column header globally in the admin; labels are
  not translated for non-English admin locales.

## 11. Magento concepts taught

- UI component **config merging** (`ui_component/*.xml`, later modules win)
- `Magento_Ui` listing (`listing` > `listing_top` > `columns`)
- Column settings: `label`, `sortOrder`, `visible`, `filter`
- Module sequence (`Magento_Customer`, `Magento_Ui`) in `module.xml`
- Upgrade-safe customisation of a core admin grid without core edits

## 12. Validation & status

- `module:enable AlpineCommerce_CustomerGrid` — OK
- `setup:upgrade` — OK (nothing to import)
- `setup:di:compile` — OK
- `cache:flush` — OK
- **Live verification** (admin, `admin` user): the merged `customer_listing`
  component was loaded through `UiComponentFactory` in `adminhtml` area —
  `dob`, `taxvat`, `gender` resolve to `visible=false`; `billing_telephone`
  resolves to label **Phone**. Core `customer_listing.xml` declares none of
  these `visible` settings, so the `false` values can only come from this module.
