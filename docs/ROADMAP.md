# Roadmap du projet AlpineCommerce

> Plan de développement v1.0 et au-delà. Réconcilie l'ancien `05_PROJECT_ROADMAP.md`
> avec l'état réel d'exécution des sprints (Sprints 1-3 de finalisation, Sprint 5
> validation, Sprint 6 intégration).
>
> ⚠️ **Réconciliation des numéros de sprint** : la documentation module parle de
> « Sprint 1 » (GDPR), « Sprint 2 » (StorePickup), « Sprint 3 » (StoreLocator) — ce sont
> les sprints de **finalisation** de chaque module. Les rapports racine archivés sont
> numérotés « Sprint 5 » (validation fonctionnelle) et « Sprint 6 » (intégration) — ce
> sont les sprints **globaux** de la phase B. Les deux numérotations coexistent ; le
> suivi global est le Sprint (voir `CHANGELOG.md`).

---

## Modules stables (v1.0)

Ces modules sont fonctionnellement complets et stables. Aucune modification n'est prévue dans les sprints de finalisation.

| Module | Description | Statut |
|---|---|---|
| `AlpineCommerce_Blog` | Blog multi-boutiques avec catégories et commentaires | ✅ Stable |
| `AlpineCommerce_Faq` | FAQ avec recherche et filtres | ✅ Stable |
| `AlpineCommerce_LegalPages` | Pages légales dynamiques (CGV, CGU, confidentialité) | ✅ Stable |
| `AlpineCommerce_ProductReviews` | Système d'avis produits avec photos, votes, modération | ✅ Stable |
| `AlpineCommerce_ProductQuestions` | Système Q&R produit avec réponses, votes, modération | ✅ Stable |
| `AlpineCommerce_ProductLabels` | Étiquettes produits avec gestion admin | ✅ Stable |

---

## Modules en cours de finalisation v1.0

Ces modules ont un cœur métier fonctionnel et nécessitent une interface admin pour être exploitables en production.

| Ordre | Module | Description | Statut | Sprint de finalisation |
|---|---|---|---|---|
| 1 | `AlpineCommerce_Gdpr` | Gestion des consentements RGPD et droits utilisateur | 🔄 Code terminé – validation Magento en attente | Sprint 1 |
| 2 | `AlpineCommerce_StorePickup` | Option de retrait en magasin pour les commandes | 🔄 Code terminé – validation Magento en attente | Sprint 2 |
| 3 | `AlpineCommerce_StoreLocator` | Localisateur de magasins physiques | 🔄 Code terminé – validation Magento en attente | Sprint 3 |
| 4 | `AlpineCommerce_LoyaltyProgram` | Programme de fidélité (gain/dépense de points) | ⏳ À finaliser | Sprint 4 |
| 5 | `AlpineCommerce_EuVat` | Validation TVA européenne via service VIES | ⏳ À finaliser | Sprint 5 |
| 6 | `AlpineCommerce_Hreflang` | Balises hreflang pour SEO multi-boutiques | ⏳ À finaliser | Sprint 6 |
| 7 | `AlpineCommerce_Training` | Module de formation et démonstration | ⏳ À finaliser | Sprint 7 |

> **Note d'état** : pour Gdpr, StorePickup et StoreLocator, l'interface admin
> (listing/formulaires UI Component, ACL, menu) a été développée pendant les sprints
> de finalisation 1-3. Ces modules restent marqués « validation en attente » tant que
> la validation Magento complète (sprint global) n'est pas close. Les détails de chaque
> finalisation sont dans `modules/` — voir les documents `GDPR.md`, `STORE_PICKUP.md`,
> `STORE_LOCATOR.md`.

---

## Modules prévus (post-v1.0)

