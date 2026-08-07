# Module AlpineCommerce_Training — Formation & démonstration

> **Statut** : 🔄 En finalisation (v1.3.0)

## 1. Responsabilité

Module de **formation et démonstration** Magento : observers d'événements, configuration
multi-stores et exemples de bonnes pratiques. **Ne pas déployer tel quel en production**
(la Data Patch de création de store views doit être supprimée — voir `BACKLOG.md` B-08).

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Data Patch** | Création de store views — ⚠️ **à supprimer** (BACKLOG B-08) |
| **Observers** | Sur produit, commande, checkout, connexion client |
| **Configuration** | Multi-stores |

## 3. Architecture

```
AlpineCommerce/Training/
├── Setup/Patch/Data/           # Data Patch création store views (⚠️ à supprimer)
├── Observer/                   # produit, commande, checkout, connexion client
├── etc/system.xml              # configuration multi-stores
└── (blocks/templates de démonstration)
```

## 4. Base de données

Aucune table dédiée. La Data Patch modifie `core_store_group` / `core_store`
(⚠️ voir BACKLOG B-08 — à transformer en script de démo reproductible).

## 5. API REST

Aucune.

## 6. Admin

- Configuration système (démonstration)

## 7. Frontend

- Démonstration via observers (logs, événements)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Data Patch pour store views | Illustration du pattern Data Patch, mais **interdit en production** (B-08) |
| Observers multiples | Support pédagogique des événements Magento |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| B-08 | Data Patch crée des store views de façon permanente — irréversible/indésirable | 📋 BACKLOG B-08 — supprimer/transformer |

## 11. Concepts Magento enseignés

- **Observers** (product, order, checkout, customer_login)
- **Data Patches** (et leurs risques en prod)
- Configuration multi-stores

## 12. Validation & statut

- **Statut** : 🔄 En finalisation — validation globale OK (Sprint 6)

---

*Sources : `docs/08_CHANGELOG.md` (v1.3.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
