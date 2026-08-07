# Architecture du projet AlpineCommerce

> Vue d'ensemble de l'architecture Magento + AlpineCommerce et registre des décisions
> d'architecture (ADR). Regroupe l'ancien `01_ARCHITECTURE.md` et `06_DECISIONS.md`.

---

## 1. Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────┐
│                     AlpineCommerce E-Commerce                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   Frontend  │    │   Backend   │    │     API     │     │
│  │   (React)   │    │   (PHTML)   │    │   (REST)    │     │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘     │
│         │                  │                  │             │
│         └──────────────────┼──────────────────┘             │
│                            │                                │
│                    ┌───────▼───────┐                        │
│                    │   Magento 2   │                        │
│                    │   (Core)      │                        │
│                    └───────┬───────┘                        │
│                            │                                │
│  ┌─────────────────────────┼─────────────────────────┐     │
│  │                         │                         │     │
│  ┌───────▼───────┐  ┌───────▼───────┐  ┌───────▼───────┐ │
│  │ AlpineCommerce│  │ AlpineCommerce│  │ AlpineCommerce│ │
│  │     Blog      │  │     Faq       │  │    Gdpr       │ │
│  └───────────────┘  └───────────────┘  └───────────────┘ │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐ │
│  │ AlpineCommerce│  │ AlpineCommerce│  │ AlpineCommerce│ │
│  │   Hreflang    │  │    LegalPages │  │ StorePickup   │ │
│  └───────────────┘  └───────────────┘  └───────────────┘ │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐ │
│  │ AlpineCommerce│  │ AlpineCommerce│  │ AlpineCommerce│ │
│  │ StoreLocator  │  │    Training   │  │   EuVat       │ │
│  └───────────────┘  └───────────────┘  └───────────────┘ │
│  ┌───────────────────────────────────────────────────┐     │
│  │              AlpineCommerce LoyaltyProgram         │     │
│  └───────────────────────────────────────────────────┘     │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Base de données (MySQL)            │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

**Principe fondamental : Magento 2 est le cœur de l'application.** Les modules
AlpineCommerce viennent **compléter** Magento, jamais le remplacer.

### Règles d'or

1. **Ne jamais recréer** un module Magento existant
2. **Étendre** via Plugin, Observer, Layout XML, ViewModel
3. **Utiliser** les Service Contracts de Magento
4. **Respecter** les conventions de nommage Magento

---

## 2. Magento Core

### Modules Magento utilisés

| Module Magento | Rôle dans AlpineCommerce |
|---|---|
| `Magento_Catalog` | Catalogue produits |
| `Magento_Customer` | Gestion des clients |
| `Magento_Checkout` | Processus de commande |
| `Magento_Sales` | Commandes, factures, avoirs |
| `Magento_Quote` | Panier et devis |
| `Magento_Cms` | Pages et blocs CMS |
| `Magento_Inventory` | MSI (Multi Source Inventory) |
| `Magento_Payment` | Méthodes de paiement |
| `Magento_Shipping` | Méthodes de livraison |
| `Magento_Store` | Multi-boutiques |
| `Magento_Backend` | Interface d'administration |
| `Magento_Webapi` | REST API |
| `Magento_Indexer` | Indexeurs |
| `Magento_Cache` | Cache |

---

## 3. Modules AlpineCommerce

### Modules existants

