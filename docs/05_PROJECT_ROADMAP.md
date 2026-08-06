# Roadmap du projet AlpineCommerce

## Modules terminés

| Module | Description | Statut |
|---|---|---|
| `AlpineCommerce_EuVat` | Validation TVA européenne via service VIES | ✅ Terminé |
| `AlpineCommerce_Hreflang` | Balises hreflang pour SEO multi-boutiques | ✅ Terminé |
| `AlpineCommerce_LoyaltyProgram` | Programme de fidélité (gain/dépense de points) | ✅ Terminé |
| `AlpineCommerce_Blog` | Blog multi-boutiques avec catégories et commentaires | ✅ Terminé |
| `AlpineCommerce_Faq` | FAQ avec recherche et filtres | ✅ Terminé |
| `AlpineCommerce_Gdpr` | Gestion des consentements RGPD et droits utilisateur | ✅ Terminé |
| `AlpineCommerce_LegalPages` | Pages légales dynamiques (CGV, CGU, confidentialité) | ✅ Terminé |
| `AlpineCommerce_StorePickup` | Option de retrait en magasin pour les commandes | ✅ Terminé |
| `AlpineCommerce_StoreLocator` | Localisateur de magasins physiques | ✅ Terminé |
| `AlpineCommerce_Training` | Module de formation et démonstration | ✅ Terminé |
| `AlpineCommerce_ProductReviews` | Système d'avis produits avec photos, votes, modération | ✅ Terminé |
| `AlpineCommerce_ProductQuestions` | Système Q&R produit avec réponses, votes, modération | ✅ Terminé |

---

## Modules en cours

Aucun module en cours de développement actuellement.

---

## Modules prévus

| Module | Description | Priorité | Justification |
|---|---|---|---|
| `AlpineCommerce_ProductReviews` | Système d'avis produits avec photos et notation | 🟠 Haute | Magento a des avis basiques, mais pas de fonctionnalités avancées (photos, votes, modération) |
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
| `AlpineCommerce_AbandonedCart` | Récupération de paniers abandonnés par email | 🟡 Moyenne |
| `AlpineCommerce_GiftCard` | Cartes cadeaux avec gestion des codes | 🟡 Moyenne |
| `AlpineCommerce_Search` | Recherche avancée avec filtres et suggestions | 🟡 Moyenne |
| `AlpineCommerce_Seo` | SEO avancé (meta tags, structured data, sitemap) | 🟡 Moyenne |
| `AlpineCommerce_Analytics` | Tableau de bord analytics intégré | 🟢 Basse |
| `AlpineCommerce_Export` | Export de données (commandes, clients, produits) | 🟢 Basse |

---

## Extensions Magento (sans module AlpineCommerce)

Certaines fonctionnalités seront ajoutées en étendant Magento directement, sans créer de module AlpineCommerce.

### Extensions prévues

| Fonctionnalité | Approche | Module affecté |
|---|---|---|
| Modification du checkout | Plugin sur `Magento_Checkout` | AlpineCommerce_StorePickup |
| Ajout de colonnes dans la grille produits | UI Component / Plugin | AlpineCommerce_ProductLabels (futur) |
| Modification du formulaire de contact | Layout XML + Plugin | AlpineCommerce_Contact |
| Ajout de filtres de recherche | Plugin sur `Magento_CatalogSearch` | AlpineCommerce_Search (futur) |
| Modification du template produit | Layout XML + ViewModel | AlpineCommerce_ProductLabels (futur) |

---

## Règles de priorisation

1. **Modules existants** : Maintenir et corriger les bugs
2. **Modules prévus** : Développer selon les priorités métier
3. **Extensions Magento** : Ajouter via Plugins/Observers/Layouts
4. **Refactoring** : Jamais sans justification métier

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
