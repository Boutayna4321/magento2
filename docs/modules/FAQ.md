# Module AlpineCommerce_Faq — FAQ

> **Statut** : ✅ Stable (v1.2.0) — **module canonique** des patterns UI Component

## 1. Responsabilité

Page « Foire aux questions » : gestion admin des questions/réponses (CRUD), exposition
via REST API publique et affichage frontend (listing + vue détaillée). Sert de
**module de référence** pour les patterns UI Component du projet.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **CRUD admin** | Listing + formulaire de question/réponse |
| **REST API publique** | Exposition des FAQ |
| **Frontend** | Route `/faq` — listing + vue détaillée |
| **Pattern de référence** | Listing, formulaire, DataProvider, boutons (modèle des autres modules) |

## 3. Architecture

```
AlpineCommerce/Faq/
├── Api/                        # Service Contracts + SearchResults
├── Block/Frontend/             # Listing / detail
├── Controller/                 # Frontend + Adminhtml
├── Model/                      # Entity, repository, ResourceModel/Collection
├── Ui/DataProvider/FaqFormDataProvider.php
└── view/adminhtml/ui_component/ faq_faq_listing.xml, faq_faq_form.xml
```

## 4. Base de données

Table principale des questions/réponses (schéma standardisé).

## 5. API REST

API publique d'exposition des entrées FAQ.

## 6. Admin

- Listing `faq_faq_listing` — ⚠️ **6 listings admin XSD-invalides (bien-formés)** :
  `<massAction>` (mauvaise casse), `<deps>` texte, `<primaryDataSource>`, `<param>`
  dans massaction, `<options>` inline — dont `faq_faq_listing` (voir `BACKLOG.md` B-01,
  **bloquant v1.0**)
- Formulaire `faq_faq_form` : corrigé (dataProvider `FaqFormDataProvider`, `faq_id` +
  remplacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- Route `/faq` : listing + vue détaillée (HTTP 200)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Module canonique des patterns UI Component | Les patterns listing/form ont été clonés depuis Faq vers les autres modules |
| Fix `button-set` appliqué en premier sur Faq | Page de test de la cause racine formulaire vide (2.4.8) |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| B-01 | 6 listings XSD-invalides (dont `faq_faq_listing`) — `<massAction>`, `<deps>` texte, `<primaryDataSource>`, `<param>` massaction, `<options>` inline | 📋 **Bloquant v1.0** — BACKLOG B-01 |
| — | `faq_faq_form` : exception « class required » + `button-set` (formulaire vide) | ✅ Corrigé (Sprint 6 addendum) |

## 11. Concepts Magento enseignés

- UI Component `<listing>` (structure XSD, dataProvider)
- UI Component `<form>` (dataProvider, boutons `ButtonProviderInterface`)
- Routes admin/frontend + ACL

## 12. Validation & statut

- **Statut** : ✅ Stable — formulaire validé à l'écran (Sprint 6)
- **Blocker restant** : listings XSD-invalides (B-01) à corriger en Phase 2

---

*Sources : `docs/08_CHANGELOG.md` (v1.2.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