| Module | Responsabilité | Tables DB | API REST |
|---|---|---|---|
| `AlpineCommerce_Blog` | Blog multi-boutiques | `alphacommerce_blog_post`, `alphacommerce_blog_category` | `/V1/alphacommerce/blog/*` |
| `AlpineCommerce_Faq` | FAQ | `alphacommerce_faq` | `/V1/alphacommerce/faqs/*` |
| `AlpineCommerce_Gdpr` | Conformité RGPD | `alphacommerce_gdpr_consent_log` | `/V1/alphacommerce/gdpr/*` |
| `AlpineCommerce_Hreflang` | SEO hreflang | Aucune | Aucune |
| `AlpineCommerce_LegalPages` | Pages légales | `alphacommerce_legal_page` | `/V1/alphacommerce/legal-pages/*` |
| `AlpineCommerce_StorePickup` | Retrait en magasin | `alphacommerce_pickup_store_info` | `/V1/carts/mine/store-pickup` |
| `AlpineCommerce_StoreLocator` | Localisateur de magasins | `alphacommerce_store_locator_store` | Aucune |
| `AlpineCommerce_Training` | Module de formation | Aucune | Aucune |
| `AlpineCommerce_EuVat` | Validation TVA UE | `alphacommerce_euvat_validation` | `/V1/alphacommerce/euvat/*` |
| `AlpineCommerce_LoyaltyProgram` | Programme de fidélité | `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points` | `/V1/carts/mine/loyalty-points` |
| `AlpineCommerce_ProductReviews` | Avis produits | `alphacommerce_product_review`, `alphacommerce_product_review_image`, `alphacommerce_product_review_helpful` | `/V1/alphacommerce/product-reviews/*` |
| `AlpineCommerce_ProductQuestions` | Q&R produit | `alphacommerce_product_question`, `alphacommerce_product_answer`, `alphacommerce_product_question_vote` | `/V1/alphacommerce/product-questions/*` |
| `AlpineCommerce_ProductLabels` | Étiquettes produits | `alphacommerce_product_label`, `alphacommerce_product_label_product` | `/V1/alphacommerce/product-labels/*` |

### Principes d'un module AlpineCommerce

- **Une seule responsabilité** : un module = une fonctionnalité métier
- **Indépendance** : pas de dépendance entre modules AlpineCommerce
- **Service Contracts** : chaque module expose ses interfaces dans `Api/`
- **db_schema.xml** : pas de `InstallSchema` ou `InstallData`
- **Pas de logique métier dans les Controllers** : les Controllers délèguent aux Services

---

## 4. Backend : patterns utilisés

| Pattern | Usage |
|---|---|
| **Service Contract** | Interface métier exposée dans `Api/` |
| **Repository** | Accès aux données, implémente le Service Contract |
| **Resource Model** | Opérations CRUD sur les tables |
| **Collection** | Liste d'entités avec filtres et tris |
| **Plugin** | Modification du comportement d'une méthode existante |
| **Observer** | Réaction à un événement Magento |
| **ViewModel** | Logique de présentation pour les templates PHTML |
| **Factory** | Création d'objets (généré automatiquement) |
| **Preference** | Liaison interface → implémentation dans `di.xml` |
| **VirtualType** | Type virtuel pour configuration DI complexe |

### Structure d'un module

```
AlpineCommerce_Module/
├── registration.php              # Enregistrement du module
├── etc/
│   ├── module.xml                # Nom, version, séquences
│   ├── db_schema.xml             # Tables et colonnes
│   ├── di.xml                    # Préférences, plugins, virtualTypes
│   ├── events.xml                # Observers
│   ├── webapi.xml                # Routes REST API
│   ├── acl.xml                   # Permissions admin
│   ├── frontend/
│   │   ├── di.xml                # Plugins frontend
│   │   └── routes.xml            # Routes frontend
│   └── adminhtml/
│       ├── routes.xml            # Routes admin
│       ├── menu.xml              # Menu admin
│       └── system.xml            # Configuration admin
├── Api/
│   ├── ModuleInterface.php       # Service Contract principal
│   └── Data/                     # Data Interfaces
├── Model/
│   ├── Entity.php                # Entité métier
│   ├── EntityRepository.php      # Repository (CRUD)
│   ├── ResourceModel/
│   │   ├── Entity.php            # Resource Model
│   │   └── Entity/
│   │       └── Collection.php    # Collection
│   └── Service.php               # Logique métier
├── Block/                        # Blocks PHTML (backend)
├── Controller/                   # Controllers
├── Plugin/                       # Plugins
├── Observer/                     # Observers
├── Console/Command/              # Commandes CLI
├── view/
│   ├── adminhtml/                # Templates et layouts admin
│   └── frontend/                 # Templates et layouts frontend
└── i18n/                         # Traductions
```

