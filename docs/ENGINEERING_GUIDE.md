# Engineering Bible — AlpineCommerce

> Ce document est la **référence absolue** pour tout développement sur AlpineCommerce.
> Il est la Phase A de la roadmap : les règles ci-dessous sont **gelées**. Tout nouveau
> module (Phase B) doit les respecter sans exception. La dette existante est tracée dans
> `BACKLOG.md`.
>
> Ce document regroupe l'ancien `02_ENGINEERING_GUIDE.md`, `03_MODULE_GUIDELINES.md`,
> `04_SPRINT_WORKFLOW.md` et `07_GLOSSARY.md`.

## Ce que ce document contient

| Section | Contenu |
|---|---|
| Squelette d'un module canonique | L'arborescence obligatoire d'un module professionnel |
| Quand créer un module | La question à poser avant toute création |
| Principes fondamentaux | SOLID, DRY, KISS, YAGNI, Clean Code |
| Standards de code | PSR-12, conventions, exemples |
| Patterns officiels Adobe Commerce | Repository, Service Contracts, DI, Plugins, Observers, etc. |
| REST API | webapi.xml, authentification |
| ACL / Layout / UI Components | Les trois piliers de l'admin |
| i18n / Logging / Erreurs / Tests | Les bonnes pratiques transverses |
| Workflow des sprints | Le cycle de vie d'un sprint et le rôle de l'AI |
| ❌ Ce qu'il ne faut JAMAIS faire | Les anti-patterns, pourquoi, et l'alternative |
| Checklist de validation | À exécuter avant chaque commit et chaque sprint |
| Glossaire | Les termes Magento |

**Module de référence** : `AlpineCommerce/Faq` est le module canonique — comparez toujours
votre code à sa structure.

---

## Squelette d'un module canonique

> Toute nouvelle entité métier suit **exactement** cette arborescence (dérivée de
> `AlpineCommerce/Faq` et `AlpineCommerce/ProductLabels`).

```
AlpineCommerce/{Module}/
├── registration.php                  # Enregistrement Composer
├── etc/
│   ├── module.xml                    # Déclaration + séquence
│   ├── db_schema.xml                 # Schéma de base (jamais InstallSchema/InstallData)
│   ├── di.xml                        # preferences (interfaces → implémentations)
│   ├── webapi.xml                    # Routes REST (si API exposée)
│   ├── acl.xml                       # Ressources admin (si admin)
│   ├── menu.xml                      # Menu admin (adminhtml/)
│   └── routes.xml                    # adminhtml/ ou frontend/ selon la zone
├── Api/
│   ├── {Entity}Interface.php         # Data Interface (Service Contract)
│   ├── {Entity}SearchResultsInterface.php
│   ├── {Entity}RepositoryInterface.php
│   └── ...                           # Autres interfaces métier
├── Model/
│   ├── {Entity}.php                  # Model (données)
│   ├── {Entity}Repository.php        # Implémentation du Repository
│   ├── {Entity}SearchResults.php
│   └── ResourceModel/
│       ├── {Entity}.php              # ResourceModel (accès table)
│       └── {Entity}/Collection.php   # Collection (listes filtrées/paginées)
├── Controller/
│   ├── Index/                        # Controllers frontend (zone frontend)
│   └── Adminhtml/{Entity}/           # Controllers admin (zone adminhtml)
│       ├── Index.php                 # Grille
│       ├── NewAction.php             # Formulaire vierge
│       ├── Edit.php                  # Formulaire pré-rempli
│       ├── Save.php                  # Persistance (délègue au Repository)
│       └── Delete.php / MassDelete.php
├── Block/                            # Blocks frontend (+ admin si nécessaire)
├── Ui/
│   ├── DataProvider/                 # DataProviders des grilles/formulaires
│   └── Component/                    # Colonnes custom (actions, ...)
├── Plugin/                           # Plugins (interception)
├── Observer/                         # Observers (événements)
├── Console/                          # Commandes CLI
├── Setup/Patch/                      # Data/Schema Patches
├── view/
│   ├── adminhtml/ui_component/       # Grilles + formulaires UI Components
│   ├── adminhtml/layout/             # Layouts admin
│   ├── frontend/layout/              # Layouts frontend
│   └── frontend/templates/           # Templates PHTML
└── i18n/                             # Traductions CSV
```

**Règles associées**
- Aucun de ces dossiers n'est **optionnel au choix** : s'il manque, la justification doit
  être écrite dans la doc du module (décision assumée).
- Le dossier `Api/` n'est pas un détail : c'est **la promesse publique** du module.

---

## Quand créer un module AlpineCommerce ?

**Principe fondamental** — avant de créer un module, poser cette question :

> Est-ce que Magento possède déjà cette fonctionnalité ?

