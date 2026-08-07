# Module AlpineCommerce_ProductQuestions — Questions produits

> **Statut** : ✅ Stable (v1.1.0)

## 1. Responsabilité

Questions/réponses sur les produits : affichage sur la page produit, modération admin,
exposition via REST.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Frontend page produit** | Bloc des questions (`QuestionList`) |
| **Admin** | Formulaire de modération (`question_question_form`) |
| **REST** | Services REST questions/réponses |

## 3. Architecture

```
AlpineCommerce/ProductQuestions/
├── Api/                        # Service Contracts + AnswerSearchResultsInterface
├── Block/Frontend/QuestionList.php  # use SortOrder (fix Sprint 6)
├── Model/
│   ├── Status.php               # getLabel(): string (cast match) + OptionSourceInterface
│   └── Rest/                    # services REST
├── Ui/
│   ├── DataProvider/QuestionFormDataProvider.php
│   └── Source/Status.php        # fix C4
├── etc/
│   ├── di.xml                   # preference AnswerSearchResultsInterface (fix Sprint 6)
│   └── frontend/routes.xml      # fix C6 (créé — route frontend absente)
└── view/adminhtml/ui_component/ question_question_form.xml
```

## 4. Base de données

Tables des questions et réponses (standardisées).

## 5. API REST

Services REST questions/réponses (pattern `QuestionRestService`/`ReviewRestService`,
`UserContextInterface::getUserId()`).

## 6. Admin

- Formulaire `question_question_form` : corrigé (dataProvider `QuestionFormDataProvider`,
  `question_id` + remplacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- Page produit : bloc des questions — **route frontend ajoutée** (fix C6, HTTP 200)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Préférence `AnswerSearchResultsInterface` → `AnswerSearchResults` | Fatal « Cannot instantiate interface » (di:compile) |
| Cast `(string)` dans `Status::getLabel()` | `Phrase` → `string` (PHP 8.2 strict_types) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| C4 | `Ui/Source/Status.php` : mauvais namespace (fatal compile) | ✅ Corrigé (Phase 1) |
| C5 | `question_question_form.xml` : `</item>` jamais fermé (XML malformé) | ✅ Corrigé (Phase 1) |
| C6 | `etc/frontend/routes.xml` absent → 404 | ✅ Corrigé (Phase 1) — fichier créé |
| — | `AnswerSearchResultsInterface` sans implémentation | ✅ Corrigé (Sprint 6) — préférence di.xml |
| — | `QuestionList` `use SortOrder` manquant (fatal) | ✅ Corrigé (Sprint 6) |
| — | `question_question_form` : exception « class required » + `button-set` | ✅ Corrigé (Sprint 6 addendum) |

## 11. Concepts Magento enseignés

- Routes frontend (`etc/frontend/routes.xml`)
- Préférences `di.xml` pour SearchResults
- UI Component form + `ButtonProviderInterface`

## 12. Validation & statut

- **Statut** : ✅ Stable — formulaire validé à l'écran (Sprint 6)

---

*Sources : `SPRINT_VALIDATION_REPORT.md`, `SPRINT_INTEGRATION_REPORT.md`
(fusionnés dans `CHANGELOG.md`).*