> ⚠️ Note : la référence canonique complète (avec `Ui/`, `Setup/Patch/`, etc.) est
> documentée dans `ENGINEERING_GUIDE.md` → « Squelette d'un module canonique ».

---

## 5. Base de données

### Principes

- Utiliser exclusivement `db_schema.xml` (déclaratif)
- Pas de `InstallSchema.php` ou `InstallData.php`
- Préférer les Data Patches (`Setup/Patch/Data/`) pour les insertions initiales
- Nommage : `alphacommerce_{module}_{table}`

### Tables par module

| Module | Tables |
|---|---|
| `Blog` | `alphacommerce_blog_post`, `alphacommerce_blog_category` |
| `Faq` | `alphacommerce_faq` |
| `Gdpr` | `alphacommerce_gdpr_consent_log` |
| `LegalPages` | `alphacommerce_legal_page` |
| `StorePickup` | `alphacommerce_pickup_store_info` (+ colonnes `quote`/`sales_order`) |
| `StoreLocator` | `alphacommerce_store_locator_store` |
| `ProductReviews` | `alphacommerce_product_review`, `alphacommerce_product_review_image`, `alphacommerce_product_review_helpful` |
| `ProductQuestions` | `alphacommerce_product_question`, `alphacommerce_product_answer`, `alphacommerce_product_question_vote` |
| `ProductLabels` | `alphacommerce_product_label`, `alphacommerce_product_label_product` |
| `LoyaltyProgram` | `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points` |
| `EuVat` | `alphacommerce_euvat_validation` |

---

## 6. REST API

### Principes