- **Si OUI** → Étendre Magento via Plugin, Observer, Layout XML, ViewModel
- **Si NON** → Créer un module AlpineCommerce

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

## Principes fondamentaux

### SOLID

- **S**ingle Responsibility : chaque classe a une seule raison de changer
- **O**pen/Closed : ouvert à l'extension, fermé à la modification
- **L**iskov Substitution : une implémentation peut remplacer son interface
- **I**nterface Segregation : interfaces petites et spécifiques
- **D**ependency Inversion : dépendre d'abstractions, pas de concret

### DRY (Don't Repeat Yourself)

- Pas de duplication de code
- Extraire la logique commune dans des services, helpers ou traits
- Les configurations XML doivent être factorisées

### KISS (Keep It Simple, Stupid)

- Privilégier la simplicité
- Éviter la sur-ingénierie
- Une solution simple > une solution complexe

### YAGNI (You Ain't Gonna Need It)

- Ne pas développer de fonctionnalité "au cas où"
- Développer seulement ce qui est nécessaire maintenant
- Supprimer le code mort

### Clean Code

- Noms explicites (variables, méthodes, classes)
- Fonctions courtes (< 20 lignes idéalement)
- Pas de commentaires inutiles (le code doit être auto-explicite)
- Gestion des erreurs explicite
- Pas de code mort

---

## Standards de code

### PSR-12

Tout le code PHP doit respecter la norme **PSR-12**.

```bash
# Vérification avec PHP_CodeSniffer
vendor/bin/phpcs --standard=PSR12 app/code/AlpineCommerce/

# Correction automatique
vendor/bin/phpcbf --standard=PSR12 app/code/AlpineCommerce/
```

### Conventions Magento

- **Classes** : `PascalCase`
- **Méthodes** : `camelCase`
- **Variables** : `$camelCase`
- **Constantes** : `UPPER_SNAKE_CASE`
- **Fichiers** : correspond au nom de la classe
- **Namespaces** : `AlpineCommerce\Module\SousNamespace`

### Exemple de code conforme

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogPostRepositoryInterface
{
    public const ENTITY_ID = 'entity_id';

    public function getById(int $id): BlogPostInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): BlogPostSearchResultsInterface;

    public function save(BlogPostInterface $blogPost): BlogPostInterface;

    public function delete(BlogPostInterface $blogPost): bool;
}
```

---

## Patterns officiels Adobe Commerce

### Repository Pattern

**Usage** : Accès aux données, masque la complexité des Resource Models.

**Structure** :

```
Api/
  └── EntityRepositoryInterface.php    # Interface (Service Contract)
Model/
  └── EntityRepository.php             # Implémentation
```

**Exemple** :

```php
// Api/EntityRepositoryInterface.php
interface EntityRepositoryInterface
{
    public function getById(int $id): EntityInterface;
    public function save(EntityInterface $entity): EntityInterface;
    public function delete(EntityInterface $entity): bool;
}

// di.xml
<preference for="AlpineCommerce\Module\Api\EntityRepositoryInterface"
            type="AlpineCommerce\Module\Model\EntityRepository"/>
```

### Service Contracts

**Définition** : Interfaces définies dans `Api/` qui exposent les fonctionnalités métier.

**Règles** :
- Toute logique métier doit être derrière un Service Contract
- Les Controllers ne font jamais de logique métier directement
- Les Controllers délèguent aux Services/Repositories

**Quand en créer un** : dès qu'une fonctionnalité expose une API (REST, GraphQL, ou usage interne).

### Dependency Injection

**Définition** : Injection des dépendances via le constructeur.

**Règles** :
- Toujours typer les paramètres du constructeur (`private readonly`)
- Ne jamais utiliser `$objectManager->create()` dans le code métier
- Utiliser les factories générées automatiquement par Magento

```php
public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly LoggerInterface $logger
) {}
```

### Plugins (Interceptors)

**Usage** : Modifier le comportement d'une méthode existante sans la toucher.

**Quand utiliser** :
- Ajouter du comportement avant/après/autour d'une méthode Magento
- Modifier un retour sans override de classe
- Ajouter de la logique métier sur un code existant

**Quand ne PAS utiliser** :
- Pour remplacer complètement une méthode → préférer une Preference
- Pour la logique métier → créer un Service

```php
// etc/di.xml
<type name="Magento\Catalog\Model\Product">
    <plugin name="alpinecommerce_product_plugin"
            type="AlpineCommerce\Module\Plugin\ProductPlugin"
            sortOrder="10"/>
</type>

// Plugin/ProductPlugin.php
class ProductPlugin
{
    public function beforeGetName(\Magento\Catalog\Model\Product $subject): array
    {
        // Avant l'appel à getName()
    }

    public function afterGetName(\Magento\Catalog\Model\Product $subject, string $result): string
    {
        // Après l'appel à getName()
        return strtoupper($result);
    }

