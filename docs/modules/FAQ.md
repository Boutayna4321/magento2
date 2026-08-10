# AlpineCommerce_Faq Module — FAQ

> **Status**: ✅ Stable (v1.2.0) — **canonical module** for UI Component patterns

## 1. Responsibility

"Frequently Asked Questions" page: admin management of questions/answers (CRUD),
exposure via public REST API, and frontend display (listing + detail view). Serves as a
**reference module** for the project's UI Component patterns.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Admin CRUD** | Listing + question/answer form |
| **Public REST API** | FAQ exposure |
| **Frontend** | `/faq` route — listing + detail view |
| **Reference pattern** | Listing, form, DataProvider, buttons (model for other modules) |

## 3. Architecture

```
AlpineCommerce/Faq/
├── Api/                        # Service Contracts + SearchResults
├── Block/Frontend/             # Listing / detail
├── Controller/                 # Frontend + Adminhtml
├── Model/                      # Entity, repository, ResourceModel/Collection
├── Ui/DataProvider/FaqFormDataProvider.php
└── view/adminhtml/ui_component/ faq_faq_listing.xml, faq_faq_form.xml
```

## 4. Database

Main questions/answers table (standardized schema).

## 5. REST API

Public API for exposing FAQ entries.

## 6. Admin

- `faq_faq_listing` listing — ⚠️ **6 admin listings XSD-invalid (well-formed)**:
  `<massAction>` (wrong case), `<deps>` text, `<primaryDataSource>`, `<param>`
  in massaction, `<options>` inline — including `faq_faq_listing` (see `BACKLOG.md` B-01,
  **blocking v1.0**)
- `faq_faq_form` form: fixed (dataProvider `FaqFormDataProvider`, `faq_id` +
  replacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- `/faq` route: listing + detail view (HTTP 200)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Canonical module for UI Component patterns | The listing/form patterns were cloned from Faq to other modules |
| `button-set` fix applied first on Faq | Test page for the root cause of empty form (2.4.8) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| B-01 | 6 listings XSD-invalid (including `faq_faq_listing`) — `<massAction>`, `<deps>` text, `<primaryDataSource>`, `<param>` massaction, `<options>` inline | 📋 **Blocking v1.0** — BACKLOG B-01 |
| — | `faq_faq_form`: "class required" exception + `button-set` (empty form) | ✅ Fixed (Sprint 6 addendum) |

## 11. Magento concepts taught

- UI Component `<listing>` (XSD structure, dataProvider)
- UI Component `<form>` (dataProvider, `ButtonProviderInterface` buttons)
- Admin/frontend routes + ACL

## 12. Validation & status

- **Status**: ✅ Stable — form validated on screen (Sprint 6)
- **Remaining blocker**: XSD-invalid listings (B-01) to fix in Phase 2

---

*Sources: `docs/08_CHANGELOG.md` (v1.2.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