- Uniquement REST API (pas de GraphQL pour l'instant — voir ADR-006)
- Routes définies dans `etc/webapi.xml`
- Authentification : `self` (client connecté) ou `anonymous`
- Toutes les routes exposent des Service Contracts

### Routes existantes

| Module | Route | Méthode | Authentification |
|---|---|---|---|
| `Blog` | `/V1/alphacommerce/blog/*` | GET/POST | Mixte |
| `Faq` | `/V1/alphacommerce/faqs` | GET | Anonymous |
| `Gdpr` | `/V1/alphacommerce/gdpr/*` | POST/GET/DELETE | Mixte |
| `LegalPages` | `/V1/alphacommerce/legal-pages/*` | GET | Anonymous |
| `StorePickup` | `/V1/carts/mine/store-pickup` | GET/POST | Self |
| `LoyaltyProgram` | `/V1/carts/mine/loyalty-points` | POST | Self |
| `EuVat` | `/V1/alphacommerce/euvat/*` | GET/POST | Mixte |
| `ProductReviews` | `/V1/alphacommerce/product-reviews/*` | GET/POST | Mixte |
| `ProductQuestions` | `/V1/alphacommerce/product-questions/*` | GET/POST | Mixte |
| `ProductLabels` | `/V1/alphacommerce/product-labels/*` | GET/POST/DELETE | Mixte |

---

## 7. Multi Store

### Configuration

- Magento gère nativement le multi-store
- Les modules AlpineCommerce utilisent les scopes Magento
- Les données peuvent être filtrées par `store_id` ou `website_id`
- Le module `Hreflang` gère les balises hreflang pour le SEO multi-boutiques

### Bonnes pratiques

- Utiliser `\Magento\Store\Model\StoreManagerInterface` pour récupérer le store courant
- Ne jamais hardcoder un `store_id`
- Préférer les repositories avec filtres par store

---

## 8. Sécurité

### Principes

- **ACL** : chaque module admin protégé par des ACLs
- **Input validation** : validation systématique des entrées
- **Output escaping** : échappement systématique des sorties
- **Prepared statements** : via les Resource Models
- **REST API** : authentification par token ou session

### Checklist

- [ ] ACLs définies pour chaque controller admin
- [ ] Validation des paramètres d'entrée
- [ ] Échappement HTML dans les templates
- [ ] Pas de données sensibles en log
- [ ] Règles de validation Magento utilisées

---

## 9. Performance

### Principes

- **Cache** : utilisation du cache Magento (config, layout, block_html, full_page)
- **Indexers** : pas d'indexeur personnalisé sans justification
- **Collections** : chargement paginé, pas de `load()` complet
- **Queries** : pas de requête SQL brute sans justification
- **EAV** : utilisation correcte des attributs EAV
- **Cache de configuration** : `config.xml` pour les valeurs par défaut

### Outils

- `bin/magento cache:clean`
- `bin/magento indexer:reindex`
- `bin/magento setup:di:compile`
- Blackfire / Xdebug pour le profiling

---

## 10. Frontend

### Stack technique

- **Framework** : React
- **Bundler** : Vite
- **CSS** : Tailwind CSS

### Architecture

```
frontend/
├── src/
│   ├── components/      # Composants React réutilisables
│   ├── pages/           # Pages de l'application
│   ├── hooks/           # Custom hooks
│   ├── services/        # Appels API REST
│   ├── store/           # State management
│   └── main.jsx         # Point d'entrée
├── public/
├── vite.config.js
└── tailwind.config.js
```

### Principes

- **Separation of Concerns** : composants présentationnels vs conteneurs
- **Custom Hooks** : logique réutilisable
- **Services API** : centralisation des appels REST
- **TypeScript** : typage fort (à confirmer)

---

## 11. Déploiement

### Environnements

- **Développement** : Docker, mode developer
- **Staging** : Pré-production, mode production
- **Production** : Live, mode production, cache activé

### Processus

1. Commit sur Git
2. CI/CD (tests, lint, analyse statique)
3. Déploiement sur staging
4. Validation fonctionnelle
5. Déploiement en production

### Commandes de déploiement

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
bin/magento indexer:reindex
```

---

## 12. Décisions d'architecture (ADR)

### Format d'une décision

Chaque décision d'architecture est documentée selon le format ADR (Architecture Decision Record).

```
ADR-XXX
Titre de la décision

Statut: Accepté | Rejeté | Déprécié | Remplacé par ADR-YYY
Date: YYYY-MM-DD
Décideurs: [liste]

Contexte:
Description du contexte et du problème.

Décision:
Description de la décision prise.

Justification:
Pourquoi cette décision a été prise.

Impact:
Conséquences de cette décision.
```

### Registre des décisions

| ADR | Titre | Statut |
|---|---|---|
| ADR-001 | Magento reste le cœur de l'application | Accepté |
| ADR-002 | AlpineCommerce développe uniquement des fonctionnalités métier | Accepté |
| ADR-003 | Étendre Magento plutôt que le remplacer | Accepté |
| ADR-004 | Chaque module possède une seule responsabilité | Accepté |
| ADR-005 | Toutes les APIs utilisent les Service Contracts | Accepté |
| ADR-006 | Le projet utilise uniquement REST API | Accepté |
| ADR-007 | Chaque Sprint se termine par un audit complet | Accepté |
| ADR-008 | Toute nouvelle décision devra être ajoutée dans ce document | Accepté |
| ADR-009 | Migration depuis Cartware vers AlpineCommerce | Accepté |
| ADR-014 | Architecture des modules ProductReviews et ProductQuestions | Accepté |
| ADR-010 | Frontend React vs PWA Studio | À décider |
| ADR-011 | GraphQL pour les APIs publiques | À décider |
| ADR-012 | Tests automatisés en CI/CD | À décider |
| ADR-013 | Stratégie de déploiement | À décider |

### ADR-001 : Magento reste le cœur de l'application

- **Statut** : Accepté — **Date** : 2024-01-01

Magento 2 Open Source reste le cœur de l'application. Toutes les fonctionnalités natives de Magento sont utilisées telles quelles.
Justification : Magento est mature, sécurisé et éprouvé ; la communauté est active ; les fonctionnalités natives (catalogue, checkout, paiement) sont complexes à réécrire.
Impact : aucune réécriture des fonctionnalités Magento ; les modules AlpineCommerce complètent Magento ; les mises à jour restent possibles.

### ADR-002 : AlpineCommerce développe uniquement des fonctionnalités métier

- **Statut** : Accepté — **Date** : 2024-01-01

Les modules AlpineCommerce ne font que des fonctionnalités métier que Magento ne propose pas nativement.
Impact : pas de module `AlpineCommerce_Catalog`, `AlpineCommerce_Customer`, etc. ; les modules sont des extensions métier pures.

### ADR-003 : Étendre Magento plutôt que le remplacer

- **Statut** : Accepté — **Date** : 2024-01-01

Étendre Magento via Plugins, Observers, Layout XML, ViewModels avant de créer un nouveau module.
Justification : moins de code à maintenir, meilleure compatibilité avec les mises à jour Magento, respect des conventions.
Impact : utilisation systématique des Plugins et Observers ; pas de duplication de code Magento.

### ADR-004 : Chaque module possède une seule responsabilité

- **Statut** : Accepté — **Date** : 2024-01-01

Chaque module AlpineCommerce a une seule responsabilité métier et ne dépend pas des autres modules AlpineCommerce.
Impact : pas de dépendances entre modules AlpineCommerce ; chaque module peut être activé/désactivé indépendamment.

### ADR-005 : Toutes les APIs utilisent les Service Contracts

- **Statut** : Accepté — **Date** : 2024-01-01

Toutes les routes REST API exposent des Service Contracts (interfaces dans `Api/`).
Impact : tous les modules avec API REST ont un `Api/` directory ; les Controllers utilisent les interfaces, pas les implémentations.

### ADR-006 : Le projet utilise uniquement REST API

- **Statut** : Accepté — **Date** : 2024-01-01

Le projet utilise uniquement REST API pour l'instant. GraphQL n'est pas exclu pour le futur.
Justification : REST est plus simple à mettre en place, l'équipe maîtrise REST, les besoins actuels sont couverts.
Impact : toutes les routes sont définies dans `webapi.xml` ; pas de schema GraphQL pour l'instant.

### ADR-007 : Chaque Sprint se termine par un audit complet

- **Statut** : Accepté — **Date** : 2024-01-01

Chaque sprint se termine par un audit technique complet avant de passer au suivant.
Impact : temps d'audit inclus dans chaque sprint ; aucun code non audité n'est considéré comme terminé.

### ADR-008 : Toute nouvelle décision devra être ajoutée dans ce document

- **Statut** : Accepté — **Date** : 2024-01-01

Toute nouvelle décision d'architecture sera ajoutée dans ce document avec le format ADR.
Impact : traçabilité des décisions ; documentation vivante ; référence pour toute l'équipe.

### ADR-009 : Migration depuis Cartware vers AlpineCommerce

- **Statut** : Accepté — **Date** : 2024-01-01

Tous les modules Cartware sont migrés vers AlpineCommerce avec changement du namespace PHP, du nom de module, des noms de tables DB, des référenceIds dans `db_schema.xml`, et conservation des fonctionnalités.
Impact : 10 modules migrés ; migration progressive module par module ; les modules Cartware restent actifs jusqu'à validation complète.

### ADR-014 : Architecture des modules ProductReviews et ProductQuestions

- **Statut** : Accepté — **Date** : 2026-08-04

Décisions :
1. **Routes isolées** : `productreviews` et `productquestions` comme frontName pour éviter tout conflit avec les routes natives Magento (`review`).
2. **Injection produit** : `catalog_product_view.xml` pour injecter les blocs frontend sur la fiche produit, sans modifier le Core.
3. **3 tables séparées** : chaque entité (review, image, vote / question, answer, vote) a sa propre table avec clés étrangères et index.
4. **Vote utile désynchronisé** : le compteur `helpful_count` est incrémenté à la volée pour éviter les jointures coûteuses en lecture.
5. **Modération par statut** : workflow (pending → approved/rejected).
6. **Réponses officielles** : champ `is_official` pour distinguer les réponses admin des réponses clients.

Impact : pas de conflit avec les modules Magento natifs ; performance optimisée en lecture ; modération complète côté admin.

---

*Registre des décisions vivant : toute nouvelle décision y est ajoutée au format ADR (ADR-008).*
