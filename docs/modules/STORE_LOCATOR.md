# Module AlpineCommerce_StoreLocator — Localisateur de magasins

> **Statut** : 🔄 Code terminé — validation Magento en attente (sprint de finalisation : Sprint 3)

## 1. Responsabilité

Permettre aux visiteurs de **trouver les magasins physiques** de la marque : interface
admin de gestion (CRUD) et page frontend de listing. Module **totalement indépendant** de
`AlpineCommerce_StorePickup` (aucun import croisé, données distinctes).

## 2. Périmètre & fonctionnalités

### Inclus (v1.0)

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **CRUD admin des magasins** | Listing + formulaire (nom, adresse, ville, pays, code postal, lat/lng, horaires, statut) | Critique |
| **Page frontend** | Route `/store-locator` — listing des magasins actifs | Critique |
| **Filtres frontend** | Recherche côté client par nom et ville | Haute |
| **Lien Google Maps** | Lien vers Google Maps depuis chaque magasin (si lat/lng renseignées) | Haute |
| **ACL + Menu hiérarchiques** | Menu « Store Locator » sous Content | Haute |

### Exclusions assumées (v1.1)

- **Page détaillée** par magasin (route dédiée)
- **Carte interactive** (intégration Map JS)
- **Recherche par proximité** (géolocalisation du visiteur)
- **Géolocalisation automatique** du visiteur
- **Configuration système** (valeurs codées en dur)
- **REST API** (aucun Service Contract exposé en v1.0)
- **Import/export** des magasins

Verdict de la revue (archive 26) : module **fonctionnel OK pour v1.0**, ces manques sont
non bloquants.

## 3. Architecture

```
AlpineCommerce/StoreLocator/
├── etc/
│   ├── module.xml / db_schema.xml / di.xml / config.xml
│   ├── acl.xml                    # Créé — main > store
│   └── adminhtml/menu.xml         # Créé — Store Locator sous content
├── Api/
│   ├── StoreInterface.php         # Créé — Service Contract Data
│   └── StoreRepositoryInterface.php  # Créé — Repository Pattern
├── Model/
│   ├── Store.php                  # Créé
│   ├── StoreRepository.php        # Créé — CollectionProcessorInterface
│   ├── ResourceModel/Store.php    # Créé
│   ├── ResourceModel/Store/Collection.php  # Créé
│   └── Status.php                 # Créé — OptionSourceInterface
├── Controller/
│   ├── Adminhtml/Store/{Index,Edit,Save,Delete}.php  # Créés — CRUD admin
│   └── Index/Index.php            # Créé — page frontend
├── Block/
│   ├── Adminhtml/Store/Edit/*     # Créés — boutons (GenericButton, SaveButton, BackButton)
│   └── StoreLocator.php           # Créé — getStores(): array
├── Ui/
│   ├── DataProvider/
│   │   ├── StoreListingDataProvider.php  # Créé
│   │   └── StoreFormDataProvider.php     # Créé
│   └── Component/Listing/Column/Actions.php  # Créé
└── view/
    ├── adminhtml/ui_component/alphacommerce_store_locator_store_{listing,form}.xml  # Créés, XSD-valides
    ├── adminhtml/layout/..._index.xml / ..._edit.xml        # Créés
    └── frontend/
        ├── layout/alphacommerce_store_locator_index_index.xml  # Créé
        └── templates/store-locator.phtml                       # Créé
```

**Règle d'or** : indépendance totale vis-à-vis de StorePickup — aucune classe
`AlpineCommerce\StorePickup\*` importée. Architecture de référence « Repository Pattern »
pour les autres modules.

## 4. Base de données

| Table | Rôle |
|---|---|
| `alphacommerce_store_locator_store` | Magasins (name, address, city, country_id, postcode, lat, lng, hours, is_active) |

Schéma `db_schema.xml` créé au Sprint 3 (table principale du module).

## 5. API REST

**Aucune en v1.0** — exclusions assumées (pas de Service Contract exposé). Reportée en v1.1.

## 6. Admin

