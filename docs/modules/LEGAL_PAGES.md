# Module AlpineCommerce_LegalPages — Pages légales dynamiques

> **Statut** : ✅ Stable (v1.2.0)

## 1. Responsabilité

Pages légales dynamiques et administrables : **CGV, CGU, politique de confidentialité,
mentions légales**. CRUD admin, REST API publique, frontend avec listing et vue détaillée.

## 2. Périmètre & fonctionnalités

| Fonctionnalité | Description |
|---|---|
| **Types de pages** | CGV, CGU, confidentialité, mentions légales |
| **CRUD admin** | Listing + formulaire (contenu riche par type) |
| **REST API publique** | Exposition des pages |
| **Frontend** | Route `/legal` — listing + vue détaillée |

## 3. Architecture

```
AlpineCommerce/LegalPages/
├── Api/                        # Service Contracts
├── Block/Frontend/             # Listing / detail
├── Controller/                 # Frontend + Adminhtml
├── Model/                      # Entity, repository, ResourceModel/Collection
├── Ui/DataProvider/FormDataProvider.php
└── view/adminhtml/ui_component/ legal_page_form.xml
```

## 4. Base de données

Table des pages légales (type, contenu, statut).

## 5. API REST

API publique d'exposition des pages légales.

## 6. Admin

- CRUD des pages par type (CGV, CGU, confidentialité, mentions légales)
- Formulaire `legal_page_form` : corrigé (dataProvider `FormDataProvider`, `page_id` +
  remplacement `button-set` → `<settings><buttons>`)

## 7. Frontend

- Route `/legal` : listing + vue détaillée (HTTP 200)

## 8. CLI

Aucune commande dédiée.

## 9. Décisions d'architecture

| Décision | Justification |
|---|---|
| Contenu stocké en base, pages dynamiques | Les textes légaux évoluent sans déploiement |
| Frontend listing + détail | Pattern standard des modules de contenu |

## 10. Bugs connus / limites

| # | Problème | Statut |
|---|---|---|
| — | `legal_page_form` : exception « class required » + `button-set` (formulaire vide) | ✅ Corrigé (Sprint 6 addendum) |

## 11. Concepts Magento enseignés

- UI Component form + `ButtonProviderInterface`
- Routes frontend/admin, ACL

## 12. Validation & statut

- **Statut** : ✅ Stable — formulaire validé à l'écran (Sprint 6)

---

*Sources : `docs/08_CHANGELOG.md` (v1.2.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (fusionnés dans `CHANGELOG.md`).*
