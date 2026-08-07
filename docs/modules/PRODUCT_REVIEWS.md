# Module AlpineCommerce_ProductReviews — Avis produits

> **Statut** : ✅ Stable (v1.1.0)

## 1. Responsabilité

Gestion des **avis produits** : affichage sur la page produit, modération admin,
exposition via REST.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Frontend page produit** | Affichage des avis (bloc `ReviewList`) |
| **Admin** | Formulaire de modération (`review_review_form`) |
| **REST** | Services REST des avis (`QuestionRestService`/`ReviewRestService` pattern) |

## 3. Architecture

```
AlpineCommerce/ProductReviews/
├── Api/                        # Service Contracts
├── Block/Frontend/ReviewList.php  # use SortOrder (fix C2)
├── Helper/Image.php             # fix C1 (Context)
├── Model/
│   ├── Status.php               # getLabel(): string (cast match — fix Sprint 6)
│   └── Rest/                    # services REST
├── Ui/
│   ├── DataProvider/ReviewFormDataProvider.php
│   └── Source/Status.php        # Status implements OptionSourceInterface (fix C3)
└── view/adminhtml/ui_component/ review_review_form.xml
```

## 4. Base de données

Table des avis produits (standardisée).

## 5. API REST

Services REST des avis (pattern `QuestionRestService`/`ReviewRestService` — utilisent
`UserContextInterface::getUserId()` après le fix 2.4.8).

## 6. Admin

- Formulaire `review_review_form` : corrigé (dataProvider `ReviewFormDataProvider`,
  `review_id` + remplacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- Page produit : bloc des avis (HTTP 200)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| `Status implements OptionSourceInterface` | Corrige le mauvais namespace (fatal di:compile) |
| `getLabel()` cast `(string)` | `Phrase` → `string` (TypeError PHP 8.2 + strict_types) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| C1 | `Helper/Image.php` : ctor sans `Context` + `parent::__construct` | ✅ Corrigé (Phase 1) |
| C2 | `Block/Frontend/ReviewList.php` : `use SortOrder` manquant (fatal) | ✅ Corrigé (Phase 1) |
| C3 | `Ui/Source/Status.php` : classe dans le mauvais namespace (fatal compile) | ✅ Corrigé (Phase 1) |
| — | `getCurrentCustomer()` inexistant en 2.4.8 (API) | ✅ Corrigé (Sprint 5) — `UserContextInterface::getUserId()` |
| — | `review_review_form` : exception « class required » + `button-set` | ✅ Corrigé (Sprint 6 addendum) |
| — | `Status::getLabel()` `Phrase` → `string` | ✅ Corrigé (Sprint 6) |

## 11. Concepts Magento enseignés

- UI Component form + `ButtonProviderInterface`
- `UserContextInterface` (remplacement `getCurrentCustomer()` en 2.4.8)
- `OptionSourceInterface` pour les sources de listing
- `SortOrder` (SearchCriteria)

## 12. Validation & statut

- **Statut** : ✅ Stable — formulaire validé à l'écran (Sprint 6)

---

*Sources : `SPRINT_VALIDATION_REPORT.md`, `SPRINT_INTEGRATION_REPORT.md`
(fusionnés dans `CHANGELOG.md`).*
