# Module AlpineCommerce_Hreflang — Balises hreflang SEO

> **Statut** : 🔄 En finalisation (v1.0.0)

## 1. Responsabilité

Génération automatique des **balises hreflang** (SEO multi-boutiques) : balises
`<link rel="alternate" hreflang="...">` pour les pages de chaque boutique.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Génération automatique** | Balises hreflang injectées sur les pages |
| **Multi-boutiques** | Support des store views |
| **Configuration admin** | Activation et paramétrage |
| **i18n** | Traduction française |

## 3. Architecture

```
AlpineCommerce/Hreflang/
├── Model/                      # générateur de balises + logique hreflang
├── (Plugin/Block)              # injection dans le head des pages
└── etc/
    └── system.xml              # configuration admin
```

## 4. Base de données

Aucune table dédiée (configuration en `core_config_data`).

## 5. API REST

Aucune.

## 6. Admin

- Configuration système (activation, domaines par store view)

## 7. Frontend

- Balises `<link rel="alternate" hreflang="xx-XX">` générées automatiquement dans le
  `<head>` des pages (une par store view), selon la configuration

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Génération automatique (plugin/observer sur le head) | Aucun changement de template core |
| Configuration par store view | Mapping URL → langue propre à chaque boutique |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| — | Finalisation complète (configuration fine, tests SEO) | 📋 v1.1 — `ROADMAP.md` |

## 11. Concepts Magento enseignés

- SEO multi-store (hreflang)
- Configuration système par store view
- Injection de markup dans le `<head>` (plugin/bloc)

## 12. Validation & statut

- **Statut** : 🔄 En finalisation — validation globale OK (Sprint 6)

---

*Sources : `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
