# AlpineCommerce_Blog Module — Multi-store Blog

> **Status**: ✅ Stable (v1.1.0)

## 1. Responsibility

Multi-store blog: management of **categories** and **posts**, with admin CRUD
interface, public REST API, and frontend pages (listing + detail view).

## 2. Scope & features

| Feature | Description |
|---|---|
| **Categories** | Management of post categories |
| **Posts** | Full CRUD with status |
| **Public REST API** | Exposure of posts/categories |
| **Frontend** | Listing + detail view |
| **Admin** | Listing + edit form (post & category) |

## 3. Architecture

```
AlpineCommerce/Blog/
├── Api/                        # Service Contracts + SearchResults
├── Block/
│   ├── Frontend/               # Frontend listing / detail
│   └── Adminhtml/Post/Edit/    # GenericButton, SaveButton, BackButton (button pattern)
├── Controller/
│   ├── Frontend/               # /blog routes
│   └── Adminhtml/Post/         # Admin CRUD
├── Model/                      # Entities, repositories, ResourceModel/Collection
├── Ui/DataProvider/            # PostFormDataProvider, CategoryFormDataProvider
└── view/
    ├── adminhtml/ui_component/ blog_post_form.xml, blog_category_form.xml
    └── frontend/               # templates + layouts
```

## 4. Database

Blog tables (categories, posts, multi-store links). Standardized schema
(table and column names normalized in v1.1).

## 5. REST API

Public API for exposing posts and categories.

## 6. Admin

- Listing + post and category edit forms
- `ButtonProviderInterface` buttons (pattern created for Sprint 6 fix)

## 7. Frontend

- `/blog` route: listing + detail view (HTTP 200)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| `ButtonProviderInterface` button pattern | Fixes the missing `button-set` component in 2.4.8 (root cause of empty form) |
| `<dataProvider class="...">` mandatory child | `definition.map.xml` (module-ui) requires this node (XPath) to build the `dataProvider` argument |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| C12 | `blog_post_form.xml`: button classes `Edit\*` non-existent (fatal) | ✅ Fixed — GenericButton/SaveButton/BackButton created |
| — | `blog_category_form.xml`: "class required" exception (missing dataProvider) + `button-set` | ✅ Fixed (Sprint 6 addendum) — dataProvider `CategoryFormDataProvider` (`category_id`) + `<settings><buttons>` |

## 11. Magento concepts taught

- UI Component form (`dataProvider`, `js_config`)
- `ButtonProviderInterface` + form `Toolbar`
- `requestFieldName`/`primaryFieldName`

## 12. Validation & status

- **Status**: ✅ Stable — forms validated on screen (Sprint 6)
- Reference module for the form button pattern (`button-set` fix)

---

*Sources: `docs/08_CHANGELOG.md` (v1.1.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
