# Official Changelog of the AlpineCommerce Project

All notable changes to the project are documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/en-US/).

> This document combines the former `08_CHANGELOG.md` as well as the root reports
> `SPRINT_VALIDATION_REPORT.md` (Sprint 5 — functional validation) and
> `SPRINT_INTEGRATION_REPORT.md` (Sprint 6 — functional integration).
>
> ⚠️ **Sprint reconciliation**: the module finalization sprints
> (Sprint 1 = Gdpr, 2 = StorePickup, 3 = StoreLocator) and global sprints
> (Sprint 4 = code fixes, Sprint 5 = validation, Sprint 6 = integration) are
> grouped here chronologically. The detailed reports of each sprint are
> archived in `archive/sprints/`.

---

## [1.5.2] - 2026-08-06

### Fixed (admin bug report — Sprint 6, addendum)

- `AlpineCommerce_Blog` — `view/adminhtml/ui_component/blog_category_form.xml`:
  exception `InvalidArgumentException: Node "argument" with name "class" is required for this type`
  on admin page `admin/blog/category/edit`. Cause: the `<dataSource>` had no
  child `<dataProvider class="...">` — `definition.map.xml` (module-ui) builds
  the `dataProvider` argument as `configurableObject` whose class comes from
  `dataProvider/@class` (XPath); without this node, the evaluator
  `Magento\Framework\View\Element\UiComponent\Argument\Interpreter\ConfigurableObject`
  threw the exception at layout rendering.
  Fix: addition of `<dataProvider class="AlpineCommerce\Blog\Ui\DataProvider\CategoryFormDataProvider"
  name="blog_category_form_data_source">` (`requestFieldName`/`primaryFieldName` = `category_id`),
  removal of the redundant `config/dataProvider` item, `js_config` aligned on the
  working Post form.
- **Same cause fixed on 4 other admin forms** (same "class required" exception):

  | uiComponent | dataProvider | ID |
  |---|---|---|
  | `faq_faq_form` | `AlpineCommerce\Faq\Ui\DataProvider\FaqFormDataProvider` | `faq_id` |
  | `legal_page_form` | `AlpineCommerce\LegalPages\Ui\DataProvider\FormDataProvider` | `page_id` |
  | `question_question_form` | `AlpineCommerce\ProductQuestions\Ui\DataProvider\QuestionFormDataProvider` | `question_id` |
  | `review_review_form` | `AlpineCommerce\ProductReviews\Ui\DataProvider\ReviewFormDataProvider` | `review_id` |

### Fixed (empty admin form in browser — root cause `button-set`)

- The page `admin/faq/faq/new|edit` returned valid HTML but the form stayed
  empty in the browser. Root cause: `<container name="button_set"
  component="Magento_Ui/js/form/components/button-set">` — this JS component **does not
  exist in Magento 2.4.8** (only `button.js`, `button-adapter.js` and
  `form/adapter/buttons.js` exist). On load, `Magento_Ui/js/core/app` fails
  on the missing reference → the form structure is never initialized.
- Fix — replacement of the container by `<settings><buttons>` with
  `ButtonProviderInterface` classes (pattern `Blog/Block/Adminhtml/Post/Edit/*`), for the 5
  concerned forms (`faq_faq_form`, `blog_category_form`, `legal_page_form`,
  `question_question_form`, `review_review_form`) + 15 classes created
  (`{GenericButton,SaveButton,BackButton}.php` per module).
  `GenericButton::get{...}Id()` via `getRequest()->getParam('<id>')`; `SaveButton`:
  `actionName=save`, `params=[false]`, `sort_order=90`; `BackButton`: url `*/*/`,
  `sort_order=10`.

### Technical (Sprint 6 — integration)

- `ProductLabels/view/frontend/layout/catalog_product_view.xml`: `referenceContainer`
  → `referenceBlock` for `product.info.media` + `product.info.details` (these are
  `block`, not `container` — labels were never rendered).
- `ProductQuestions/Block/Frontend/QuestionList.php`: `use Magento\Framework\Api\SortOrder;`
  added (fatal `Class SortOrder not found`).
- `ProductQuestions/Model/Status.php` + `ProductReviews/Model/Status.php`: cast
  `(string)` on `match` branches (`getLabel()` returned a `Phrase`, not a `string`
  → `TypeError` under PHP 8.2 + `strict_types=1`).
- `ProductQuestions/etc/di.xml`: `AnswerSearchResultsInterface` preference →
  `AnswerSearchResults` added (fatal `Cannot instantiate interface`).
- **Integration validated** for the 13 modules: frontend (product page: labels,
  reviews, questions; routes `/blog`, `/faq`, `/legal`, `/store-locator`; checkout:
  loyalty + store pickup), admin (CRUD), REST API (GET/POST) and CLI.

### Remaining to address (Phase 2 — see `BACKLOG.md` B-06)

- 6 admin listings XSD-invalid (well-formed): `<massAction>` (wrong case),
  `<deps>` text, `<primaryDataSource>`, `<param>` in massaction, `<options>` inline
