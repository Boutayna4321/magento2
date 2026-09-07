# AlpineCommerce_Hreflang Module — SEO Hreflang Tags

> **Status**: ✅ Stable (v1.7.0)

## 1. Responsibility

Automatic generation of **hreflang tags** (multi-store SEO): `<link rel="alternate" hreflang="...">`
tags for each store's pages.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Automatic generation** | Hreflang tags injected in `<head>` via layout XML |
| **Multi-store** | One tag per active store view |
| **x-default** | Optional x-default link to default store view |
| **Admin configuration** | Enable/disable + x-default toggle (per store view) |
| **Admin UI** | Settings info page with configuration link |
| **i18n** | French translation |

## 3. Architecture

```
AlpineCommerce/Hreflang/
├── Block/
│   └── Hreflang.php                    # generates alternate links array
├── Controller/
│   └── Adminhtml/
│       └── Settings/
│           └── Index.php               # Admin settings info page
├── etc/
│   ├── acl.xml                          # config, settings
│   ├── adminhtml/
│   │   ├── menu.xml                     # Content → Hreflang
│   │   └── routes.xml                   # frontName: hreflang
│   ├── config.xml                       # defaults: enabled=1, x_default=1
│   ├── module.xml
│   └── system.xml                       # admin config
└── view/
    ├── adminhtml/
    │   ├── layout/
    │   │   └── hreflang_settings_index.xml
    │   └── templates/
    │       └── settings/
    │           └── info.phtml           # Settings info template
    └── frontend/
        ├── layout/
        │   └── default.xml              # injects block into head.additional
        └── templates/
            └── hreflang.phtml           # renders <link> tags
```

## 4. Database

No dedicated table (configuration in `core_config_data`).

## 5. REST API

None.

## 6. Admin

- **Menu**: Content → Hreflang → Settings Info
- **Route**: `/admin/hreflang/settings` (frontName: `hreflang`)
- **Settings Info Page**: Module information, active stores, configuration link
- **Stores > Configuration > General > Hreflang**: enable toggle + x-default toggle (per store view)
- **ACL Resources**: `AlpineCommerce_Hreflang::config`, `AlpineCommerce_Hreflang::settings`

## 7. Frontend

- `<link rel="alternate" hreflang="xx-XX">` tags generated automatically in the
  `<head>` of pages (one per active store view), according to configuration
- Injection via `default.xml` layout XML → `head.additional` container
- Block `Hreflang::getAlternateLinks()` returns array of `hreflang` + `href`

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Layout XML injection (`default.xml`) | No plugin needed — block added to `head.additional` on every page |
| Block-based generation | `Hreflang` block encapsulates all logic (store iteration, URL building, locale conversion) |
| Config per store view | Each store view can enable/disable independently |
| x-default support | Optional link to default store view for undefined locales |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Admin UI | ✅ Done — settings info page |

## 11. Magento concepts taught

- Multi-store SEO (hreflang)
- System configuration per store view
- Layout XML injection into `<head>`
- Block template rendering
- StoreManager + ScopeConfig usage

## 12. Validation & status

- **Status**: ✅ Stable — admin UI completed (v1.7.0)

---

*Sources: `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
