# Guide de création de modules AlpineCommerce

## Principe fondamental

**Avant de créer un module, poser cette question :**

> Est-ce que Magento possède déjà cette fonctionnalité ?

- **Si OUI** → Étendre Magento via Plugin, Observer, Layout XML, ViewModel
- **Si NON** → Créer un module AlpineCommerce

---

## Quand créer un module AlpineCommerce ?

Un module AlpineCommerce doit être créé **uniquement** si Magento ne propose pas nativement la fonctionnalité métier.

### Exemples valides

| Fonctionnalité | Module AlpineCommerce | Justification |
|---|---|---|
| Blog | `AlpineCommerce_Blog` | Magento n'a pas de blog natif |
| FAQ | `AlpineCommerce_Faq` | Magento n'a pas de FAQ natif |
| Programme de fidélité | `AlpineCommerce_LoyaltyProgram` | Pas en Open Source |
| RGPD avancé | `AlpineCommerce_Gdpr` | Magento a des bases mais pas de gestion complète |
| Store Pickup | `AlpineCommerce_StorePickup` | Magento n'a pas de retrait en magasin natif |
| Localisateur de magasins | `AlpineCommerce_StoreLocator` | Magento n'a pas de store locator natif |
| Validation TVA UE | `AlpineCommerce_EuVat` | Magento n'a pas de validation VIES native |
| Pages légales | `AlpineCommerce_LegalPages` | Magento n'a pas de gestion de pages légales dédiée |

### Exemples invalides

| Fonctionnalité proposée | Pourquoi c'est invalide |
|---|---|
| `AlpineCommerce_Catalog` | Magento a `Magento_Catalog` → Étendre |
| `AlpineCommerce_Customer` | Magento a `Magento_Customer` → Étendre |
| `AlpineCommerce_Checkout` | Magento a `Magento_Checkout` → Étendre |
| `AlpineCommerce_Sales` | Magento a `Magento_Sales` → Étendre |
| `AlpineCommerce_Cms` | Magento a `Magento_Cms` → Étendre |
| `AlpineCommerce_Payment` | Magento a `Magento_Payment` → Étendre |
| `AlpineCommerce_Shipping` | Magento a `Magento_Shipping` → Étendre |

---

## Structure d'un module AlpineCommerce

```
AlpineCommerce_Module/
├── registration.php                      # Enregistrement du module
├── etc/
│   ├── module.xml                        # Nom du module, version, séquences
│   ├── db_schema.xml                     # Tables et colonnes (déclaratif)
│   ├── di.xml                            # Préférences, plugins, virtualTypes
│   ├── events.xml                        # Observers (si nécessaire)
│   ├── webapi.xml                        # Routes REST API (si nécessaire)
│   ├── acl.xml                           # Permissions admin (si nécessaire)
│   ├── config.xml                        # Valeurs par défaut (si nécessaire)
│   ├── system.xml                        # Configuration admin (si nécessaire)
│   ├── frontend/
│   │   ├── di.xml                        # Plugins frontend (si nécessaire)
│   │   └── routes.xml                    # Routes frontend (si nécessaire)
│   └── adminhtml/
│       ├── routes.xml                    # Routes admin (si nécessaire)
│       ├── menu.xml                      # Menu admin (si nécessaire)
│       └── system.xml                    # Configuration admin (si nécessaire)
├── Api/
│   ├── ServiceInterface.php              # Service Contract principal
│   ├── Data/
│   │   ├── EntityInterface.php           # Interface de l'entité
│   │   └── SearchResultsInterface.php    # Interface de résultats de recherche
├── Model/
│   ├── Entity.php                        # Entité métier
│   ├── EntityRepository.php              # Repository (implémente le Service Contract)
│   ├── ResourceModel/
│   │   ├── Entity.php                    # Resource Model (CRUD DB)
│   │   └── Entity/
│   │       └── Collection.php            # Collection avec filtres/tris
│   └── Service.php                       # Logique métier (si complexe)
├── Block/                                # Blocks PHTML (si templates)
├── Controller/                           # Controllers (si routes)
│   ├── Index/
│   │   └── Index.php
│   └── Adminhtml/
│       └── Entity/
│           ├── Index.php
│           ├── Edit.php
│           └── Save.php
├── Plugin/                               # Plugins (si extension de Magento)
├── Observer/                             # Observers (si événements)
├── Console/Command/                      # Commandes CLI (si nécessaire)
├── Ui/                                   # UI Components admin (si nécessaire)
├── view/
│   ├── adminhtml/
│   │   ├── layout/                       # Layouts admin
│   │   ├── templates/                    # Templates admin
│   │   └── ui_component/                 # UI Components
│   └── frontend/
│       ├── layout/                       # Layouts frontend
│       ├── templates/                    # Templates frontend
│       ├── web/js/                       # JavaScript
│       ├── web/css/                      # CSS/LESS
│       └── requirejs-config.js           # RequireJS (si JS)
├── i18n/                                 # Traductions
│   ├── fr_FR.csv
│   └── en_US.csv
└── Setup/
    └── Patch/
        └── Data/                         # Data Patches (si données initiales)
            └── CreateInitialData.php
```

---

## Quand utiliser chaque pattern ?

### Plugin vs Observer

| Critère | Plugin | Observer |
|---|---|---|
| **Usage** | Modifier une méthode existante | Réagir à un événement |
| **Dépendance** | Couplé à une classe spécifique | Découplé via événement |
| **Priorité** | `before` / `after` / `around` | Exécution après l'événement |
| **Cas d'usage** | Ajouter un comportement sur `Product::getName()` | Réagir à `sales_order_save_after` |