| Module | Description | Priorité | Justification |
|---|---|---|---|
| `AlpineCommerce_Contact` | Formulaire de contact avancé avec gestion des demandes | 🟠 Haute | Magento a un formulaire basique, mais pas de suivi des demandes |
| `AlpineCommerce_AbandonedCart` | Récupération de paniers abandonnés par email | 🟡 Moyenne | Magento n'a pas de récupération automatique de paniers |
| `AlpineCommerce_Wishlist` | Liste de souhaits améliorée avec partage social | 🟡 Moyenne | Magento a une wishlist, mais limitée en fonctionnalités |
| `AlpineCommerce_Compare` | Comparateur de produits avancé | 🟡 Moyenne | Magento a un comparateur basique |
| `AlpineCommerce_Newsletter` | Newsletter avec gestion des abonnements et templates | 🟡 Moyenne | Magento a une newsletter basique |
| `AlpineCommerce_CacheWarmer` | Pré-génération du cache pour améliorer les performances | 🟢 Basse | Optimisation technique |

---

## Modules futurs (idées)

| Module | Description | Priorité |
|---|---|---|
| `AlpineCommerce_Multilingual` | Gestion multilingue améliorée avec détection automatique | 🟢 Basse |
| `AlpineCommerce_Personalization` | Recommandations produits personnalisées | 🟢 Basse |
| `AlpineCommerce_GiftCard` | Cartes cadeaux avec gestion des codes | 🟡 Moyenne |
| `AlpineCommerce_Search` | Recherche avancée avec filtres et suggestions | 🟡 Moyenne |
| `AlpineCommerce_Seo` | SEO avancé (meta tags, structured data, sitemap) | 🟡 Moyenne |
| `AlpineCommerce_Analytics` | Tableau de bord analytics intégré | 🟢 Basse |
| `AlpineCommerce_Export` | Export de données (commandes, clients, produits) | 🟢 Basse |

---

## Extensions Magento (sans module AlpineCommerce)

Certaines fonctionnalités seront ajoutées en étendant Magento directement, sans créer de module AlpineCommerce.

| Fonctionnalité | Approche | Module affecté |
|---|---|---|
| Modification du checkout | Plugin sur `Magento_Checkout` | AlpineCommerce_StorePickup |
| Ajout de colonnes dans la grille produits | UI Component / Plugin | AlpineCommerce_ProductLabels |
| Modification du formulaire de contact | Layout XML + Plugin | AlpineCommerce_Contact |
| Ajout de filtres de recherche | Plugin sur `Magento_CatalogSearch` | AlpineCommerce_Search |
| Modification du template produit | Layout XML + ViewModel | AlpineCommerce_ProductLabels |

---

## Règles de priorisation

1. **Modules stables** : Maintenir et corriger les bugs
2. **Finalisation v1.0** : Compléter les 7 modules en cours (un par sprint)
3. **Modules prévus** : Développer selon les priorités métier (post-v1.0)
4. **Extensions Magento** : Ajouter via Plugins/Observers/Layouts
5. **Refactoring** : Jamais sans justification métier

---

## Historique des versions

| Version | Date | Changements |
|---|---|---|
| 1.0.0 | 2024 | Migration initiale depuis Cartware vers AlpineCommerce |
| 1.1.0 | 2024 | Ajout de AlpineCommerce_Blog, AlpineCommerce_Faq, AlpineCommerce_Gdpr |
| 1.2.0 | 2024 | Ajout de AlpineCommerce_LegalPages, AlpineCommerce_StorePickup, AlpineCommerce_StoreLocator |
| 1.3.0 | 2024 | Ajout de AlpineCommerce_Training, AlpineCommerce_EuVat, AlpineCommerce_Hreflang |
| 1.4.0 | 2024 | Ajout de AlpineCommerce_LoyaltyProgram |
| 1.5.0 | 2024 | Ajout de AlpineCommerce_ProductReviews, AlpineCommerce_ProductQuestions |
| 1.5.1 | 2026-08-06 | Audit v1.0, plan de finalisation par sprints, 14 bugs critiques corrigés (Phase 1) |
| 1.5.2 | 2026-08-06 | Correction des formulaires admin (dataProvider « class required », boutons `button-set`) |

> Les versions détaillées sont documentées dans `CHANGELOG.md`.

---

*Dernière mise à jour : 2026-08-06.*
