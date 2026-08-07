# Module AlpineCommerce_LoyaltyProgram — Programme de fidélité

> **Statut** : 🔄 En finalisation (v1.4.0)

## 1. Responsabilité

**Programme de fidélité** : gain et dépense de points sur les commandes, réduction de
panier, et messagerie d'incitation.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Gain de points** | Observer : attribution de points sur facture |
| **Dépense de points** | Observer : déduction sur commande |
| **Réduction de panier** | Total collector |
| **Minicart** | Plugin : message incitatif |
| **REST API** | `/V1/carts/mine/loyalty-points` |
| **Admin** | Interface admin en cours de finalisation |

## 3. Architecture

```
AlpineCommerce/LoyaltyProgram/
├── Api/                        # Service Contracts (points, balance)
├── Model/
│   ├── Total/                  # Total collector (réduction de panier)
│   └── (Repository en base — InMemory supprimé)
├── Observer/                   # Sur invoice (gain) et order (dépense)
├── Plugin/Minicart.php         # message incitatif
└── etc/db_schema.xml           # referenceId prefix ALPINECOMMERCE_*
```

## 4. Base de données

| Table | Rôle |
|---|---|
| `alpinecommerce_loyalty_balance` | Solde de points par client |
| `alpinecommerce_loyalty_order_points` | Points émis/déduits par commande |

## 5. API REST

| Route | Rôle |
|---|---|
| `/V1/carts/mine/loyalty-points` | Consultation/utilisation des points (cart mine) |

## 6. Admin

- Interface admin **en cours de finalisation** (v1.1 prévu — voir `ROADMAP.md`)
- Intégration admin globale validée (Sprint 6)

## 7. Frontend

- Minicart : message incitatif (plugin)
- Checkout : réduction appliquée par le total collector

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Observers (invoice/order) | Attribution et déduction déléguées aux événements Magento |
| Total collector | Réduction de panier native (extension du processus de totaux) |
| Suppression du repository `InMemory/LoyaltyBalanceRepository.php` | Inutile — repository en base |
| Suppression `InstallSchema.php` / `InstallData.php` | Remplacés par `db_schema.xml` / data patches |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| — | `referenceId` incorrects dans `db_schema.xml` | ✅ Corrigé — prefix `ALPINECOMMERCE_*` |
| — | Fichiers legacy `Setup/InstallSchema.php` / `InstallData.php` | ✅ Corrigé — supprimés |
| — | Repository en mémoire legacy | ✅ Corrigé — supprimé |
| — | Transactions / interface admin complète | 📋 v1.1 — `ROADMAP.md` |

## 11. Concepts Magento enseignés

- **Total collector** (`collect` sur le processus de totaux)
- **Observers** (invoice, order)
- **Plugin** sur minicart
- Data patches + `db_schema.xml` (referenceId)

## 12. Validation & statut

- **Statut** : 🔄 En finalisation — cœur fonctionnel validé (Sprint 6), interface admin à compléter

---

*Sources : `docs/08_CHANGELOG.md` (v1.4.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