**Règle d'or** :
- Si tu veux modifier le comportement d'une méthode → **Plugin**
- Si tu veux réagir à un événement métier → **Observer**

### Preference vs Factory

| Critère | Preference | Factory |
|---|---|---|
| **Usage** | Lier une interface à une implémentation | Créer un objet |
| **Portée** | Global (tout le DI) | Local (un seul appel) |
| **Cas d'usage** | Service Contract → Implémentation | Création d'entités métier |

**Règle d'or** :
- Service Contract → **Preference** dans `di.xml`
- Création d'objets → **Factory** générée automatiquement

### ViewModel vs Block

| Critère | ViewModel | Block |
|---|---|---|
| **Usage** | Logique de présentation | Structure de page Magento |
| **Héritage** | `\Magento\Framework\View\Element\Template` | `\Magento\Framework\View\Element\Template` |
| **Cas d'usage** | Formater des données pour un template | Conteneur dans un layout XML |

**Règle d'or** :
- Si tu as besoin d'un conteneur dans un layout → **Block**
- Si tu as besoin de logique de présentation → **ViewModel**

---

## Quand créer une REST API ?

**Créer une route REST si :**

- Le frontend React a besoin de données métier
- Une intégration externe doit consommer le module
- Le module expose des fonctionnalités interactives (ex: voter pour une FAQ)

**Ne pas créer de REST API si :**

- Les données sont déjà accessibles via les endpoints Magento natifs
- Le module est purement backend (ex: Data Patch)
- Les données ne sont utilisées que dans les templates PHTML

### Structure webapi.xml

```xml
<?xml version="1.0"?>
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Webapi:etc/webapi.xsd">
    <!-- GET : liste publique -->
    <route url="/V1/alphacommerce/module/items" method="GET">
        <service class="AlpineCommerce\Module\Api\ServiceInterface" method="getItems"/>
        <resources>
            <resource ref="anonymous"/>
        </resources>
    </route>

    <!-- POST : authentifié client -->
    <route url="/V1/alphacommerce/module/action" method="POST">
        <service class="AlpineCommerce\Module\Api\ServiceInterface" method="doAction"/>
        <resources>
            <resource ref="self"/>
        </resources>

        </data>
    </route>
</routes>
```

## Quand créer une table ?

Créer une table uniquement si Magento ne dispose pas d'entité native adaptée.

- Préférer les attributs EAV de Magento si possible
- Créer une table personnalisée seulement pour des entités métier spécifiques
- Nommage : `alphacommerce_{module}_{table}` ou `alpinecommerce_{module}_{table}`

### Quand utiliser db_schema.xml ?

Toujours. Jamais `InstallSchema.php` ou `InstallData.php`.

- Déclaratif : Magento gère la création/migration des tables
- Versionné : les changements sont tracés
- Multi-environnements : fonctionne sur dev, staging, prod

### Quand créer un Repository ?

Toujours, pour toute entité métier ayant une table dédiée.

- Le Repository est l'unique point d'accès aux données
- Il implémente un Service Contract
- Il masque la complexité des Resource Models

### Quand créer un Service Contract ?

Dès qu'une fonctionnalité expose une API (REST, GraphQL, ou usage interne).

- Interface dans `Api/`
- Implémentation dans `Model/` ou `Service/`
- Preference dans `di.xml`

### Relations entre composants

```
Api/Data/EntityInterface.php      <- Interface de l'entité
Api/EntityRepositoryInterface.php <- Service Contract (CRUD)
Model/Entity.php                  <- Entité métier
Model/EntityRepository.php        <- Implémentation
Model/ResourceModel/Entity.php    <- Accès DB
Model/ResourceModel/Entity/
    └── Collection.php             <- Liste d'entités
```

## Checklist de validation d'un module

### Avant chaque commit

- [ ] `php -l` sur tous les fichiers PHP
- [ ] `phpcs` conforme PSR-12
- [ ] Pas de référence à un autre module AlpineCommerce (sauf autorisation)
- [ ] Service Contracts définis dans `Api/`
- [ ] `db_schema.xml` valide et cohérent avec les ResourceModels
- [ ] `module.xml` avec séquences correctes
- [ ] `di.xml` sans erreur
- [ ] `webapi.xml` avec authentification correcte
- [ ] `acl.xml` défini si controller admin
- [ ] Routes frontend et admin définies
- [ ] Traductions dans `i18n/`
- [ ] Pas de logique métier dans les Controllers
- [ ] Pas de `$objectManager->create()` dans le code métier
- [ ] Pas de SQL direct sans justification
- [ ] Pas de code mort (classes, méthodes, variables inutilisés)

### Avant chaque Sprint

- [ ] `setup:upgrade` passe sans erreur
- [ ] `setup:di:compile` passe sans erreur
- [ ] `module:status` affiche le module correctement
- [ ] `cache:clean` et `cache:flush` passent
- [ ] `indexer:reindex` passe
- [ ] Module testé en frontend et/ou backend
- [ ] Aucune erreur dans `var/log/system.log` et `var/log/exception.log`

## Références croisées

- Architecture générale : voir `01_ARCHITECTURE.md`
- Standards de code : voir `02_ENGINEERING_GUIDE.md`
- Workflow des sprints : voir `04_SPRINT_WORKFLOW.md`
- Décisions d'architecture : voir `06_DECISIONS.md`
