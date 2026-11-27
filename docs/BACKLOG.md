# AlpineCommerce Technical Backlog

> This document centralizes the **technical debt** detected by the module audit.
> It is addressed in **Phase C** (progressive harmonization), after the standards freeze
> (Phase A) and the finalization of the v1.0 platform (Phase B).
> Each entry follows the format: **Problem → Why → Fix → Priority**.
>
> Sources: `10_BACKLOG.md` (Phase A) and v1.0 audit of the 13 modules (former Sprint 11 report, archived).

---

## B-01 — Non-compliant admin grids with Magento 2.4.8 XSD — **CLOSED: OBSOLETE**

> **Status re-checked on 2026-09-29: this entry no longer describes the repository.**
> It is kept for history, with the verification that closed it.

**Original claim.** Six admin listings used the pre-2.4.x format — `<primaryDataSource>`
(removed), `<templates><filters><select customScope="...">` (obsolete), and `<dataSource>`
without a mandatory child `<dataProvider class="..." name="...">` — which raises
`ConfigurableObject: Node "argument" with name "class" is required` on load. The affected files
were `Blog/blog_post_listing.xml`, `Blog/blog_category_listing.xml`, `Faq/faq_faq_listing.xml`
(the canonical module), `LegalPages/legal_page_listing.xml`,
`ProductQuestions/question_question_listing.xml` and `ProductReviews/review_review_listing.xml`.

**Why it was closed.** The three claimed symptoms no longer exist:

