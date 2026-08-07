# Documentation AlpineCommerce

Bienvenue dans la documentation officielle d'AlpineCommerce — plateforme e-commerce
professionnelle construite sur **Magento 2.4.8** (Adobe Commerce Open Source) et référence
open source pour apprendre Magento 2.

## Organisation de la documentation

```
docs/
├── README.md                 ← ce fichier (hub de la documentation)
├── PROJECT_CHARTER.md        ← vision, mission, cahier des charges v1.0, analyse fonctionnelle
├── ARCHITECTURE.md           ← architecture Magento + AlpineCommerce + registre ADR
├── ENGINEERING_GUIDE.md      ← l'Engineering Bible : standards, patterns, workflow, glossaire
├── ROADMAP.md                ← plan de développement v1.0 et au-delà
├── CHANGELOG.md              ← historique des versions et des correctifs
├── BACKLOG.md                ← dette technique tracée (Phase C)
├── modules/                  ← le chapitre de chaque module (Phase D)
│   ├── BLOG.md
│   ├── FAQ.md
│   ├── ...
└── archive/sprints/          ← rapports de sprint historiques (hors doc officielle)
```

## Documents principaux

| Document | Rôle | Contenu essentiel |
|---|---|---|
| `PROJECT_CHARTER.md` | 🎯 La charte | Vision double (plateforme + référence d'apprentissage), philosophie, cahier des charges et analyse fonctionnelle v1.0, décisions d'architecture majeures |
| `ARCHITECTURE.md` | 🏗️ L'architecture | Vue d'ensemble, Magento Core, 13 modules, tables DB, REST API, multi-store, sécurité, performance, déploiement, registre ADR (ADR-001 → 014) |
| `ENGINEERING_GUIDE.md` | 📐 L'Engineering Bible | Squelette canonique d'un module, principes (SOLID/DRY/KISS/YAGNI), PSR-12, patterns Adobe Commerce, ACL/UI Components, workflow des sprints, anti-patterns, checklist, glossaire |
| `ROADMAP.md` | 🗺️ La roadmap | 6 modules stables, 7 en finalisation, modules prévus/futurs, extensions Magento, priorisation, historique des versions |
| `CHANGELOG.md` | 📜 L'historique | Versions 0.1.0 → 1.5.2, correctifs Phase 1 (14 bugs critiques), intégration Sprint 6, résolution des formulaires admin |
| `BACKLOG.md` | 🛠️ La dette technique | B-01 → B-09 : listings XSD, Service Contracts manquants, absence de tests, Training incohérent, Phase 2 résiduelle |

## Documents modules

Chaque module AlpineCommerce a son document dans `docs/modules/` — il est
**auto-suffisant** : tout ce qu'il faut savoir sur le module est dedans (responsabilité,
périmètre, architecture, API, décisions, bugs connus).

| Module | Document | Statut |
|---|---|---|
| Blog | `modules/BLOG.md` | ✅ Stable |
| Faq (module canonique) | `modules/FAQ.md` | ✅ Stable |
| LegalPages | `modules/LEGAL_PAGES.md` | ✅ Stable |
| ProductReviews | `modules/PRODUCT_REVIEWS.md` | ✅ Stable |
| ProductQuestions | `modules/PRODUCT_QUESTIONS.md` | ✅ Stable |
| ProductLabels | `modules/PRODUCT_LABELS.md` | ✅ Stable |
| Gdpr | `modules/GDPR.md` | 🔄 Finalisation (Sprint 1) |
| StorePickup | `modules/STORE_PICKUP.md` | 🔄 Finalisation (Sprint 2) |
| StoreLocator | `modules/STORE_LOCATOR.md` | 🔄 Finalisation (Sprint 3) |
| LoyaltyProgram | `modules/LOYALTY_PROGRAM.md` | ⏳ À finaliser |
| EuVat | `modules/EU_VAT.md` | ⏳ À finaliser |
| Hreflang | `modules/HREFLANG.md` | ⏳ À finaliser |
| Training | `modules/TRAINING.md` | ⏳ À finaliser |

## Points d'entrée par profil

- **Développeur débutant** : commencez par `PROJECT_CHARTER.md` (le « pourquoi »),
  puis `ARCHITECTURE.md` (le « comment »), puis le document du module canonique
  `modules/FAQ.md`.
- **Développeur intermédiaire** : `ENGINEERING_GUIDE.md` est votre référence ;
  comparez chaque module au squelette canonique.
- **Contributeur / maintainer** : `ENGINEERING_GUIDE.md` (checklist de validation),
  `BACKLOG.md` (dette à traiter), `CHANGELOG.md` (historique des décisions de correctif).

## Lien avec le code

- Modules : `src/app/code/AlpineCommerce/*`
- Thème custom : `src/app/design/`
- La doc est la **Source of Truth** : toute décision architecturale y est tracée,
  tout le code doit la respecter, toute modification est validée.

---

*Dernière mise à jour : 2026-08-07 (restructuration en documentation produit).*
