# AlpineCommerce Technical Backlog

> This document centralizes the **technical debt** detected by the module audit.
> It is addressed in **Phase C** (progressive harmonization), after the standards freeze
> (Phase A) and the finalization of the v1.0 platform (Phase B).
> Each entry follows the format: **Problem → Why → Fix → Priority**.
>
> Sources: `10_BACKLOG.md` (Phase A) and v1.0 audit of the 13 modules (former Sprint 11 report, archived).

---

## B-01 — Non-compliant admin grids with Magento 2.4.8 XSD (6 files)

**Impact: critical.** The affected admin pages crash (or may crash) on
load: exception `ConfigurableObject` — *"Node "argument" with name "class" is
required for this type"*.

**Problem**
The `<listing>` elements use the pre-2.4.x XML format:
- `<primaryDataSource>` (removed)
- `<templates><filters><select customScope="...">` (obsolete)
- `<dataSource>` without child `<dataProvider class="..." name="...">` (mandatory in 2.4.8)

**Concerned files**

| File | Module |
|---|---|
| `Blog/view/adminhtml/ui_component/blog_post_listing.xml` | Blog |
| `Blog/view/adminhtml/ui_component/blog_category_listing.xml` | Blog |
| `Faq/view/adminhtml/ui_component/faq_faq_listing.xml` | **Faq (canonical module!)** |
| `LegalPages/view/adminhtml/ui_component/legal_page_listing.xml` | LegalPages |
| `ProductQuestions/view/adminhtml/ui_component/question_question_listing.xml` | ProductQuestions |
| `ProductReviews/view/adminhtml/ui_component/review_review_listing.xml` | ProductReviews |

**Fix** (reference already applied)
`ProductLabels/view/adminhtml/ui_component/productlabels_label_grid.xml` is the only one
that is correct: removal of `primaryDataSource`, addition of the child `<dataProvider>` with
its `<settings>`, modern columns (`selectionsColumn`, actions).

**Priority**: High — 6 modules' admin are affected, including the canonical module.

---

## B-02 — StoreLocator: admin in "legacy" style (Block instead of UI Component)

**Impact: medium.** Pedagogical and functional inconsistency with the rest of the repository.

**Problem**
- `StoreLocator/Block/Adminhtml/Store/Listing.php`: grid built in **legacy PHP
  Block** (no UI Component).
- `StoreLocator/Block/Adminhtml/Store/Edit.php`: form in Block.
- No interface in `Api/` (no Service Contract).

**Why this is a problem for the project**
A learner comparing StoreLocator to the canonical module (Faq) sees **two different
ways** of doing admin — and no longer knows which is correct. The standards freeze
(Phase A) assumes that any new module uses UI Components.

**Fix**
- Migrate the grid to `view/adminhtml/ui_component/store_store_listing.xml` + DataProvider.
- Define a Service Contract in `Api/` + Repository.
- Reuse the `Faq` pattern (canonical module).

**Priority**: Medium (Phase C).

---

## B-03 — Modules without Service Contract (Hreflang, StoreLocator, Training)

**Impact: low to medium — decision to document.**

**Problem**

| Module | `Api/` interfaces | Analysis |
|---|---|---|
| Hreflang | 0 | 100% config SEO module (`system.xml` + Block). No business entity → the Service Contract is **questionable**. |
| StoreLocator | 0 | Has entities (stores) → the Service Contract is **mandatory** (see B-02). |
| Training | 0 | Introductory demo module (first module, observers). Destined for simplicity → to **explicitly justify**. |

**Why this is a problem for the project**
The rule "all business logic is behind a Service Contract" must be **uniform**.
If exceptions exist, they must be **documented as assumed decisions**,
not as oversights.

**Fix**
- StoreLocator: covered by B-02.
- Hreflang and Training: add a "Decision" note in their module doc
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

## B-08 — Inconsistent Training module

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
