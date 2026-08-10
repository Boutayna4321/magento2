# AlpineCommerce_ProductQuestions Module — Product Questions

> **Status**: ✅ Stable (v1.1.0)

## 1. Responsibility

Product questions/answers: display on the product page, admin moderation,
exposure via REST.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Frontend product page** | Questions block (`QuestionList`) |
| **Admin** | Moderation form (`question_question_form`) |
| **REST** | Questions/answers REST services |

## 3. Architecture

```
AlpineCommerce/ProductQuestions/
├── Api/                        # Service Contracts + AnswerSearchResultsInterface
├── Block/Frontend/QuestionList.php  # use SortOrder (Sprint 6 fix)
├── Model/
│   ├── Status.php               # getLabel(): string (match cast) + OptionSourceInterface
│   └── Rest/                    # REST services
├── Ui/
│   ├── DataProvider/QuestionFormDataProvider.php
│   └── Source/Status.php        # fix C4
├── etc/
│   ├── di.xml                   # AnswerSearchResultsInterface preference (Sprint 6 fix)
│   └── frontend/routes.xml      # fix C6 (created — missing frontend route)
└── view/adminhtml/ui_component/ question_question_form.xml
```

## 4. Database

Questions and answers tables (standardized).

## 5. REST API

Questions/answers REST services (pattern `QuestionRestService`/`ReviewRestService`,
`UserContextInterface::getUserId()`).

## 6. Admin

- `question_question_form` form: fixed (dataProvider `QuestionFormDataProvider`,
  `question_id` + replacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- Product page: questions block — **frontend route added** (fix C6, HTTP 200)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| `AnswerSearchResultsInterface` → `AnswerSearchResults` preference | Fatal "Cannot instantiate interface" (di:compile) |
| `(string)` cast in `Status::getLabel()` | `Phrase` → `string` (PHP 8.2 strict_types) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| C4 | `Ui/Source/Status.php`: wrong namespace (compile fatal) | ✅ Fixed (Phase 1) |
| C5 | `question_question_form.xml`: `</item>` never closed (malformed XML) | ✅ Fixed (Phase 1) |
| C6 | `etc/frontend/routes.xml` missing → 404 | ✅ Fixed (Phase 1) — file created |
| — | `AnswerSearchResultsInterface` without implementation | ✅ Fixed (Sprint 6) — di.xml preference |
| — | `QuestionList` `use SortOrder` missing (fatal) | ✅ Fixed (Sprint 6) |
| — | `question_question_form`: "class required" exception + `button-set` | ✅ Fixed (Sprint 6 addendum) |

## 11. Magento concepts taught

- Frontend routes (`etc/frontend/routes.xml`)
- `di.xml` preferences for SearchResults
- UI Component form + `ButtonProviderInterface`

## 12. Validation & status

- **Status**: ✅ Stable — form validated on screen (Sprint 6)

---

*Sources: `SPRINT_VALIDATION_REPORT.md`, `SPRINT_INTEGRATION_REPORT.md`
(merged into `CHANGELOG.md`).*