- `AlpineCommerce_StorePickup/etc/adminhtml/routes.xml` missing (admin URLs
  `alphacommerce_pickup/*` unresolved)
- `AlpineCommerce_StorePickup/etc/adminhtml/menu.xml`: menu item without `action` attribute

---

## [1.5.1] - 2026-08-06

### Fixed (Phase 1 — 14 critical bugs, Sprint 5 validation)

Targeted review of the 13 modules: 12 critical bugs re-verified then 2 additional PHP
fatals discovered by the compiler. The `setup:di:compile` compilation was
**blocked** (PHP fatal hidden by the progress bar). **14 bugs fixed.**

| # | Module | File(s) | Root cause | Fix |
|---|--------|---------|------------|-----|
| C1 | ProductReviews | `Helper/Image.php` | ctor without `Context` + `parent::__construct` | `Context` injected, `parent::__construct($context)` |
| C2 | ProductReviews | `Block/Frontend/ReviewList.php` | `use Magento\Framework\Api\SortOrder;` missing (fatal) | import added |
| C3 | ProductReviews | `Ui/Source/Status.php` | class in wrong namespace (compile fatal) | rewritten as `Status implements OptionSourceInterface` |
| C4 | ProductQuestions | `Ui/Source/Status.php` | same as C3 | rewritten as `Status implements OptionSourceInterface` |
| C5 | ProductQuestions | `question_question_form.xml` | `</item>` never closed (malformed XML) | closing tag fixed |
| C6 | ProductQuestions | `etc/frontend/routes.xml` | frontend route missing (404) | file created |
| C7 | ProductLabels | `Block/Adminhtml/Label/Grid.php` | `use Magento\Backend\Block\Widget\Grid` (fatal collision), invalid ctor, renderer + non-existent constant | ctor fixed, renderer removed, native massaction |
| C8 | StoreLocator | `alphacommerce_store_locator_store_form.xml` | malformed XML (`optionsclass`, orphaned `<formElements>`, `</label>`, `country_id` missing) | rewritten, XSD-validated |
| C9 | StorePickup | `alphacommerce_pickup_store_info_form.xml` | same as C8 | rewritten, XSD-validated |
| C10 | StoreLocator | `store-locator.phtml:7` | `getSize()` on an `array` return (fatal) | `count($stores)` |
| C11 | StoreLocator + StorePickup | `Controller/Adminhtml/Store/{Save,Delete}.php` (4) | `parent::__construct($context)` while `AbstractAction` requires `PageFactory` (fatal) | `PageFactory` injected + `parent::__construct($context, $pageFactory)` |
| C12 | Blog | `blog_post_form.xml` | button classes `Block\Adminhtml\Post\Edit\*` non-existent (fatal) | `GenericButton`, `SaveButton`, `BackButton` created |
| D1 | Gdpr | `Controller/Adminhtml/ConsentLog/Export.php` | PHP 8.2 fatal "Cannot redeclare non-readonly property ... as readonly" (discovered by di:compile) | promoted property `ResultFactory` removed |
| D2 | StoreLocator | `Model/StoreRepository.php` | `StoreInterfaceFactory` without `use` → `Model\StoreInterfaceFactory` non-existent (discovered by di:compile) | `use Api\Data\StoreInterfaceFactory` added |

### Technical (Sprint 5 — validation)

- `setup:di:compile`: full compilation validated (**EXIT 0**, 4582 classes generated)
  after correcting PHP fatals (the historical blockage "Repositories code generation"
  was the silent Gdpr fatal). `var/generated` permissions restored for php-fpm runtime.
- PHP lint 100% clean (488+ files), XML 100% well-formed, 12/18 `ui_component` XSD-valid.
- **13 modules functionally validated**: installation, DB schema (16 tables), admin,
  frontend (HTTP 200), REST API, CLI — all `PASS`.
- **18 bugs fixed in total** on sprints 4-5, including 4 API-critical bugs in Sprint 5:
  1. `getCurrentCustomer()` non-existent in 2.4.8 (`QuestionRestService`, `ReviewRestService`)
     → replaced by `UserContextInterface::getUserId()` + `getById()`.
  2. Doc blocks `@return`/`@param` missing on 6 Data interfaces (`DataObjectProcessor`
     requires doc blocks for JSON serialization) → added (10 files with SearchResults).
  3. `Status` not imported in `Model\Rest` namespace → `use` added.
  4. Customer ID type mismatch (`string` vs `?int`) in setters → cast `(int)` + `?->`.

### Residual issues (non-blocking — see `BACKLOG.md`)

- Product page 500: Elasticsearch 8.x core bug `_id` fielddata (not an AlpineCommerce bug).
- `self` APIs 401: `recaptcha-webapi-rest` plugin blocks customer-self routes including
  native Magento endpoints (environment issue).
- GDPR `delete` does not anonymize customer data (Art. 17 incomplete) — B-06 P4.
- ProductLabels: N+1 observer — B-06 P5.

---

## [1.5.0] - 2026-08-06

