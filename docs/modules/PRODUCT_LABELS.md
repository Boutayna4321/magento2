# Module AlpineCommerce_ProductLabels — Étiquettes produits

> **Statut** : ✅ Stable (v1.5.0)

## 1. Responsabilité

**Étiquettes produits** administrables (ex. « Nouveau », « Promotion », « Stock limité ») :
rendu visuel sur la page produit et les listings de catégorie.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Grille admin** | Listing avec massactions Delete / Change status + bouton « Add New Label » |
| **Formulaire d'édition** | Nom, code, couleurs, priorité, position, dates de validité, statut, sélection produits |
| **REST API** | CRUD des labels + liaison produits + application |
| **Frontend** | Rendu sur page produit et listings catégorie (plugin `CatalogBlock`) |
| **i18n** | Traduction française |

## 3. Architecture

```
AlpineCommerce/ProductLabels/
├── Api/                        # Service Contracts + SearchResults
├── Block/
│   ├── Adminhtml/Label/Grid.php   # fix C7 — réécrit (massaction natif)
│   └── Frontend/                  # rendu labels
├── Controller/                 # Adminhtml (CRUD) + REST
├── Model/                      # Entities, repositories, ResourceModel/Collection
├── Plugin/CatalogBlock.php     # plugin d'affichage frontend
├── Observer/                   # application des labels (⚠️ N+1 — BACKLOG B-06 P5)
└── view/
    ├── adminhtml/ui_component/alphacommerce_product_label_listing.xml
    └── frontend/layout/catalog_product_view.xml   # referenceBlock (fix Sprint 6)
```

## 4. Base de données

| Table | Rôle |
|---|---|
| `alphacommerce_product_label` | Labels (code, couleurs, priorité, position, dates, statut) |
| `alphacommerce_product_label_product` | Liaison label ↔ produits |

## 5. API REST

| Route | Méthodes |
|---|---|
| `/V1/alphacommerce/product-labels` | GET, POST |
| `/V1/alphacommerce/product-labels/:entityId` | GET, DELETE |
| `/V1/alphacommerce/product-labels/:labelId/products` | GET, POST |
| `/V1/alphacommerce/product-labels/:productId/apply` | POST |

## 6. Admin

- Grille admin réécrite au format 2.4.8 : retrait `primaryDataSource`, bloc
  `<templates><filters><select>` obsolète, **`<dataProvider class="...">` enfant ajouté**
- VirtualType de data source retiré du `di.xml`
- Formulaire : `use_container => true`, URL d'action via `getUrl()`, Registry injecté
  explicitement dans le contrôleur `Edit`

## 7. Frontend

- Page produit + listings catégorie : rendu des labels via plugin `CatalogBlock`
- Fix Sprint 6 : `referenceContainer` → **`referenceBlock`** pour `product.info.media`
  et `product.info.details` (ce sont des `block`, pas des `container` — labels jamais rendus)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Plugin `CatalogBlock` pour le rendu | Affichage sans toucher les templates core |
| Grid Magento 2.4.8 natif | Le bloc `<templates><filters><select>` est obsolète ; massaction natif |
| `<dataProvider class="...">` enfant obligatoire | Exigence `definition.map.xml` (module-ui) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| C7 | `Block/Adminhtml/Label/Grid.php` : `use Magento\Backend\Block\Widget\Grid` (collision fatale), ctor invalide, renderer + constante inexistants | ✅ Corrigé (Phase 1) |
| — | Grille non conforme 2.4.8 (`primaryDataSource`, `<templates><filters><select>`) | ✅ Corrigé (v1.5.0) |
| — | Labels jamais rendus (referenceContainer sur des blocks) | ✅ Corrigé (Sprint 6) |
| P5 | Observer : N+1 sur l'application des labels | 📋 BACKLOG B-06 P5 |

## 11. Concepts Magento enseignés

- Plugin (`around/after`) sur blocs core (`CatalogBlock`)
- Grille admin native 2.4.8 (listing + massactions + bouton Add New)
- `referenceBlock` vs `referenceContainer`
- Routes REST syntaxe `:param`

## 12. Validation & statut

- **Statut** : ✅ Stable — frontend et admin validés (Sprint 6)

---

*Sources : `docs/08_CHANGELOG.md` (v1.5.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
