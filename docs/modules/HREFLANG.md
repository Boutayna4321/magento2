# AlpineCommerce_Hreflang Module — SEO Hreflang Tags

> **Status**: 🔄 In finalization (v1.0.0)

## 1. Responsibility

Automatic generation of **hreflang tags** (multi-store SEO): `<link rel="alternate" hreflang="...">`
tags for each store's pages.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Automatic generation** | Hreflang tags injected on pages |
| **Multi-store** | Store view support |
| **Admin configuration** | Activation and setup |
| **i18n** | French translation |

## 3. Architecture

```
AlpineCommerce/Hreflang/
├── Model/                      # tag generator + hreflang logic
├── (Plugin/Block)              # injection into page head
└── etc/
    └── system.xml              # admin configuration
```

## 4. Database

No dedicated table (configuration in `core_config_data`).

## 5. REST API

None.

## 6. Admin

- System configuration (activation, domains per store view)

## 7. Frontend

- `<link rel="alternate" hreflang="xx-XX">` tags generated automatically in the
  `<head>` of pages (one per store view), according to configuration

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Automatic generation (plugin/observer on head) | No core template change |
| Configuration per store view | URL → language mapping specific to each store |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Complete finalization (fine configuration, SEO tests) | 📋 v1.1 — `ROADMAP.md` |

## 11. Magento concepts taught

- Multi-store SEO (hreflang)
- System configuration per store view
- Markup injection into `<head>` (plugin/block)

## 12. Validation & status

- **Status**: 🔄 In finalization — global validation OK (Sprint 6)

---

*Sources: `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
