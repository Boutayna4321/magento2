# Architecture du projet AlpineCommerce

## Vue d'ensemble

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

## Architecture générale

### Principe fondamental

**Magento 2 est le cœur de l'application.**

Toutes les fonctionnalités natives de Magento sont utilisées telles quelles :

- Catalog
- Customer
- Checkout
- Sales
- Quote
- CMS
- Inventory (MSI)
- Payment
- Shipping
- Search
- Multi Store
- Cache
- Indexers
- REST API

Les modules AlpineCommerce viennent **compléter** Magento, jamais le remplacer.

---

## Magento Core

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

### Règles d'or

1. **Ne jamais recréer** un module Magento existant
2. **Étendre** via Plugin, Observer, Layout XML, ViewModel
3. **Utiliser** les Service Contracts de Magento
4. **Respecter** les conventions de nommage Magento

---

## Modules AlpineCommerce

### Modules existants

| Module | Responsabilité | Tables DB | API REST |
|---|---|---|---|
| `AlpineCommerce_Blog` | Blog multi-boutiques | `alphacommerce_blog_post`, `alphacommerce_blog_category` | `/V1/alphacommerce/blog/*` |
| `AlpineCommerce_Faq` | FAQ | `alphacommerce_faq` | `/V1/alphacommerce/faqs/*` |
| `AlpineCommerce_Gdpr` | Conformité RGPD | `alphacommerce_gdpr_consent_log` | `/V1/alphacommerce/gdpr/*` |
| `AlpineCommerce_Hreflang` | SEO hreflang | Aucune | Aucune |
| `AlpineCommerce_LegalPages` | Pages légales | `alphacommerce_legal_page` | `/V1/alphacommerce/legal-pages/*` |
| `AlpineCommerce_StorePickup` | Retrait en magasin | `alphacommerce_pickup_store_info` | `/V1/carts/mine/store-pickup` |
| `AlpineCommerce_StoreLocator` | Localisateur de magasins | Aucune | Aucune |
| `AlpineCommerce_Training` | Module de formation | Aucune | Aucune |
| `AlpineCommerce_EuVat` | Validation TVA UE | `alphacommerce_euvat_validation` | `/V1/alphacommerce/euvat/*` |
| `AlpineCommerce_LoyaltyProgram` | Programme de fidélité | `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points` | `/V1/carts/mine/loyalty-points` |

### Principes d'un module AlpineCommerce

- **Une seule responsabilité** : un module = une fonctionnalité métier
- **Indépendance** : pas de dépendance entre modules AlpineCommerce
- **Service Contracts** : chaque module expose ses interfaces dans `Api/`
- **db_schema.xml** : pas de `InstallSchema` ou `InstallData`
- **Pas de logique métier dans les Controllers** : les Controllers délèguent aux Services

---

## Frontend

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

## Backend

### Patterns utilisés

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

---

## Base de données

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
| `StorePickup` | `alphacommerce_pickup_store_info` |
| `LoyaltyProgram` | `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points` |
| `EuVat` | `alphacommerce_euvat_validation` |

---

## REST API

### Principes

- Uniquement REST API (pas de GraphQL pour l'instant)
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

---

## Multi Store

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

## Sécurité

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

## Performance

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

## Déploiement

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