- **ACL** : `AlpineCommerce_StoreLocator::main` (parent) > `store`
- **Menu** : Store Locator sous `Magento_Backend::content`, hiérarchique
- **Listing** : UI Component — filtres **ID, Name, City, Country, Status**, colonne
  Actions (Edit/Delete)
- **Formulaire** : UI Component XSD-valide (cf. C8 corrigé : `optionsclass` retiré,
  `<formElements>` orphelins supprimés, `country_id` présent, `</label>` réparé)
- **Boutons** : classes `ButtonProviderInterface` (GenericButton/SaveButton/BackButton)

## 7. Frontend

- Route frontend `/store-locator` (HTTP 200) — listing des magasins actifs
- **Filtre client-side** par nom et ville (JS) — pas de rechargement
- **Lien Google Maps** par magasin (lat/lng) si renseignées
- Bloc `StoreLocator::getStores()` : retourne **`array`** (fix CRIT-2)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Indépendance totale vs StorePickup | Les deux modules gèrent des magasins mais ont des modèles de données et usages distincts ; zéro couplage |
| Repository Pattern complet | `StoreInterface` + `StoreRepositoryInterface` + ResourceModel/Collection (module de référence) |
| `CollectionProcessorInterface` dans `getList()` | Respect du Service Contract SearchCriteria (fix CRIT-1) |
| Filtres et recherche côté client | Simplification v1.0 (pas de pagination serveur) |
| Exclusions v1.1 | Carte, proximité, géoloc, config, REST, import/export (non bloquants) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| CRIT-1 | `Model/StoreRepository.php::getList()` : `SearchCriteriaBuilder` direct — le builder ne filtre pas la collection | ✅ Corrigé — `CollectionProcessorInterface` injecté |
| CRIT-2 | `Block/StoreLocator.php::getStores()` retourne une `Collection` au lieu d'un `array` | ✅ Corrigé — retour `array` |
| CRIT-3 | Bug critique découvert en revue (Sprint 3) | ✅ Corrigé — cf. archive `27_SPRINT_REPORT_STORELOCATOR_FIXES.md` |
| C8 | `alphacommerce_store_locator_store_form.xml` malformé | ✅ Corrigé (Phase 1) — réécrit, XSD-validé |
| C10 | `store-locator.phtml:7` — `getSize()` sur un `array` (fatal) | ✅ Corrigé — `count($stores)` |
| C11 | `Controller/Adminhtml/Store/{Save,Delete}.php` : `PageFactory` manquant (fatal) | ✅ Corrigé (Phase 1) |
| D2 | `StoreRepository.php` : `StoreInterfaceFactory` sans `use` (fatal di:compile) | ✅ Corrigé (Phase 1) |
| — | Non-bloquants v1.1 : page détaillée, carte interactive, proximité, géoloc auto, config, REST, import/export | 📋 v1.1 |

## 11. Concepts Magento enseignés

- **Service Contracts + Repository Pattern** (module de référence)
- **CollectionProcessorInterface** pour `getList(SearchCriteria)` (découverte CRIT-1)
- UI Component listing + form admin (XSD-valides)
- Layout XML frontend + bloc + template
- ACL + menu hiérarchiques
- Boutons `ButtonProviderInterface`

## 12. Validation & statut

- **Sprint de finalisation** : Sprint 3 (analyse `22`-`23`, architecture `24`, revue `26`,
  correctifs `27`)
- **Verdict revue** : fonctionnel OK v1.0 (revue code terminée)
- **Validation Magento** : en attente — tests de non-régression (routes frontend, CRUD
  admin, filtres) font partie de la validation globale (Sprint 5)

---

*Sources : docs `22_SPRINT_CAHIER_DES_CHARGES_STORELOCATOR.md`,
`23_SPRINT_ANALYSE_STORELOCATOR.md`, `24_SPRINT_ARCHITECTURE_STORELOCATOR.md`,
`26_SPRINT_REVUE_STORELOCATOR.md` (fusionnés ici), archives `25_SPRINT_REPORT_STORELOCATOR.md`
et `27_SPRINT_REPORT_STORELOCATOR_FIXES.md`.*