### Added

- `AlpineCommerce_ProductLabels`: manageable product labels
  - Tables: `alphacommerce_product_label`, `alphacommerce_product_label_product`
  - Admin grid (listing, Delete / Change status massactions, "Add New Label" button)
  - Edit form: name, code, colors, priority, position, validity dates, status, product selection
  - REST API: `/V1/alphacommerce/product-labels` (GET/POST), `/:entityId` (GET/DELETE),
    `/:labelId/products` (GET/POST), `/:productId/apply` (POST)
  - Frontend: label rendering on product page and category listings (plugin `CatalogBlock`)
  - French i18n

### Fixed

- Admin grid rewritten in Magento 2.4.8 format (removal of `primaryDataSource`, obsolete
  `<templates><filters><select>` block; addition of mandatory child `<dataProvider>`)
- VirtualType data source removed from `di.xml` (the `<dataProvider class="...">` XML is sufficient)
- Admin block template corrected with `.phtml` extension (`::label/edit.phtml`)
- Edit form: `use_container => true` + action URL via `getUrl()`
- `Edit` controller: explicit injection of `Magento\Framework\Registry`
- REST routes rewritten in `:param` syntax; PHPDoc docblocks added
- `ProductLabelSearchResultsInterface::getItems()` without `: array` (PHP compatibility)
- Removal of debug code (`var_dump`)

---

## [1.4.0] - 2024-01-15

### Added

- `AlpineCommerce_LoyaltyProgram`: loyalty program with point earning and spending
  - Tables: `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points`
  - REST API: `/V1/carts/mine/loyalty-points`
  - Observers: point allocation on invoice, deduction on order
  - Total collector: cart discount
  - Minicart plugin: incentive message

### Fixed

- Correction of `referenceId` in `db_schema.xml` (prefix `ALPINECOMMERCE_*`)
- Removal of legacy files `Setup/InstallSchema.php` and `Setup/InstallData.php`
- Removal of in-memory repository `InMemory/LoyaltyBalanceRepository.php`

---

## [1.3.0] - 2024-01-10

### Added

- `AlpineCommerce_Training`: training and demo module
  - Data Patch for store view creation (⚠️ to remove — see `BACKLOG.md` B-08)
  - Observers on product, order, checkout, customer login
  - Multi-store configuration
- `AlpineCommerce_StoreLocator`: physical store locator
  - Admin interface to manage stores
  - Frontend with map and coordinates
  - Admin and frontend CSS
- `AlpineCommerce_StorePickup`: store pickup option
  - Custom Magento carrier
  - Store selection in checkout
  - Admin configuration
  - French i18n

### Fixed

- Migration of configuration paths (`cartware_*` → `alphacommerce_*`)

---

## [1.2.0] - 2024-01-05

### Added

- `AlpineCommerce_LegalPages`: dynamic legal pages
  - Page types: T&C, ToS, privacy, legal notices
  - Admin CRUD interface
  - Public REST API
  - Frontend with listing and detail view
- `AlpineCommerce_Gdpr`: GDPR compliance
  - Consent logging
  - Personal data export (Art. 15)
  - Data anonymization (Art. 17)
  - CLI commands
  - REST API
- `AlpineCommerce_Faq`: FAQ
  - Admin CRUD interface
  - Public REST API
  - Frontend with listing and detail view

---

## [1.1.0] - 2024-01-01

### Added

- `AlpineCommerce_Blog`: multi-store blog
  - Categories and posts
  - Admin CRUD interface
  - Public REST API
  - Frontend with listing and detail view

### Fixed

- Standardization of table and column names
- Correction of configuration paths

---

## [1.0.0] - 2023-12-20

### Added

- `AlpineCommerce_EuVat`: European VAT validation
  - VIES service integration via SOAP
  - CLI command `alphacommerce:euvat:validate`
  - REST API
  - Admin configuration
  - French i18n
- `AlpineCommerce_Hreflang`: hreflang SEO tags
  - Automatic hreflang tag generation
  - Multi-store support
  - Admin configuration
  - French i18n

---

## [0.1.0] - 2023-12-01

### Added

- Initial project structure
- Official documentation (`docs/`)
- Sprint workflow
- Development guidelines
- Architecture decisions (ADR)

---

## Legend

- **Added**: New features
- **Fixed**: Bug fixes
- **Changed**: Changes to existing features
- **Removed**: Removed features
- **Security**: Vulnerability fixes

---

## Upcoming versions

### v1.1 (planned)

- Complete the finalization of the 7 modules in progress (admin interface for
  LoyaltyProgram, EuVat, Hreflang, Training — see `ROADMAP.md`)
- GDPR admin anonymization (Art. 17), GDPR system configuration
- LoyaltyProgram transaction management, StorePickup availability,
  StoreLocator proximity search
- Automated tests (see `BACKLOG.md` B-07)

### v2.0 (planned)

- Introduction of `AlpineCommerce_Contact`
- Migration to React + Vite + Tailwind CSS frontend

---

*Last updated: 2026-08-06.*
