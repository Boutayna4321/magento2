# AlpineCommerce_Gdpr Module — GDPR Compliance

> **Status**: 🔄 Code done — Magento validation pending (finalization sprint: Sprint 1)

## 1. Responsibility

Manage **GDPR compliance** for the platform: consent logging,
personal data export (right to portability, Art. 15) and deletion
(right to be forgotten, Art. 17). Magento does not offer a native GDPR module in Open Source.

## 2. Scope & features

### Included (v1.0)

| Feature | Description | Priority |
|---|---|---|
| **Admin consent listing** | Admin interface listing all consents (customer, date, type, IP, status) | Critical |
| **GDPR admin export** | "Export" button in the listing → triggers `GdprExportInterface` for a customer (Art. 15) | High |
| **Granular ACL** | `consent_log`, `export`, `config` (separate because export is a sensitive action) | High |
| **Admin menu** | "GDPR > Consent Log" entry under `Magento_Backend::content` | High |
| **Existing business core** | Logging, export, delete, REST API, console commands (pre-existing, unchanged) | — |

### Assumed exclusions (v1.1)

- **Admin anonymization** (Art. 17): console commands are sufficient in v1.0
- **System configuration**: values hardcoded
- **Export access logging**: planned for v1.1

## 3. Architecture

```
AlpineCommerce/Gdpr/
├── etc/
│   ├── module.xml / db_schema.xml / di.xml / webapi.xml   # EXISTING — unchanged
│   ├── acl.xml                    # Created (Sprint 1) — consent_log, export, config
│   └── adminhtml/menu.xml         # Created (Sprint 1)
├── Controller/Adminhtml/ConsentLog/
│   ├── Index.php                  # Created — listing (Faq pattern)
│   └── Export.php                 # Created — admin export
├── Ui/
│   ├── DataProvider/ConsentLogListingDataProvider.php     # Created — AbstractDataProvider
│   └── Component/Listing/Column/Actions.php               # Created — Export column
└── view/adminhtml/
    ├── layout/alphacommerce_gdpr_consentlog_index.xml
    └── ui_component/alphacommerce_gdpr_consent_log_listing.xml
```

**Golden rule**: do not touch the existing business core. The admin interface relies on
the Service Contracts (`ConsentLogRepositoryInterface`, `GdprExportInterface`) without modifying them.

## 4. Database

| Table | Role |
|---|---|
| `alphacommerce_gdpr_consent_log` | Consent logs (customer_id, consent_type, status, ip_address, created_at) |

No schema modification in Sprint 1 (pre-existing table).

## 5. REST API

| Route | Method | Auth | Role |
|---|---|---|---|
| `/V1/alphacommerce/gdpr/consent` | POST | anonymous | Record a consent |
| `/V1/alphacommerce/gdpr/export` | GET | Mixed | Export a customer's data |
| `/V1/alphacommerce/gdpr/delete` | DELETE | Mixed | Delete/anonymize data |

5 Service Contracts (`ConsentManagementInterface`, `ConsentLogRepositoryInterface`,
`GdprExportInterface`, `GdprDeleteInterface`, `GdprRestInterface`).

## 6. Admin

- **ACL**: `AlpineCommerce_Gdpr::main` (parent) > `consent_log`, `export`, `config`
- **Menu**: GDPR under `Magento_Backend::content`, `sortOrder=90`
- **Listing**: 2.4.8 compliant UI Component (custom `AbstractDataProvider`,
  `textRange`/`select`/`date` filters, Actions column with Export button + confirmation)
- **Safety net**: route protected by `_isAllowed()` → 403 if not authorized

## 7. Frontend

No specific frontend (actions via REST on client side). IP data is sensitive
and accessible only to admins with ACL `consent_log`.

## 8. CLI

| Command | Role |
|---|---|
| `alphacommerce:gdpr:export <customer_id>` | Export a customer's data (positional argument) |
| `alphacommerce:gdpr:delete <customer_id>` | Delete a customer's data |

> ⚠️ CLI help (`--help`) is misleading: actual usage is the positional argument
> (see BACKLOG B-06 P8).

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Do not extend business scope | The core (log, export, delete) already exists and is covered by REST API |
| Add only the missing admin interface | Listing + export trigger + ACL |
| Keep console commands | Operational alternative (e.g. Art. 17 anonymization deferred to v1.1) |
| v1.1 exclusions | Admin anonymization + system configuration deferred (console commands sufficient) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| D1 | `Controller/Adminhtml/ConsentLog/Export.php`: PHP 8.2 fatal `readonly` (collision `AbstractAction::$resultFactory`) | ✅ Fixed (Phase 1) |
| — | `GdprDeleteService` does not anonymize order addresses/emails (Art. 17 incomplete) | 📋 BACKLOG B-06 P4 |
| — | Misleading CLI export help | 📋 BACKLOG B-06 P8 |

## 11. Magento concepts taught

- Service Contracts (5 interfaces exposed via REST)
- UI Component `<listing>` + custom DataProvider (`AbstractDataProvider`)
- Hierarchical ACL + admin menu
- Admin controller (Faq pattern) + export action (JSON Response)
- Custom action column with confirmation (`UrlInterface`)

## 12. Validation & status

- **Finalization sprint**: Sprint 1 (analysis `14`-`15`, architecture `16`)
- **Magento validation**: pending — non-regression tests (REST consent,
  CLI export/delete, admin) are part of global validation (Sprint 5)
- Known environment issues: non-blocking

---

*Sources: docs `14_SPRINT_CAHIER_DES_CHARGES_GDPR.md`, `15_SPRINT_ANALYSE_GDPR.md`,
`16_SPRINT_ARCHITECTURE_GDPR.md` (merged here), archive `17_SPRINT_REPORT_GDPR.md`.*
