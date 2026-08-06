# Guide de développement AlpineCommerce

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

### Preference

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

**Quand utiliser** :
- Logique d'affichage complexe
- Préparation de données pour un template
- Éviter la logique métier dans les Blocks

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

### Bonnes pratiques

- Toutes les routes exposent des Service Contracts
- Validation des paramètres dans le Service
- Retour de objets Data Interface (pas de arrays)
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

### Structure

```xml
<!-- view/adminhtml/ui_component/entity_listing.xml -->
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">entity_listing.entity_listing_data_source</item>
        </item>
    </argument>
    <dataSource name="entity_listing_data_source">
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">Magento_Ui/js/grid/provider</item>
            </item>
        </argument>
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
