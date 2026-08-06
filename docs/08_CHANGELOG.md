# Changelog officiel du projet AlpineCommerce

Toutes les modifications notables du projet sont documentées dans ce fichier.

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr-FR/).

---

## [1.4.0] - 2024-01-15

### Ajouté

- `AlpineCommerce_LoyaltyProgram` : programme de fidélité avec gain et dépense de points
  - Tables : `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points`
  - REST API : `/V1/carts/mine/loyalty-points`
  - Observers : attribution de points sur facture, déduction sur commande
  - Total collector : réduction de panier
  - Plugin minicart : message incitatif

### Corrigé

- Correction des `referenceId` dans `db_schema.xml` (prefix `ALPINECOMMERCE_*`)
- Suppression des fichiers legacy `Setup/InstallSchema.php` et `Setup/InstallData.php`
- Suppression du repository en mémoire `InMemory/LoyaltyBalanceRepository.php`

---

## [1.3.0] - 2024-01-10

### Ajouté

- `AlpineCommerce_Training` : module de formation et démonstration
  - Data Patch pour création de store views
  - Observers sur produit, commande, checkout, connexion client
  - Configuration multi-stores

- `AlpineCommerce_StoreLocator` : localisateur de magasins physiques
  - Interface admin pour gérer les magasins
  - Frontend avec carte et coordonnées
  - CSS admin et frontend

- `AlpineCommerce_StorePickup` : option de retrait en magasin
  - Carrier Magento personnalisé
  - Sélection de magasin dans le checkout
  - Configuration admin
  - i18n français

### Corrigé

- Migration des chemins de configuration (`cartware_*` → `alphacommerce_*`)

---

## [1.2.0] - 2024-01-05

### Ajouté

- `AlpineCommerce_LegalPages` : pages légales dynamiques
  - Types de pages : CGV, CGU, confidentialité, mentions légales
  - Interface admin CRUD
  - REST API publique
  - Frontend avec listing et vue détaillée

- `AlpineCommerce_Gdpr` : conformité RGPD
  - Logging des consentements
  - Export des données personnelles (Art. 15)
  - Anonymisation des données (Art. 17)
  - Commandes CLI
  - REST API

- `AlpineCommerce_Faq` : FAQ
  - Interface admin CRUD
  - REST API publique
  - Frontend avec listing et vue détaillée

---

## [1.1.0] - 2024-01-01

### Ajouté

- `AlpineCommerce_Blog` : blog multi-boutiques
  - Catégories et articles
  - Interface admin CRUD
  - REST API publique
  - Frontend avec listing et vue détaillée

### Corrigé

- Standardisation des noms de tables et colonnes
- Correction des chemins de configuration

---

## [1.0.0] - 2023-12-20

### Ajouté

- `AlpineCommerce_EuVat` : validation TVA européenne
  - Intégration service VIES via SOAP
  - Commande CLI `alphacommerce:euvat:validate`
  - REST API
  - Configuration admin
  - i18n français

- `AlpineCommerce_Hreflang` : balises hreflang SEO
  - Génération automatique des balises hreflang
  - Support multi-boutiques
  - Configuration admin
  - i18n français

---

## [0.1.0] - 2023-12-01

### Ajouté

- Structure initiale du projet
- Documentation officielle (`docs/`)
- Workflow de sprint
- Guidelines de développement
- Décisions d'architecture (ADR)

---

## Légende

- **Ajouté** : Nouvelles fonctionnalités
- **Corrigé** : Corrections de bugs
- **Modifié** : Changements dans des fonctionnalités existantes
- **Supprimé** : Fonctionnalités supprimées
- **Sécurité** : Corrections de vulnérabilités

---

## Prochaines versions

### [1.5.0] - Prévu

- `AlpineCommerce_ProductReviews` : système d'avis produits avancé
  - Tables : `alphacommerce_product_review`, `alphacommerce_product_review_image`, `alphacommerce_product_review_helpful`
  - REST API : `/V1/alphacommerce/product-reviews/*`
  - Modération (pending/approved/rejected), photos, votes utiles, achat vérifié

- `AlpineCommerce_ProductQuestions` : système Q&R produit
  - Tables : `alphacommerce_product_question`, `alphacommerce_product_answer`, `alphacommerce_product_question_vote`
  - REST API : `/V1/alphacommerce/product-questions/*`
  - Questions, réponses officielles, votes utiles, modération

### [2.0.0] - Prévu

- Introduction de `AlpineCommerce_Contact`
- Passage au frontend React + Vite + Tailwind CSS
