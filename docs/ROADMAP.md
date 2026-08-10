# AlpineCommerce Project Roadmap

> v1.0 development plan and beyond. Reconciles the former `05_PROJECT_ROADMAP.md`
> with the actual sprint execution status (Sprints 1-3 of finalization, Sprint 5
> validation, Sprint 6 integration).
>
> ⚠️ **Sprint number reconciliation**: the module documentation speaks of
> "Sprint 1" (GDPR), "Sprint 2" (StorePickup), "Sprint 3" (StoreLocator) — these are
> the **finalization** sprints for each module. The archived root reports are
> numbered "Sprint 5" (functional validation) and "Sprint 6" (integration) — these
> are the global **Phase B** sprints. Both numbering systems coexist; the
> global tracking is the Sprint (see `CHANGELOG.md`).

---

## Stable modules (v1.0)

These modules are functionally complete and stable. No modification is planned in the finalization sprints.

| Module | Description | Status |
|---|---|---|
| `AlpineCommerce_Blog` | Multi-store blog with categories and comments | ✅ Stable |
| `AlpineCommerce_Faq` | FAQ with search and filters | ✅ Stable |
| `AlpineCommerce_LegalPages` | Dynamic legal pages (T&C, ToS, privacy) | ✅ Stable |
| `AlpineCommerce_ProductReviews` | Product review system with photos, votes, moderation | ✅ Stable |
| `AlpineCommerce_ProductQuestions` | Product Q&A system with answers, votes, moderation | ✅ Stable |
| `AlpineCommerce_ProductLabels` | Product labels with admin management | ✅ Stable |

---

## Modules in v1.0 finalization

These modules have a functional core and need an admin interface to be usable in production.

| Order | Module | Description | Status | Finalization sprint |
|---|---|---|---|---|
| 1 | `AlpineCommerce_Gdpr` | GDPR consent management and user rights | 🔄 Code done — Magento validation pending | Sprint 1 |
| 2 | `AlpineCommerce_StorePickup` | Store pickup option for orders | 🔄 Code done — Magento validation pending | Sprint 2 |
| 3 | `AlpineCommerce_StoreLocator` | Physical store locator | 🔄 Code done — Magento validation pending | Sprint 3 |
| 4 | `AlpineCommerce_LoyaltyProgram` | Loyalty program (earn/spend points) | ⏳ To be finalized | Sprint 4 |
| 5 | `AlpineCommerce_EuVat` | European VAT validation via VIES service | ⏳ To be finalized | Sprint 5 |
| 6 | `AlpineCommerce_Hreflang` | Hreflang tags for multi-store SEO | ⏳ To be finalized | Sprint 6 |
| 7 | `AlpineCommerce_Training` | Training and demo module | ⏳ To be finalized | Sprint 7 |

> **Status note**: for Gdpr, StorePickup and StoreLocator, the admin interface
> (UI Component listing/forms, ACL, menu) was developed during finalization
> sprints 1-3. These modules remain marked "validation pending" until
> complete Magento validation (global sprint) is closed. The details of each
> finalization are in `modules/` — see documents `GDPR.md`, `STORE_PICKUP.md`,
> `STORE_LOCATOR.md`.

---

## Planned modules (post-v1.0)

| Module | Description | Priority | Justification |
|---|---|---|---|
| `AlpineCommerce_Contact` | Advanced contact form with request management | 🟠 High | Magento has a basic form, but no request tracking |
| `AlpineCommerce_AbandonedCart` | Abandoned cart recovery by email | 🟡 Medium | Magento has no automatic cart recovery |
| `AlpineCommerce_Wishlist` | Improved wishlist with social sharing | 🟡 Medium | Magento has a wishlist, but limited in features |
| `AlpineCommerce_Compare` | Advanced product comparator | 🟡 Medium | Magento has a basic comparator |
| `AlpineCommerce_Newsletter` | Newsletter with subscription and template management | 🟡 Medium | Magento has a basic newsletter |
| `AlpineCommerce_CacheWarmer` | Pre-generation of cache to improve performance | 🟢 Low | Technical optimization |

---

## Future modules (ideas)

| Module | Description | Priority |
|---|---|---|
| `AlpineCommerce_Multilingual` | Improved multilingual management with automatic detection | 🟢 Low |
| `AlpineCommerce_Personalization` | Personalized product recommendations | 🟢 Low |
| `AlpineCommerce_GiftCard` | Gift cards with code management | 🟡 Medium |
| `AlpineCommerce_Search` | Advanced search with filters and suggestions | 🟡 Medium |
| `AlpineCommerce_Seo` | Advanced SEO (meta tags, structured data, sitemap) | 🟡 Medium |
| `AlpineCommerce_Analytics` | Integrated analytics dashboard | 🟢 Low |
| `AlpineCommerce_Export` | Data export (orders, customers, products) | 🟢 Low |

---

## Magento Extensions (without AlpineCommerce module)

Some features will be added by extending Magento directly, without creating an AlpineCommerce module.

| Feature | Approach | Affected module |
|---|---|---|
| Checkout modification | Plugin on `Magento_Checkout` | AlpineCommerce_StorePickup |
| Adding columns to product grid | UI Component / Plugin | AlpineCommerce_ProductLabels |
| Contact form modification | Layout XML + Plugin | AlpineCommerce_Contact |
| Adding search filters | Plugin on `Magento_CatalogSearch` | AlpineCommerce_Search |
| Product template modification | Layout XML + ViewModel | AlpineCommerce_ProductLabels |

---

## Prioritization rules

1. **Stable modules**: Maintain and fix bugs
2. **v1.0 finalization**: Complete the 7 modules in progress (one per sprint)
3. **Planned modules**: Develop according to business priorities (post-v1.0)
4. **Magento extensions**: Add via Plugins/Observers/Layouts
5. **Refactoring**: Never without business justification

---

## Version history

| Version | Date | Changes |
|---|---|---|
| 1.0.0 | 2024 | Initial migration from Cartware to AlpineCommerce |
| 1.1.0 | 2024 | Addition of AlpineCommerce_Blog, AlpineCommerce_Faq, AlpineCommerce_Gdpr |
| 1.2.0 | 2024 | Addition of AlpineCommerce_LegalPages, AlpineCommerce_StorePickup, AlpineCommerce_StoreLocator |
| 1.3.0 | 2024 | Addition of AlpineCommerce_Training, AlpineCommerce_EuVat, AlpineCommerce_Hreflang |
| 1.4.0 | 2024 | Addition of AlpineCommerce_LoyaltyProgram |
| 1.5.0 | 2024 | Addition of AlpineCommerce_ProductReviews, AlpineCommerce_ProductQuestions |
| 1.5.1 | 2026-08-06 | v1.0 audit, finalization plan by sprints, 14 critical bugs fixed (Phase 1) |
| 1.5.2 | 2026-08-06 | Admin form fixes (dataProvider "class required", `button-set` buttons) |

> Detailed versions are documented in `CHANGELOG.md`.

---

*Last updated: 2026-08-06.*
