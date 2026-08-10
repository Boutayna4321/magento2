# AlpineCommerce_LegalPages Module — Dynamic Legal Pages

> **Status**: ✅ Stable (v1.2.0)

## 1. Responsibility

Dynamic and manageable legal pages: **T&C, ToS, privacy policy,
legal notices**. Admin CRUD, public REST API, frontend with listing and detail view.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Page types** | T&C, ToS, privacy, legal notices |
| **Admin CRUD** | Listing + form (rich content by type) |
| **Public REST API** | Page exposure |
| **Frontend** | `/legal` route — listing + detail view |

## 3. Architecture

```
AlpineCommerce/LegalPages/
├── Api/                        # Service Contracts
├── Block/Frontend/             # Listing / detail
├── Controller/                 # Frontend + Adminhtml
├── Model/                      # Entity, repository, ResourceModel/Collection
├── Ui/DataProvider/FormDataProvider.php
└── view/adminhtml/ui_component/ legal_page_form.xml
```

## 4. Database

Legal pages table (type, content, status).

## 5. REST API

Public API for exposing legal pages.

## 6. Admin

- CRUD of pages by type (T&C, ToS, privacy, legal notices)
- `legal_page_form` form: fixed (dataProvider `FormDataProvider`, `page_id` +
  replacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- `/legal` route: listing + detail view (HTTP 200)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Content stored in DB, dynamic pages | Legal texts evolve without deployment |
| Frontend listing + detail | Standard pattern for content modules |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | `legal_page_form`: "class required" exception + `button-set` (empty form) | ✅ Fixed (Sprint 6 addendum) |

## 11. Magento concepts taught

- UI Component form + `ButtonProviderInterface`
- Frontend/admin routes, ACL

## 12. Validation & status

- **Status**: ✅ Stable — form validated on screen (Sprint 6)

---

*Sources: `docs/08_CHANGELOG.md` (v1.2.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