    public function aroundGetName(\Magento\Catalog\Model\Product $subject, \Closure $proceed): string
    {
        // Autour de l'appel à getName()
        return $proceed();
    }
}
```

### Observers

**Usage** : Réagir à un événement Magento.

**Quand utiliser** :
- Réagir à un événement métier (commande passée, facture créée)
- Découpler la logique métier
- Plusieurs listeners sur le même événement

**Quand ne PAS utiliser** :
- Pour modifier un comportement → préférer un Plugin
- Pour la logique métier critique → préférer un Service direct

```php
// etc/events.xml
<event name="sales_order_save_after">
    <observer name="alpinecommerce_order_save_observer"
              instance="AlpineCommerce\Module\Observer\OrderSaveObserver"/>
</event>

// Observer/OrderSaveObserver.php
class OrderSaveObserver
{
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        // Logique métier
    }
}
```

### Plugin vs Observer — règle d'or

| Critère | Plugin | Observer |
|---|---|---|
| **Usage** | Modifier une méthode existante | Réagir à un événement |
| **Dépendance** | Couplé à une classe spécifique | Découplé via événement |
| **Priorité** | `before` / `after` / `around` | Exécution après l'événement |
| **Cas d'usage** | Ajouter un comportement sur `Product::getName()` | Réagir à `sales_order_save_after` |

- Si tu veux modifier le comportement d'une méthode → **Plugin**
- Si tu veux réagir à un événement métier → **Observer**

### Preference vs Factory — règle d'or

| Critère | Preference | Factory |
|---|---|---|
| **Usage** | Lier une interface à une implémentation | Créer un objet |
| **Portée** | Global (tout le DI) | Local (un seul appel) |
| **Cas d'usage** | Service Contract → Implémentation | Création d'entités métier |

- Service Contract → **Preference** dans `di.xml`
- Création d'objets → **Factory** générée automatiquement

### ViewModel vs Block — règle d'or

| Critère | ViewModel | Block |
|---|---|---|
| **Usage** | Logique de présentation | Structure de page Magento |
| **Héritage** | `\Magento\Framework\View\Element\Template` | `\Magento\Framework\View\Element\Template` |
| **Cas d'usage** | Formater des données pour un template | Conteneur dans un layout XML |

- Si tu as besoin d'un conteneur dans un layout → **Block**
- Si tu as besoin de logique de présentation → **ViewModel**

### Preference (DI)

**Usage** : Lier une interface à une implémentation concrète dans `di.xml`.

**Règles** :
- Utiliser uniquement pour les Service Contracts
- Une seule Preference par interface
- Préférer les factories pour la création d'objets

```xml
<!-- etc/di.xml -->
<preference for="AlpineCommerce\Module\Api\RepositoryInterface"
            type="AlpineCommerce\Module\Model\Repository"/>
```

### ViewModel

**Usage** : Logique de présentation pour les templates PHTML.

```php
// Block/Product/ViewModel.php
class ViewModel extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function getProduct(): ProductInterface
    {
        return $this->productRepository->getById($this->getProductId());
    }
}
```

### Resource Models

**Usage** : Opérations CRUD sur les tables de base de données.

**Règles** :
- Hériter de `\Magento\Framework\Model\ResourceModel\Db\AbstractDb`
- Définir `_construct()` avec `_init($tableName, $primaryKey)`
- Ne pas utiliser de SQL direct sans justification

```php
class Entity extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_entity', 'entity_id');
    }
}
```

### Collections

**Usage** : Liste d'entités avec filtres, tris et pagination.

**Règles** :
- Toujours utiliser `addFieldToFilter()` au lieu de WHERE manuel
- Limiter les résultats avec `setPageSize()` et `setCurPage()`
- Ne jamais charger une collection complète sans pagination

```php
$collection = $this->entityCollectionFactory->create();
$collection->addFieldToFilter('is_active', 1)
    ->setOrder('created_at', 'DESC')
    ->setPageSize(20)
    ->setCurPage(1);
```

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

---

## REST API

### webapi.xml

**Structure** :

```xml
<?xml version="1.0"?>
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Webapi:etc/webapi.xsd">
    <route url="/V1/alphacommerce/module/endpoint" method="GET">
        <service class="AlpineCommerce\Module\Api\ServiceInterface" method="getItems"/>
        <resources>
            <resource ref="self"/> <!-- ou "anonymous" -->
        </resources>
    </route>
