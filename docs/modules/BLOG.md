# Module AlpineCommerce_Blog — Blog multi-boutiques

> **Statut** : ✅ Stable (v1.1.0)

## 1. Responsabilité

Blog multi-boutiques : gestion des **catégories** et des **articles**, avec interface
admin CRUD, REST API publique et pages frontend (listing + vue détaillée).

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Catégories** | Gestion des catégories d'articles |
| **Articles (posts)** | CRUD complet avec statut |
| **REST API publique** | Exposition des articles/catégories |
| **Frontend** | Listing + vue détaillée |
| **Admin** | Listing + formulaire d'édition (post & category) |

## 3. Architecture

```
AlpineCommerce/Blog/
├── Api/                        # Service Contracts + SearchResults
├── Block/
│   ├── Frontend/               # Listing / detail frontend
│   └── Adminhtml/Post/Edit/    # GenericButton, SaveButton, BackButton (pattern boutons)
├── Controller/
│   ├── Frontend/               # Routes /blog
│   └── Adminhtml/Post/         # CRUD admin
├── Model/                      # Entities, repositories, ResourceModel/Collection
├── Ui/DataProvider/            # PostFormDataProvider, CategoryFormDataProvider
└── view/
    ├── adminhtml/ui_component/ blog_post_form.xml, blog_category_form.xml
    └── frontend/               # templates + layouts
```

## 4. Base de données

Tables du blog (catégories, articles, liaisons multi-store). Schéma standardisé
(noms de tables et colonnes normalisés en v1.1).

## 5. API REST

API publique d'exposition des articles et catégories.

## 6. Admin

- Listing + formulaires post et catégorie
- Boutons `ButtonProviderInterface` (pattern créé à l'occasion des fix Sprint 6)

## 7. Frontend

- Route `/blog` : listing + vue détaillée (HTTP 200)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Pattern boutons `ButtonProviderInterface` | Corrige le composant `button-set` inexistant en 2.4.8 (cause racine formulaire vide) |
| `<dataProvider class="...">` enfant obligatoire | `definition.map.xml` (module-ui) exige ce nœud (XPath) pour construire l'argument `dataProvider` |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| C12 | `blog_post_form.xml` : classes de boutons `Edit\*` inexistantes (fatal) | ✅ Corrigé — GenericButton/SaveButton/BackButton créés |
| — | `blog_category_form.xml` : exception « class required » (dataProvider manquant) + `button-set` | ✅ Corrigé (Sprint 6 addendum) — dataProvider `CategoryFormDataProvider` (`category_id`) + `<settings><buttons>` |

## 11. Concepts Magento enseignés

- UI Component form (`dataProvider`, `js_config`)
- `ButtonProviderInterface` + `Toolbar` de formulaire
- `requestFieldName`/`primaryFieldName`

## 12. Validation & statut

- **Statut** : ✅ Stable — formulaires validés à l'écran (Sprint 6)
- Module de référence pour le pattern des boutons de formulaire (fix `button-set`)

---

*Sources : `docs/08_CHANGELOG.md` (v1.1.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
