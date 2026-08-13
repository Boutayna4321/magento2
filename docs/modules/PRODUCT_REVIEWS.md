# AlpineCommerce_ProductReviews Module — Product Reviews

> **Status**: ✅ Stable (v1.1.0)

## 1. Responsibility

**Product review** management: display on the product page, admin moderation,
exposure via REST.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Frontend product page** | Reviews display (`ReviewList` block) |
| **Admin** | Moderation form (`review_review_form`) |
| **REST** | Review REST services (`QuestionRestService`/`ReviewRestService` pattern) |

## 3. Architecture

```
AlpineCommerce/ProductReviews/
├── Api/                        # Service Contracts
├── Block/Frontend/ReviewList.php  # use SortOrder (fix C2)
├── Service/ImageProcessor.php   # pure calculation service (replaced Helper)
├── Model/
│   ├── Status.php               # getLabel(): string (match cast — Sprint 6 fix)
│   └── Rest/                    # REST services
├── Ui/
│   ├── DataProvider/ReviewFormDataProvider.php
│   └── Source/Status.php        # Status implements OptionSourceInterface (fix C3)
└── view/adminhtml/ui_component/ review_review_form.xml
```

## 4. Database

Product reviews table (standardized).

## 5. REST API

Review REST services (pattern `QuestionRestService`/`ReviewRestService` — using
`UserContextInterface::getUserId()` after the 2.4.8 fix).

## 6. Admin

- `review_review_form` form: fixed (dataProvider `ReviewFormDataProvider`,
  `review_id` + replacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- Product page: reviews block (HTTP 200)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| `Status implements OptionSourceInterface` | Fixes wrong namespace (fatal di:compile) |
| `getLabel()` cast `(string)` | `Phrase` → `string` (PHP 8.2 strict_types TypeError) |
| Service class replaces Helper | ImageProcessor has no Magento dependencies, pure service |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| C2 | `Block/Frontend/ReviewList.php`: `use SortOrder` missing (fatal) | ✅ Fixed (Phase 1) |
| C3 | `Ui/Source/Status.php`: class in wrong namespace (fatal compile) | ✅ Fixed (Phase 1) |
| — | `getCurrentCustomer()` non-existent in 2.4.8 (API) | ✅ Fixed (Sprint 5) — `UserContextInterface::getUserId()` |
| — | `review_review_form`: "class required" exception + `button-set` | ✅ Fixed (Sprint 6 addendum) |
| — | `Status::getLabel()` `Phrase` → `string` | ✅ Fixed (Sprint 6) |

## 11. Magento concepts taught

- UI Component form + `ButtonProviderInterface`
- `UserContextInterface` (replacement for `getCurrentCustomer()` in 2.4.8)
- `OptionSourceInterface` for listing sources
- `SortOrder` (SearchCriteria)
- Service classes (no Helper anti-pattern)

## 12. Validation & status

- **Status**: ✅ Stable — form validated on screen (Sprint 6)

---

*Sources: `SPRINT_VALIDATION_REPORT.md`, `SPRINT_INTEGRATION_REPORT.md`
(merged into `CHANGELOG.md`).*
