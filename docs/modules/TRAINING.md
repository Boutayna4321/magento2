# AlpineCommerce_Training Module — Training & Demo

> **Status**: 🔄 In finalization (v1.3.0)

## 1. Responsibility

Magento **training and demo** module: event observers, multi-store configuration,
and examples of best practices. **Do not deploy as-is in production**
(the store view creation Data Patch must be removed — see `BACKLOG.md` B-08).

## 2. Scope & features

| Feature | Description |
|---|---|
| **Data Patch** | Store view creation — ⚠️ **to remove** (BACKLOG B-08) |
| **Observers** | On product, order, checkout, customer login |
| **Configuration** | Multi-store |

## 3. Architecture

```
AlpineCommerce/Training/
├── Setup/Patch/Data/           # Data Patch store view creation (⚠️ to remove)
├── Observer/                   # product, order, checkout, customer login
├── etc/system.xml              # multi-store configuration
└── (demo blocks/templates)
```

## 4. Database

No dedicated table. The Data Patch modifies `core_store_group` / `core_store`
(⚠️ see BACKLOG B-08 — to transform into reproducible demo script).

## 5. REST API

None.

## 6. Admin

- System configuration (demo)

## 7. Frontend

- Demo via observers (logs, events)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Data Patch for store views | Illustrates the Data Patch pattern, but **forbidden in production** (B-08) |
| Multiple observers | Pedagogical support for Magento events |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| B-08 | Data Patch creates store views permanently — irreversible/undesirable | 📋 BACKLOG B-08 — remove/transform |

## 11. Magento concepts taught

- **Observers** (product, order, checkout, customer_login)
- **Data Patches** (and their risks in production)
- Multi-store configuration

## 12. Validation & status

- **Status**: 🔄 In finalization — global validation OK (Sprint 6)

---

*Sources: `docs/08_CHANGELOG.md` (v1.3.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