</routes>
```

### Authentification

| Valeur | Signification |
|---|---|
| `self` | Client connecté (customer token) |
| `anonymous` | Accès public |
| `admin` | Administrateur connecté |

### Quand créer une REST API ?

**Créer une route REST si :**
- Le frontend React a besoin de données métier
- Une intégration externe doit consommer le module
- Le module expose des fonctionnalités interactives (ex : voter pour une FAQ)

**Ne pas créer de REST API si :**
- Les données sont déjà accessibles via les endpoints Magento natifs
- Le module est purement backend (ex : Data Patch)
- Les données ne sont utilisées que dans les templates PHTML

### Bonnes pratiques

- Toutes les routes exposent des Service Contracts
- Validation des paramètres dans le Service
- Retour d'objets Data Interface (pas de arrays)
- Gestion des erreurs avec exceptions Magento

---

## ACL (Access Control List)

### Structure

```xml
<!-- etc/acl.xml -->
<acl>
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="AlpineCommerce_Module::menu" title="Menu Title" sortOrder="10">
                <resource id="AlpineCommerce_Module::entity" title="Manage Entities" sortOrder="10"/>
            </resource>
        </resource>
    </resources>
</acl>
```

### Règles

- Une ACL par ressource protégée
- Les Controllers admin vérifient `ADMIN_RESOURCE`
- Les menus utilisent la même ACL

---

## Layout XML

### Principes

- Les layouts définissent la structure des pages
- Utiliser `referenceContainer` et `referenceBlock` pour modifier
- Ne pas dupliquer les layouts, utiliser `reference` pour étendre
- ⚠️ Vérifier le type de la cible : `referenceContainer` ne fonctionne que sur un vrai
  `<container>` ; sur un `<block>` il faut `referenceBlock` (sinon les blocs sont
  silencieusement ignorés — cf. bug corrigé sur `catalog_product_view.xml` de ProductLabels).

```xml
<!-- view/frontend/layout/cms_index_index.xml -->
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\Listing"
                   name="alphacommerce.blog.listing"
                   template="AlpineCommerce_Blog::listing.phtml"/>
        </referenceContainer>
    </body>
</page>
```

---

## UI Components

### Usage

Les UI Components sont utilisés pour les grilles et formulaires dans l'admin Magento.

**Types principaux** :
- `listing` : grille de données
- `form` : formulaire d'édition
- `dataSource` : source de données

### Structure (format Magento 2.4.x)

> **⚠️ Attention** : ce format a changé en 2.4.x. Le `<dataSource>` doit contenir un
> enfant `<dataProvider class="..." name="...">`. Sans lui, la grille plante au chargement
> (exception `ConfigurableObject`). Référence fonctionnelle : `productlabels_label_grid.xml`.

```xml
<!-- view/adminhtml/ui_component/entity_listing.xml -->
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <settings>
        <buttons>
            <button name="add">
                <url path="*/entity/new"/>
                <class>primary</class>
                <label translate="true">Add New Entity</label>
            </button>
        </buttons>
    </settings>
    <dataSource name="entity_listing_data_source">
        <dataProvider class="AlpineCommerce\Module\Ui\DataProvider\EntityListingDataProvider"
                      name="entity_listing_data_source"/>
        <settings>
            <submitUrl path="mui/index/render"/>
            <updateUrl path="mui/index/render"/>
        </settings>
    </dataSource>
    <columns name="entity_columns">
        <column name="entity_id">
            <settings>
                <label translate="true">ID</label>
            </settings>
        </column>
    </columns>
</listing>
```

**Ce qui ne doit JAMAIS apparaître** (format obsolète, cf. `BACKLOG.md` → B-01) :
`<primaryDataSource>`, `<templates><filters><select customScope="...">`, et un
`<dataSource>` sans `<dataProvider class=...>`.

**Boutons de formulaire** : ne jamais utiliser `<container name="button_set" component="Magento_Ui/js/form/components/button-set">` —
ce composant JS **n'existe pas en Magento 2.4.8** et laisse le formulaire vide dans le navigateur.
Utiliser `<settings><buttons>` + des classes `ButtonProviderInterface`
(`{GenericButton,SaveButton,BackButton}.php`).

---

## i18n (Traductions)

### Structure

```
i18n/
├── fr_FR.csv
├── en_US.csv
└── de_DE.csv
```

### Format CSV

```csv
"Original string","Traduction"
"Save","Enregistrer"
"Delete","Supprimer"
```

### Utilisation dans le code

```php
// PHP
__('Save')

// PHTML
<?= __('Save') ?>
```

---

## Logging

### PSR-3

Utiliser l'interface `\Psr\Log\LoggerInterface` :

```php
public function __construct(
    private readonly LoggerInterface $logger
) {}

