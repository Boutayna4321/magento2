# Module AlpineCommerce_StorePickup — Retrait en magasin

> **Statut** : 🔄 Code terminé — validation Magento en attente (sprint de finalisation : Sprint 2)

## 1. Responsabilité

Permettre au client de choisir un **retrait en magasin** pour sa commande. Magento n'a
pas de retrait en magasin natif en Open Source. Le module ajoute un carrier Magento
personnalisé, une sélection de magasin dans le checkout et une gestion admin des points
de retrait.

## 2. Périmètre & fonctionnalités

### Inclus (v1.0)

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **CRUD des points de retrait** | Admin : listing + formulaire (source_code, name, adresse, coordonnées, horaires, statut) | Critique |
| **Carrier Magento personnalisé** | `Model/Carrier/StorePickup` | — |
| **Sélection checkout** | Intégration au checkout (config provider frontend) | — |
| **ACL + Menu** | `main` > `store`, `config` — menu sous Content | Haute |
| **REST API** | GET/POST `/V1/carts/mine/store-pickup` (self) | — |

### Exclusions assumées (v1.1)

- **Disponibilités/créneaux horaires** par magasin
- **Configuration avancée** (frais de retrait, délai avant retrait) : la config carrier de
  base (`system.xml` : active, title, name, price, sort_order, sallowspecific) suffit

## 3. Architecture

```
AlpineCommerce/StorePickup/
├── etc/
│   ├── module.xml / db_schema.xml / di.xml / config.xml / webapi.xml   # EXISTANT — inchangé
│   ├── acl.xml                    # Créé (Sprint 2) — main, store, config
│   └── adminhtml/
│       ├── system.xml             # EXISTANT — config carrier
│       └── menu.xml               # Créé (Sprint 2)
├── Api/                           # EXISTANT — StoreInfoRepositoryInterface, StoreAvailabilityInterface, StorePickupCartManagementInterface
├── Controller/Adminhtml/Store/
│   ├── Index.php                  # Créé — listing
│   ├── Edit.php                   # Créé — formulaire
│   ├── Save.php                   # Créé — sauvegarde (délègue au Repository)
│   └── Delete.php                 # Créé — suppression
├── Ui/
│   ├── DataProvider/
│   │   ├── StoreInfoListingDataProvider.php   # Créé — AbstractDataProvider
│   │   └── StoreInfoFormDataProvider.php      # Créé — ModifierPoolDataProvider
│   └── Component/Listing/Column/Actions.php   # Créé — Edit/Delete
└── view/adminhtml/
    ├── layout/alphacommerce_pickup_store_index.xml / _edit.xml
    └── ui_component/alphacommerce_pickup_store_info_listing.xml / _form.xml
```

**Règle d'or** : ne pas toucher au cœur métier existant (carrier, checkout, REST API,
`StoreInfoRepositoryInterface`). L'admin s'appuie dessus.

## 4. Base de données

| Table | Rôle |
|---|---|
| `alphacommerce_pickup_store_info` | Points de retrait (source_code, name, adresse, lat/lng, horaires, is_active) |
| `quote` / `sales_order` | Colonnes ajoutées (sélection du point de retrait par commande) |

Aucune modification de schéma au Sprint 2 (tables pré-existantes).

## 5. API REST

| Route | Méthode | Auth | Rôle |
|---|---|---|---|
| `/V1/carts/mine/store-pickup` | GET | self | Récupérer les magasins disponibles |
| `/V1/carts/mine/store-pickup` | POST | self | Associer un magasin au panier |

3 Service Contracts : `StoreInfoRepositoryInterface`, `StoreAvailabilityInterface`,
`StorePickupCartManagementInterface`.

## 6. Admin

- **ACL** : `AlpineCommerce_StorePickup::main` (parent) > `store`, `config`
- **Menu** : Store Pickup sous `Magento_Backend::content`, `sortOrder=100`
- **Listing** : UI Component 2.4.8 (colonnes source_code, name, city, country_id, phone,
  is_active, actions Edit/Delete)
- **Formulaire** : UI Component (tous les champs `StoreInfoInterface`, `country_id` en
  select, validation `source_code`/`name` requis, lat/lng numériques)

## 7. Frontend

- Checkout : sélection du point de retrait + config provider (clé `storePickup`)
- Carrier personnalisé dans le processus d'expédition

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Ne pas modifier le cœur checkout | L'intégration via plugin/carrier fonctionne déjà |
| Ajouter seulement l'interface admin | CRUD magasins + ACL + menu |
| Réutiliser `alphacommerce_pickup_store_info` + son Repository | Pas de nouvelle entité |
| Listing via `CollectionFactory` (pas `getList()`) | `StoreInfoRepositoryInterface` n'expose pas `getList(SearchCriteria)` — évite de modifier le Service Contract |
| Report v1.1 | Disponibilités/créneaux + config avancée |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| C9 | `alphacommerce_pickup_store_info_form.xml` : XML malformé | ✅ Corrigé (Phase 1) |
| C11 | `Controller/Adminhtml/Store/{Save,Delete}.php` : `PageFactory` manquant | ✅ Corrigé (Phase 1) |
| P2 | `etc/adminhtml/routes.xml` absent → URLs admin `alphacommerce_pickup/*` irrésolues | 📋 BACKLOG B-06 P2 |
| P3 | `etc/adminhtml/menu.xml` : item sans attribut `action` (menu non cliquable) | 📋 BACKLOG B-06 P3 |

## 11. Concepts Magento enseignés

- Carrier Magento personnalisé (`Magento\Shipping\Model\Carrier\AbstractCarrier`)
- Intégration checkout (config provider)
- UI Component listing + form (`AbstractDataProvider`, `ModifierPoolDataProvider`)
- Controllers admin CRUD (pattern Faq)
- ACL + menu admin

## 12. Validation & statut

- **Sprint de finalisation** : Sprint 2 (analyse `18`-`19`, architecture `20`)
- **Validation Magento** : en attente — tests de non-régression (checkout, REST)
- Issues résiduelles P2/P3 à traiter en Phase 2 (voir `BACKLOG.md`)

---

*Sources : docs `18_SPRINT_CAHIER_DES_CHARGES_STOREPICKUP.md`,
`19_SPRINT_ANALYSE_STOREPICKUP.md`, `20_SPRINT_ARCHITECTURE_STOREPICKUP.md`
(fusionnés ici), archive `21_SPRINT_REPORT_STOREPICKUP.md`.*
