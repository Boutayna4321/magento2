# Module AlpineCommerce_EuVat — Validation TVA européenne

> **Statut** : 🔄 En finalisation (v1.0.0)

## 1. Responsabilité

**Validation des numéros de TVA européens** via le service **VIES** (VAT Information
Exchange System, requête SOAP), avec commande CLI, REST API et configuration admin.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Validation VIES** | Intégration du service VIES via SOAP |
| **CLI** | Commande `alphacommerce:euvat:validate` |
| **REST API** | Validation exposée |
| **Configuration admin** | Activation et paramétrage |
| **i18n** | Traduction française |

## 3. Architecture

```
AlpineCommerce/EuVat/
├── Api/                        # Service Contracts
├── Console/                    # commande alphacommerce:euvat:validate
├── Model/                      # client SOAP VIES + logique de validation
├── Controller/                 # REST
└── etc/
    ├── system.xml              # configuration admin
    └── (webapi.xml)            # REST API
```

## 4. Base de données

Aucune table dédiée (validation en temps réel via VIES).

## 5. API REST

Route REST de validation d'un numéro de TVA (Service Contract exposé).

## 6. Admin

- Configuration système (activation, paramètres VIES)

## 7. Frontend

Aucun frontend dédié.

## 8. CLI

| Commande | Rôle |
|---|---|
| `alphacommerce:euvat:validate` | Valider un numéro de TVA (VIES) |

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| VIES via SOAP | Service officiel de la Commission européenne pour la validation intracommunautaire |
| CLI + REST | Deux modes d'appel (ops et intégration) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| — | Interface admin complète à finaliser | 📋 v1.1 — `ROADMAP.md` |

## 11. Concepts Magento enseignés

- Commandes **Console** (`bin/magento`)
- Client **SOAP** externe (wsdl)
- Configuration système (`system.xml`)

## 12. Validation & statut

- **Statut** : 🔄 En finalisation — validation globale OK (Sprint 6), finalisation admin prévue

---

*Sources : `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