| Check | Result on 2026-09-29 |
|---|---|
| `grep -rl primaryDataSource` over every module XML | no match |
| every `<dataSource>` has a child `<dataProvider class=… name=…>` | yes, 0 exceptions |
| `customScope` | 1 occurrence, in `Blog/blog_post_listing.xml` only |
| `Magento\Ui\Config\Reader\DefinitionMap::read()` (Magento's own reader and schema locator) | parses all AlpineCommerce listings without error |

The Faq listing, described as broken, is in fact the correct modern shape — it was evidently
fixed without the backlog being updated.

**Residual work done.** The one real leftover was the obsolete
`<templates><filters><select customScope="…">` block in `Blog/blog_post_listing.xml`. It has had
no effect since 2.4 and every column already declares its own `<filter>`; it was replaced by
`<filters name="listing_filters"/>`, matching the other five listings.

**Lesson.** The audit that produced this entry was not re-verified against the code. Entries in
this backlog should be re-checked with a command before being worked on — the same audit that
wrote B-01 missed a much larger defect sitting in the controllers (see
[CHANGELOG 1.7.1](CHANGELOG.md#171---2026-09-29): 21 admin controllers injected the frontend
result page factory and fatal-errored on `setActiveMenu()`).

**Priority**: closed.

---

## B-02 — StoreLocator: admin in "legacy" style (Block instead of UI Component) — **CLOSED: OBSOLETE**

> **Status re-checked on 2026-09-29: this entry no longer describes the repository.**

**Original claim.** `StoreLocator/Block/Adminhtml/Store/Listing.php` and `Edit.php` built the
admin grid and form as legacy PHP Blocks instead of UI Components, and the module had no interface
in `Api/` — a pedagogical inconsistency against the canonical module (Faq).

**Why it was closed.** All three points are now false:

| Check | Result on 2026-09-29 |
|---|---|
| `StoreLocator/Block/Adminhtml/Store/` | does not exist |
| UI Components present | `view/adminhtml/ui_component/alphacommerce_store_locator_store_listing.xml` and `…_form.xml` |
| Service Contract present | `Api/StoreRepositoryInterface.php` + `Api/Data/` |

A learner comparing StoreLocator with Faq now sees one way of doing admin, not two.

**Priority**: closed.

---

## B-03 — Modules without Service Contract (Hreflang, StoreLocator, StoreSetup)

**Impact: low to medium — decision to document.**

**Problem**

| Module | `Api/` interfaces | Analysis |
|---|---|---|
| Hreflang | 0 | 100% config SEO module (`system.xml` + Block). No business entity → the Service Contract is **questionable**. |
| StoreLocator | 1 (`StoreRepositoryInterface`) | ✅ **Resolved on 2026-09-29** — the Service Contract now exists. Originally listed here with 0 interfaces. |
| StoreSetup | 0 | Introductory demo module (first module, observers). Destined for simplicity → to **explicitly justify**. |

**Why this is a problem for the project**
The rule "all business logic is behind a Service Contract" must be **uniform**.
If exceptions exist, they must be **documented as assumed decisions**,
not as oversights.

**Fix**
- StoreLocator: **done** — `Api/StoreRepositoryInterface.php` exists (re-checked 2026-09-29).
- Hreflang and StoreSetup: add a "Decision" note in their module doc
  (Phase D) explaining why the Service Contract is not applicable here.
- If a module cannot be justified, create the Service Contract.

**Priority**: Low (documentation first, code if necessary).

---

## B-04 — Engineering documents teaching the wrong UI Component format

**Impact: medium (pedagogical).** The old `docs/02_ENGINEERING_GUIDE.md` (UI Components section)
showed an XML example **in pre-2.4.8 format** (without `<dataProvider>` child) — the same
as the broken files in B-01. A learner following the guide would reproduce the bug.

**Fix**: replaced by the 2.4.8 compliant format in `ENGINEERING_GUIDE.md`
(reference example: `productlabels_label_grid.xml`).

**Priority**: ✅ Fixed in Phase A (see `ENGINEERING_GUIDE.md`).

---

## B-05 — UI Forms to audit (residual risk)

**Impact: to verify.** The admin `<form>` elements (`*_form.xml`) don't use the same
mechanism as `<listing>`, but must be validated one by one against the same standard.

**Action**
- Audit all `view/adminhtml/ui_component/*_form.xml` against the 2.4.8 XSD.
- Verify the HTTP loading of each admin page of the concerned modules.

**Priority**: Medium (to do during Phase C audit, before module doc).

---

## B-06 — Phase 2 of validation fixes (residual issues)

**Impact: medium.** Remaining to address after Phase 1 (14 critical bugs fixed,
see `CHANGELOG.md`) — issues identified by validation/integration reports:

| # | Module | Problem |
|---|---|---|
| P1 | Gdpr, LegalPages, ProductQuestions, ProductReviews, StoreLocator, StorePickup | 6 admin listings XSD-invalid (well-formed): `<massAction>` (wrong case), `<deps>` text, `<primaryDataSource>`, `<param>` in massaction, `<options>` inline |
| P2 | StorePickup | `etc/adminhtml/routes.xml` missing → admin URLs `alphacommerce_pickup/*` unresolved |
| P3 | StorePickup | `etc/adminhtml/menu.xml`: item without `action` attribute (non-clickable menu) |
| P4 | Gdpr | `GdprDeleteService` does not anonymize customer data (addresses, order emails) — Art. 17 GDPR incomplete |
| P5 | ProductLabels | Observer `getLabelsByProductId`: N+1 queries to optimize (batch) |
| P6 | ProductLabels | Direct SQL in `getLabelsByProductId` (does not follow ResourceModel pattern) |
| P7 | Hreflang | Orphan `adminhtml/routes.xml` (no admin controller) to clean up |
| P8 | Gdpr | CLI help `gdpr:export` misleading (`--help` vs positional argument `<customer_id>`) |

**Priority**: Medium (Phase C), outside Phase 1.

---

## B-07 — Absence of automated tests (blocking v1.0)

**Impact: critical (quality).** The v1.0 audit found **0 test** (unit, integration,
functional, API) across all 13 modules — while the charter requires a minimum
coverage of 80% and validation reports had to rely on manual tests/curl.

**Fix**
- Set up the test framework (`Test/Unit`, `Test/Integration`, `Test/functional`).
- Start with canonical modules (Faq, Blog, ProductLabels).
- Configure CI/CD to run tests on each commit.

**Priority**: High (dedicated sprint before/with v1.0 validation).

---

## B-08 — Inconsistent StoreSetup module

**Impact: medium.** Demo module with abnormal demo data.

**Problem**
- Data patch `Setup/Patch/Data/CreateStores.php` creates 4 store views and assigns a theme
  (risk of regression on existing installation).
- `config.xml` contains store/currency/payment/shipping configurations (demo
  data, not module configuration).

**Fix** (assumed architecture decision: **pedagogical demo** module)
- Remove the data patch `CreateStores.php`.
- Remove `config.xml`.
- Empty/remove `di.xml`.
- Document the pedagogical purpose of the module.

**Priority**: Medium (Phase B).

---

## B-09 — Hreflang: scope to document (configuration-only)

**Impact: low.** Module 100% configuration, without business entities.

**Assumed decision**: remain configuration-only (automatic injection of hreflang
tags based on active store views). The manual language→store view mapping is
deferred to v1.1. No Service Contract is required as long as the module remains
config-only — to document in its module doc.

**Priority**: Low (documentation, Phase D).

---

## Backlog rules

1. **We don't fix in Phase B.** The priority of Phase B is the finalization of
   features. Unless critical blocking bug, the debt remains listed here.
2. **Each Phase C fix** follows the "migration" pattern: one module at a time,
   verified (HTTP grid OK + `setup:di:compile` OK) before moving to the next.
3. **A new module must NOT create a new entry here** — this is the purpose of the
   standards freeze (Phase A → `ENGINEERING_GUIDE.md`).

---

*Last updated: 2026-08-06 (initial Phase A audit + validation report complements).*
