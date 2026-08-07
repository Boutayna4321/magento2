# Module AlpineCommerce_Gdpr — Conformité RGPD

> **Statut** : 🔄 Code terminé — validation Magento en attente (sprint de finalisation : Sprint 1)

## 1. Responsabilité

Gérer la **conformité RGPD** pour la plateforme : journalisation des consentements,
export des données personnelles (droit à la portabilité, Art. 15) et suppression
(droit à l'oubli, Art. 17). Magento ne propose pas de module RGPD natif en Open Source.

## 2. Périmètre & fonctionnalités

### Inclus (v1.0)

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Listing admin des consentements** | Interface admin listant tous les consentements (customer, date, type, IP, statut) | Critique |
| **Export admin RGPD** | Bouton « Export » dans le listing → déclenche `GdprExportInterface` pour un client (Art. 15) | Haute |
| **ACL granulaire** | `consent_log`, `export`, `config` (séparés car l'export est une action sensible) | Haute |
| **Menu admin** | Entrée « GDPR > Consent Log » sous `Magento_Backend::content` | Haute |
| **Cœur métier existant** | Logging, export, delete, REST API, console commands (pré-existants, inchangés) | — |

### Exclusions assumées (v1.1)

- **Anonymisation admin** (Art. 17) : les console commands suffisent en v1.0
- **Configuration système** : valeurs par défaut codées en dur
- **Journalisation des accès export** : prévu en v1.1

## 3. Architecture

```
AlpineCommerce/Gdpr/
├── etc/
│   ├── module.xml / db_schema.xml / di.xml / webapi.xml   # EXISTANT — inchangé
│   ├── acl.xml                    # Créé (Sprint 1) — consent_log, export, config
│   └── adminhtml/menu.xml         # Créé (Sprint 1)
├── Controller/Adminhtml/ConsentLog/
│   ├── Index.php                  # Créé — listing (pattern Faq)
│   └── Export.php                 # Créé — export admin
├── Ui/
│   ├── DataProvider/ConsentLogListingDataProvider.php     # Créé — AbstractDataProvider
│   └── Component/Listing/Column/Actions.php               # Créé — colonne Export
└── view/adminhtml/
    ├── layout/alphacommerce_gdpr_consentlog_index.xml
    └── ui_component/alphacommerce_gdpr_consent_log_listing.xml
```

**Règle d'or** : ne pas toucher au cœur métier existant. L'interface admin s'appuie sur
les Service Contracts (`ConsentLogRepositoryInterface`, `GdprExportInterface`) sans les modifier.

## 4. Base de données

| Table | Rôle |
|---|---|
| `alphacommerce_gdpr_consent_log` | Logs des consentements (customer_id, consent_type, status, ip_address, created_at) |

Aucune modification de schéma au Sprint 1 (table pré-existante).

## 5. API REST

| Route | Méthode | Auth | Rôle |
|---|---|---|---|
| `/V1/alphacommerce/gdpr/consent` | POST | anonymous | Enregistrer un consentement |
| `/V1/alphacommerce/gdpr/export` | GET | Mixte | Exporter les données d'un client |
| `/V1/alphacommerce/gdpr/delete` | DELETE | Mixte | Supprimer/anonymiser les données |

5 Service Contracts (`ConsentManagementInterface`, `ConsentLogRepositoryInterface`,
`GdprExportInterface`, `GdprDeleteInterface`, `GdprRestInterface`).

## 6. Admin

- **ACL** : `AlpineCommerce_Gdpr::main` (parent) > `consent_log`, `export`, `config`
- **Menu** : GDPR sous `Magento_Backend::content`, `sortOrder=90`
- **Listing** : UI Component 2.4.8 conforme (dataProvider custom `AbstractDataProvider`,
  filtres `textRange`/`select`/`date`, colonne Actions avec bouton Export + confirmation)
- **Réseau de sécurité** : route protégée par `_isAllowed()` → 403 si non autorisé

## 7. Frontend

Aucun frontend spécifique (actions via REST côté client). Les données IP sont sensibles
et accessibles uniquement aux admins avec ACL `consent_log`.

## 8. CLI

| Commande | Rôle |
|---|---|
| `alphacommerce:gdpr:export <customer_id>` | Export des données d'un client (argument positionnel) |
| `alphacommerce:gdpr:delete <customer_id>` | Suppression des données d'un client |

> ⚠️ L'aide CLI (`--help`) est trompeuse : l'usage réel est l'argument positionnel
> (voir BACKLOG B-06 P8).

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Ne pas étendre le scope métier | Le cœur (log, export, delete) existe déjà et est couvert par le REST API |
| Ajouter seulement l'interface admin manquante | Listing + export trigger + ACL |
| Conserver les console commands | Alternative opérationnelle (ex. anonymisation Art. 17 reportée en v1.1) |
| Exclusions v1.1 | Anonymisation admin + configuration système reportées (console commands suffisantes) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| D1 | `Controller/Adminhtml/ConsentLog/Export.php` : fatal PHP 8.2 `readonly` (collision `AbstractAction::$resultFactory`) | ✅ Corrigé (Phase 1) |
| — | `GdprDeleteService` n'anonymise pas les adresses/emails de commande (Art. 17 incomplet) | 📋 BACKLOG B-06 P4 |
| — | Aide CLI export trompeuse | 📋 BACKLOG B-06 P8 |

## 11. Concepts Magento enseignés

- Service Contracts (5 interfaces exposées via REST)
- UI Component `<listing>` + DataProvider custom (`AbstractDataProvider`)
- ACL hiérarchique + menu admin
- Controller admin (pattern Faq) + action export (Réponse JSON)
- Colonne d'actions custom avec confirmation (`UrlInterface`)

## 12. Validation & statut

- **Sprint de finalisation** : Sprint 1 (analyse `14`-`15`, architecture `16`)
- **Validation Magento** : en attente — les tests de non-régression (REST consent,
  CLI export/delete, admin) font partie de la validation globale (Sprint 5)
- Issues connues d'environnement : non bloquantes

---

*Sources : docs `14_SPRINT_CAHIER_DES_CHARGES_GDPR.md`, `15_SPRINT_ANALYSE_GDPR.md`,
`16_SPRINT_ARCHITECTURE_GDPR.md` (fusionnés ici), archive `17_SPRINT_REPORT_GDPR.md`.*