public function doSomething(): void
{
    $this->logger->info('Action performed', ['entity_id' => 123]);
    $this->logger->error('Error occurred', ['exception' => $e]);
}
```

---

## Gestion des erreurs

### Exceptions Magento

| Exception | Usage |
|---|---|
| `NoSuchEntityException` | Entité introuvable |
| `CouldNotSaveException` | Erreur lors de la sauvegarde |
| `CouldNotDeleteException` | Erreur lors de la suppression |
| `LocalizedException` | Erreur métier générique |

### Bonnes pratiques

- Toujours utiliser les exceptions Magento
- Ne jamais exposer les détails techniques en production
- Logger les erreurs avec contexte

---

## Base de données

### Quand créer une table ?

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

---

## Tests

### Structure

```
Test/
├── Unit/
│   └── Service/
│       └── BlogPostServiceTest.php
├── Integration/
│   └── Repository/
│       └── BlogPostRepositoryTest.php
└── functional/
    └── BlogFrontendTest.php
```

### Règles

- Tests unitaires pour la logique métier pure
- Tests d'intégration pour les Repositories et Services
- Tests fonctionnels pour les scénarios utilisateur
- Couverture minimum : 80%

---

## Workflow des sprints

### Philosophie

Chaque sprint est une itération fermée, traçable et validée.

Nous ne développons jamais plusieurs fonctionnalités en parallèle.
Nous ne faisons jamais de refactoring non demandé.
Nous ne modifions jamais un module sans validation.

### Cycle de vie d'un Sprint

```
┌─────────────┐
│   ANALYSE    │  Comprendre le besoin métier
│   EXISTANT   │  et l'existant technique
└──────┬──────┘
       ▼
┌─────────────┐
│   PLANIF.    │  Proposer l'architecture
│  APPROUVA.  │  et attendre validation
└──────┬──────┘
       ▼
┌─────────────┐
│ DÉVELOPPEM. │  Coder le module ou
│  PROGRESSIF │  l'extension Magento
└──────┬──────┘
       ▼
┌─────────────┐
│ COMPILATION │  setup:upgrade, di:compile,
│  & TESTS    │  cache:clean, indexer:reindex
└──────┬──────┘
       ▼
┌─────────────┐
│    AUDIT     │  Vérifier la conformité
│  TECHNIQUE  │  avec les standards
└──────┬──────┘
       ▼
┌─────────────┐
│   RAPPORT    │  Documenter ce qui a été
│  & STOP     │  fait, puis s'arrêter
└─────────────┘
```

### Rôle de l'AI pendant un Sprint

L'AI est un **Tech Lead et Software Architect**.

**Responsabilités** :
- Analyser l'existant
- Expliquer les choix techniques
- Proposer l'architecture
- Coder les fonctionnalités validées
- Vérifier la conformité (PSR-12, Magento Best Practices)
- Produire des rapports d'audit
- Documenter les décisions

**Ce que l'AI ne fait PAS** :
- Proposer des refactorings non demandés
- Modifier du code sans validation
- Créer des modules sans justification
- Développer plusieurs fonctionnalités en parallèle

### Étapes détaillées

**Étape 1 : Analyse de l'existant** — lister les fichiers, vérifier les dépendances,
identifier les patterns, détecter les problèmes. *Livrable : état des lieux complet.*

**Étape 2 : Planification et validation** — expliquer le besoin, proposer l'architecture,
attendre la validation. *Livrable : plan de travail approuvé.*

**Étape 3 : Développement progressif** — un fichier à la fois, valider chaque étape. *Livrable : code fonctionnel.*

**Étape 4 : Compilation et tests** — commandes obligatoires :

```bash
# Mise à jour de la base de données
bin/magento setup:upgrade

# Compilation du DI
bin/magento setup:di:compile

# Déploiement du contenu statique (si nécessaire)
bin/magento setup:static-content:deploy

# Nettoyage du cache
bin/magento cache:clean
bin/magento cache:flush

# Réindexation
bin/magento indexer:reindex

# Vérification du statut des modules
bin/magento module:status
```

**Étape 5 : Audit technique** — conformité PSR-12, conventions Magento, cohérence des
namespaces, absence de code mort, absence de références à d'autres modules AlpineCommerce.

**Étape 6 : Rapport et STOP** — produire un rapport (résumé, fichiers modifiés/créés,
validation des commandes, prochaines étapes), **puis s'arrêter et attendre la validation.**

### Règles strictes

**Jamais** :
- ❌ Modifier plusieurs fonctionnalités en même temps
- ❌ Faire du refactoring non demandé
- ❌ Modifier un autre module sans validation
- ❌ Proposer de recréer Catalog, Customer, Checkout, Sales
- ❌ Ignorer les erreurs de compilation

**Toujours** :
- ✅ Travailler module par module
- ✅ Attendre la validation avant chaque étape importante
- ✅ Vérifier que Magento ne fait pas déjà la fonctionnalité
- ✅ Documenter les décisions
- ✅ Produire un rapport après chaque sprint

**Validation par l'utilisateur** : le plan de travail (É2), le développement (É3), l'audit
technique (É5) et le rapport final (É6). **Aucune étape ne peut être franchie sans validation explicite.**

---

## ❌ Ce qu'il ne faut JAMAIS faire

> Chaque anti-pattern est listé avec : **pourquoi c'est mauvais** et **la bonne approche**.
> Un code qui tombe dans un de ces pièges est **refusé en revue**, quelle qu'en soit la raison.

### 1. Utiliser `ObjectManager` dans le code métier

```php
// ❌ JAMAIS
$repo = $this->_objectManager->create(EntityRepository::class);
```

**Pourquoi c'est mauvais** : contourne le conteneur DI, rend le code intestable,
masque les dépendances réelles, casse les plugins sur cette classe.
**Bonne approche** : injecter la dépendance dans le constructeur (`private readonly ...`).

### 2. Créer `InstallSchema.php` / `InstallData.php`

**Pourquoi c'est mauvais** : obsolète et non exécutable dans les cycles de mise à jour
(`setup:upgrade`). Ne gère pas les mises à jour incrémentales.
**Bonne approche** : `etc/db_schema.xml` pour le schéma + `Setup/Patch/` pour les données.

### 3. SQL direct dans les Controllers ou les Blocks

```php
// ❌ JAMAIS
$result = $connection->query('SELECT * FROM entity WHERE id = ' . $id);
```

**Pourquoi c'est mauvais** : fuite de logique métier dans la couche présentation,
vulnérabilité aux injections si mal concaténé, impossible à tester.
**Bonne approche** : Repository → ResourceModel → Collection.

### 4. Logique métier dans les Blocks / ViewModels

**Pourquoi c'est mauvais** : le Block doit uniquement **préparer des données pour le
template**. La logique métier doit être réutilisable et testable.
**Bonne approche** : Service / Repository pour la logique, Block pour l'affichage.

### 5. Helper « fourre-tout »

```php
// ❌ JAMAIS
class Data extends AbstractHelper { /* 200 méthodes disparates */ }
```

**Pourquoi c'est mauvais** : anti-SOLID (Single Responsibility violé), classe difficile à
lire, à tester, à remplacer.
**Bonne approche** : un service par responsabilité (`GetActiveLabels`, `PriceCalculator`...).

### 6. Préferences inutiles (override de classes Magento)

**Pourquoi c'est mauvais** : override global, conflits avec d'autres modules, mises à jour
core impossibles. Le dernier recours absolu.
**Bonne approche** (dans l'ordre) : **Plugin → Observer → Layout XML → Preference**.

### 7. Réécrire le Core Magento

**Pourquoi c'est mauvais** : Magento fournit Catalog, Customer, Sales, Checkout, Inventory,
CMS. Les réécrire = coût énorme + perte des mises à jour de sécurité.
**Bonne approche** : **toujours étendre avant de créer.** On ne crée un module que pour de la
**vraie valeur métier nouvelle**.

### 8. Copier-coller le code du Core

**Pourquoi c'est mauvais** : code maintenu par d'autres, incompatible avec vos versions,
impossible à mettre à jour.
**Bonne approche** : étendre par plugin/observer, ou réécrire **minimalement** et proprement
pour votre besoin.

### 9. Contourner les Service Contracts

```php
// ❌ JAMAIS
$model = $this->modelFactory->create()->load($id);
// ✅ TOUJOURS
$entity = $this->entityRepository->getById($id);
```

**Pourquoi c'est mauvais** : le Repository est la seule porte d'entrée officielle.
**Bonne approche** : passer par l'interface `Api/{Entity}RepositoryInterface` partout.

### 10. Le « commit aveugle » (sans validation)

**Pourquoi c'est mauvais** : un module non validé en local casse la chaîne pour tout le monde.
**Bonne approche** : exécuter la **checklist de validation** ci-dessous avant chaque commit.

---

## Checklist de validation d'un module

### Avant chaque commit

- [ ] `php -l` sur tous les fichiers PHP
- [ ] `phpcs` conforme PSR-12
- [ ] Pas de référence à un autre module AlpineCommerce (sauf autorisation)
- [ ] Service Contracts définis dans `Api/`
- [ ] `db_schema.xml` valide et cohérent avec les ResourceModels (pas de InstallSchema/InstallData)
- [ ] `module.xml` avec séquences correctes
- [ ] `di.xml` sans erreur
- [ ] `webapi.xml` avec authentification correcte
- [ ] `acl.xml` défini si controller admin
- [ ] Routes frontend et admin définies
- [ ] Traductions dans `i18n/`
- [ ] Pas de logique métier dans les Controllers
- [ ] Pas de `$objectManager->create()` dans le code métier
- [ ] Pas de SQL direct sans justification
- [ ] Pas de `preference` sur une classe core Magento
- [ ] Pas de réécriture de fonctionnalité existante du core (étendre > créer)
- [ ] UI Components au format 2.4.x (`<dataProvider class="...">` présent, pas de `primaryDataSource`)
- [ ] Pas de code mort (classes, méthodes, variables inutilisés)

### Avant chaque Sprint

- [ ] `setup:upgrade` passe sans erreur
- [ ] `setup:di:compile` passe sans erreur
- [ ] `module:status` affiche le module correctement
- [ ] `cache:clean` et `cache:flush` passent
- [ ] `indexer:reindex` passe
- [ ] Module testé en frontend et/ou backend
- [ ] Aucune erreur dans `var/log/system.log` et `var/log/exception.log`

---

## Glossaire

### A

**ACL (Access Control List)** — Système de permissions de Magento qui définit qui peut accéder à quelles ressources dans l'admin.

**Adobe Commerce** — Nom officiel de Magento 2 (Enterprise Edition). Dans ce projet, nous utilisons Magento 2 Open Source.

**API REST** — Interface de programmation qui permet d'interagir avec Magento via des requêtes HTTP. Définie dans `etc/webapi.xml`.

**Area** — Concept Magento qui délimite le contexte d'exécution : `frontend`, `adminhtml`, `crontab`, `webapi_rest`, `graphql`.

**Attribute** — Propriété d'un produit, client ou catégorie dans Magento. Peut être de type EAV (texte, date, décimal) ou Flat (varchar, int, text, decimal, datetime).

### B

**Block** — Classe PHP qui fournit des données à un template PHTML. Hérite de `\Magento\Framework\View\Element\Template`.

**Bundle Product** — Type de produit Magento composé d'options multiples, chaque option liée à un produit simple.

### C

**Cache** — Mécanisme de Magento pour stocker des données fréquemment utilisées. Types : `config`, `layout`, `block_html`, `collections`, `reflection`, `db_ddl`, `full_page` (Varnish), `translate`, `config_integration`, `config_integration_api`.

**Collection** — Classe qui représente une liste d'entités avec filtres, tris et pagination.

**Composer** — Gestionnaire de dépendances PHP utilisé par Magento.

**ComponentRegistrar** — Classe Magento qui enregistre les modules, thèmes et packages de langue.

**Controller** — Classe qui gère les requêtes HTTP et retourne une réponse. Dans Magento, les controllers étendent `\Magento\Framework\App\Action\Action`.

**Cron** — Tâches planifiées dans Magento. Configurées dans `etc/crontab.xml`.

**Customer** — Entité représentant un client dans Magento.

### D

**Data Patch** — Script PHP qui modifie la structure ou les données de la base de données. Utilisé pour les modifications post-installation.

**db_schema.xml** — Fichier XML déclaratif qui définit les tables, colonnes, index et contraintes de la base de données.

**Dependency Injection (DI)** — Pattern qui permet d'injecter les dépendances d'une classe via son constructeur plutôt que de les créer directement.

**di.xml** — Fichier de configuration du Dependency Injection Container de Magento.

**Directory** — Répertoire virtuel de Magento (ex : `app/code`, `app/design`, `vendor`).

### E

**EAV (Entity-Attribute-Value)** — Modèle de données de Magento pour les entités comme les produits et les clients. Permet d'ajouter des attributs dynamiquement.

**Event** — Mécanisme de Magento qui permet de réagir à des actions spécifiques (ex : `sales_order_save_after`).

**events.xml** — Fichier qui déclare les observers pour des événements Magento.

**Extension Attribute** — Mécanisme qui permet d'ajouter des attributs à une interface sans la modifier. Utilisé pour étendre les Service Contracts.

### F

**Factory** — Classe générée automatiquement par Magento pour créer des instances d'objets. Utilise le pattern Factory.

**Frontend** — Zone de l'application visible par les clients. Différent de `adminhtml`.

**FrontName** — Identifiant d'une route dans l'URL (ex : `loyalty` dans `/loyalty/customer/balance`).

### G

**GraphQL** — API query language pour Magento (non utilisée dans ce projet pour l'instant).

**Group** — Niveau de configuration dans Magento : `default` (global), `websites` (par site), `stores` (par boutique).

### H

**Helper** — Classe utilitaire qui fournit des méthodes réutilisables. Dans Magento, les Helpers étendent `\Magento\Framework\App\Helper\AbstractHelper`.

### I

**Indexer** — Processus Magento qui maintient les données à jour pour améliorer les performances des recherches et filtres.

**Interface** — Contrat qui définit les méthodes qu'une classe doit implémenter. Dans Magento, les interfaces sont dans le dossier `Api/`.

**Interceptor** — Classe générée par `setup:di:compile` qui implémente la logique des Plugins.

### K

**Knockout.js** — Framework JavaScript utilisé par Magento pour les composants UI (checkout, mini-cart). Dans ce projet, React remplace Knockout pour le frontend personnalisé.

### L

**Layout XML** — Fichier XML qui définit la structure d'une page Magento (blocks, containers, templates).

**Logger** — Classe qui écrit des messages dans les fichiers de log. Utilise PSR-3.

### M

**Magento 2** — Plateforme e-commerce open source sur laquelle repose AlpineCommerce.

**Menu** — Élément du menu admin défini dans `etc/adminhtml/menu.xml`.

**Module** — Unité fonctionnelle de Magento. Dans AlpineCommerce, chaque module est une fonctionnalité métier.

**module.xml** — Fichier qui déclare un module Magento avec son nom, sa version et ses dépendances.

**MSI (Multi Source Inventory)** — Système d'inventaire multi-sources de Magento qui permet de gérer des stocks dans plusieurs entrepôts.

**Multi Store** — Fonctionnalité de Magento qui permet de gérer plusieurs boutiques avec des configurations différentes.

### O

**Observer** — Classe qui réagit à un événement Magento. Déclarée dans `etc/events.xml`.

**OOP (Object-Oriented Programming)** — Paradigme de programmation utilisé par Magento : classes, interfaces, héritage, polymorphisme.

### P

**Patch** — Script qui modifie la base de données. Peut être de type `Data` (données) ou `Schema` (structure).

**Permission** — Droit d'accès à une ressource Magento, défini dans `etc/acl.xml`.

**Plugin (Interceptor)** — Pattern Magento qui permet de modifier le comportement d'une méthode sans la toucher. Défini dans `etc/di.xml`.

**Preference** — Liaison dans `di.xml` qui associe une interface à une implémentation concrète.

**Product** — Entité représentant un produit dans Magento.

**Proxy** — Classe générée par Magento pour le chargement paresseux (lazy loading) des dépendances.

**PSR-12** — Norme de codage PHP que respecte le projet.

**PHTML** — Extension des fichiers de template Magento (PHP HTML).

### Q

**Quote** — Entité représentant le panier d'un client avant la commande.

### R

**React** — Bibliothèque JavaScript utilisée pour le frontend personnalisé d'AlpineCommerce.

**Registration** — Fichier `registration.php` qui enregistre un module, un thème ou un package de langue auprès de Magento.

**Repository** — Classe qui fournit un accès aux données via des méthodes métier (getById, getList, save, delete). Implémente un Service Contract.

**Resource Model** — Classe qui effectue les opérations CRUD sur les tables de base de données.

**REST API** — Interface de programmation basée sur HTTP pour interagir avec Magento.

**routes.xml** — Fichier qui déclare les routes frontend ou admin d'un module.

### S

**Sales** — Module Magento qui gère les commandes, factures, avoirs et expéditions.

**Schema** — Structure de la base de données. Dans Magento, défini dans `etc/db_schema.xml`.

**Scope** — Portée de configuration dans Magento : `default` (global), `website` (site web), `store` (boutique).

**Search Criteria** — Objet Magento qui représente les critères de recherche (filtres, tris, pagination).

**Service Contract** — Interface qui définit les méthodes d'un service métier. Stockée dans `Api/`.

**Setup** — Répertoire qui contient les scripts d'installation et de mise à jour de la base de données.

**Shipping** — Module Magento qui gère les méthodes de livraison.

**Store** — Entité représentant une boutique dans Magento.

**Store View** — Niveau le plus bas de la hiérarchie Magento : Global > Site Web > Groupe de boutiques > Boutique > Vue de boutique.

### T

**Tailwind CSS** — Framework CSS utility-first utilisé pour le frontend personnalisé.

**Template** — Fichier PHTML qui contient le HTML d'une page ou d'un block.

**Total Collector** — Classe Magento qui calcule les totaux du panier (sous-total, taxes, frais de livraison, remises).

### U

**UI Component** — Composant d'interface utilisateur Magento pour les grilles et formulaires admin. Défini dans `view/adminhtml/ui_component/`.

**URL Rewrite** — Mécanisme de Magento qui permet de personnaliser les URLs pour le SEO.

### V

**Varnish** — Reverse proxy cache utilisé en production pour accélérer le chargement des pages.

**ViewModel** — Classe qui fournit des données et de la logique à un template. Alternative moderne aux Blocks.

**VirtualType** — Type virtuel dans `di.xml` qui permet de configurer une classe sans la déclarer explicitement.

### W

**webapi.xml** — Fichier qui déclare les routes REST API d'un module.

**Website** — Entité représentant un site web dans la hiérarchie Magento.

### X-Y-Z

**XML** — Langage de balisage utilisé pour les configurations Magento (layouts, di, webapi, etc.).

**YAML** — Format de fichier utilisé par Docker et certaines configurations Magento.

**Zone** — Concept Magento qui délimite le contexte d'exécution (frontend, adminhtml, crontab, etc.).

---

*Dernière mise à jour : 2026-08-06 (Phase A — gel des standards).*
