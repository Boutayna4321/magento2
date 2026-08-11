# Magento 2 — Admin Basics

> **Objectif** : comprendre le panneau d'administration Magento :
> navigation, ACL, configuration système, menus, et comment AlpineCommerce
> étend l'admin avec ses propres modules.

---

## 1. Accéder à l'admin

### 1.1 URL et identifiants

```
Admin URL : http://localhost:8080/admin
Login     : admin / admin123
```

Le chemin `/admin` est défini dans `app/etc/env.php` :
```php
'backend' => [
    'frontName' => 'admin'
]
```

### 1.2 Structure du panneau admin

```
Admin
├── Dashboard                    ← Vue d'ensemble (commandes, clients)
├── Sales                        ← Ventes
│   ├── Orders
│   ├── Invoices
│   ├── Shipments
│   └── Credit Memos
├── Catalog                      ← Catalogue
│   ├── Products
│   ├── Categories
│   └── Attributes
├── Customers                     ← Clients
│   ├── All Customers
│   ├── Customer Groups
│   └── Now Online
├── Marketing                     ← Marketing
│   ├── Promotions
│   ├── SEO & Search
│   └── Communications
├── Content                       ← Contenu
│   ├── Pages
│   ├── Blocks
│   └── Widgets
├── Stores                        ← Boutiques
│   ├── Settings > Configuration   ← Configuration globale
│   ├── All Stores                ← Gestion des stores
│   ├── Attributes                ← Attributs client/produit
│   └── Taxes
└── System                        ← Système
    ├── Tools > Cache Management
    ├── Tools > Index Management
    ├── Permissions > All Users
    └── Permissions > User Roles
```

---

## 2. Les concepts clés de l'admin

### 2.1 ACL (Access Control List)

L'**ACL** contrôle ce que chaque utilisateur admin peut faire.

**Structure d'un ACL** :

```xml
<!-- etc/acl.xml -->
<acl xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="AlpineCommerce_Blog::main" title="Blog" sortOrder="10">
                <resource id="AlpineCommerce_Blog::post" title="Posts" sortOrder="10"/>
                <resource id="AlpineCommerce_Blog::category" title="Categories" sortOrder="20"/>
            </resource>
        </resource>
    </resources>
</acl>
```

**Explication** :
- `AlpineCommerce_Blog::main` : ressource parente (apparaît dans le menu)
- `AlpineCommerce_Blog::post` : ressource enfant (permission pour les posts)
- L'utilisateur doit avoir la permission `AlpineCommerce_Blog::post` pour
  accéder aux posts

### 2.2 Menu admin

```xml
<!-- etc/adminhtml/menu.xml -->
<menu xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <add id="AlpineCommerce_Blog::main"
         title="Blog"
         module="AlpineCommerce_Blog"
         sortOrder="100"
         parent="Magento_Backend::content"
         resource="AlpineCommerce_Blog::main"/>
</menu>
```

**Attributs** :
- `id` : doit correspondre à l'ACL
- `title` : texte affiché dans le menu
- `parent` : où placer l'élément (`Magento_Backend::content` = menu principal)
- `resource` : ressource ACL requise
- `sortOrder` : position (plus petit = plus haut)

### 2.3 Protection des routes

Chaque Controller admin doit vérifier l'ACL :

```php
// Controller/Adminhtml/Post/Index.php
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';
    
    public function execute(): void
    {
        // Si l'utilisateur n'a pas la permission, Magento affiche 403 automatiquement
        // ...
    }
}
```

---

## 3. Stores > Configuration

### 3.1 Accéder à la configuration

```
Admin → Stores → Settings → Configuration
```

### 3.2 Les sections de configuration

La configuration est organisée en **sections** :

```
Stores > Configuration
├── General                     ← Paramètres généraux
│   ├── General
│   ├── Web
│   ├── Currency Setup
│   └── Store Email Addresses
├── Catalog                     ← Catalogue
│   ├── Catalog
│   ├── Price
│   └── Inventory
├── Customers                   ← Clients
│   ├── Customer Configuration
│   ├── Customer Groups
│   └── Login
├── Sales                       ← Ventes
│   ├── Checkout
│   ├── Shipping Settings
│   └── Tax
└── Advanced                     ← Avancé
    ├── Admin
    └── System
```

### 3.3 system.xml — Définir sa propre config

```xml
<!-- etc/adminhtml/system.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <system>
        <section id="alphacommerce_blog" translate="label" type="text" sortOrder="100" showInDefault="1" showInWebsite="1" showInStore="1">
            <label>Blog</label>
            <tab>general</tab>
            <resource>AlpineCommerce_Blog::config</resource>
            
            <group id="general" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>General Configuration</label>
                
                <field id="enabled" translate="label" type="select" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Enabled</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                    <default>1</default>
                </field>
                
                <field id="posts_per_page" translate="label" type="text" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Posts Per Page</label>
                    <default>10</default>
                    <validate>validate-digits</validate>
                </field>
            </group>
        </section>
    </system>
</config>
```

**Structure** :
- `<section>` : une section dans la config (`AlpineCommerce_Blog`)
- `<group>` : un groupe dans la section (`General Configuration`)
- `<field>` : un champ de configuration

### 3.4 Lire la configuration dans le code

```php
// Dans un Block, Helper, Model...
$isEnabled = $this->scopeConfig->isSetFlag('alphacommerce_blog/general/enabled');
$postsPerPage = $this->scopeConfig->getValue('alphacommerce_blog/general/posts_per_page');

// Avec le helper (recommandé)
$helper = \Magento\Framework\App\Config\ScopeConfigInterface::class;
$isEnabled = $helper->isSetFlag('alphacommerce_blog/general/enabled');
```

### 3.5 Les scopes de configuration

| Scope | Niveau | Exemple |
|-------|--------|---------|
| **Default** | Global | Tous les sites |
| **Website** | Par site | Site UK vs Site FR |
| **Store View** | Par langue | Anglais vs Français |

Dans `system.xml` :
- `showInDefault="1"` : visible au niveau global
- `showInWebsite="1"` : visible par site
- `showInStore="1"` : visible par store view

---

## 4. Les listings admin (UI Components)

### 4.1 Structure d'un listing admin

```
AlpineCommerce/Blog/
├── Controller/Adminhtml/Post/
│   ├── Index.php          ← Controller : affiche la grille
│   ├── Edit.php           ← Controller : affiche le formulaire
│   ├── Save.php           ← Controller : sauvegarde
│   └── Delete.php         ← Controller : suppression
├── Ui/
│   ├── DataProvider/
│   │   ├── PostListingDataProvider.php  ← Données de la grille
│   │   └── PostFormDataProvider.php     ← Données du formulaire
│   └── Component/Listing/Column/
│       └── Actions.php     ← Colonne d'actions (Edit/Delete)
├── view/adminhtml/
│   ├── layout/
│   │   ├── alphacommerce_blog_post_index.xml  ← Layout listing
│   │   └── alphacommerce_blog_post_edit.xml   ← Layout formulaire
│   └── ui_component/
│       ├── alphacommerce_blog_post_listing.xml ← Grille UI Component
│       └── alphacommerce_blog_post_form.xml     ← Formulaire UI Component
```

### 4.2 Exemple de listing UI Component

```xml
<!-- view/adminhtml/ui_component/alphacommerce_blog_post_listing.xml -->
<listing xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <dataSource name="post_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                AlpineCommerce\Blog\Ui\DataProvider\PostListingDataProvider
            </argument>
            <argument name="name" xsi:type="string">post_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">entity_id</argument>
            <argument name="requestFieldName" xsi:type="string">id</argument>
        </argument>
    </dataSource>
    
    <columns name="post_columns">
        <column name="title">
            <settings>
                <label translate="true">Title</label>
                <sortOrder>10</sortOrder>
            </settings>
        </column>
        <column name="status">
            <settings>
                <label translate="true">Status</label>
                <sortOrder>20</sortOrder>
                <filter>select</filter>
            </settings>
        </column>
        <actions>
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="urlPath" xsi:type="string">blog/post/edit</item>
                    <item name="paramName" xsi:type="string">id</item>
                </item>
            </argument>
        </actions>
    </columns>
</listing>
```

### 4.3 Le DataProvider

```php
// Ui/DataProvider/PostListingDataProvider.php
class PostListingDataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PostRepositoryInterface $postRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->postRepository = $postRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collection = $postRepository->getList($searchCriteriaBuilder->create())->getItems();
    }
    
    public function getData(): array
    {
        return [
            'items' => $this->collection,
            'totalRecords' => count($this->collection)
        ];
    }
}
```

---

## 5. Les formulaires admin (UI Components)

### 5.1 Structure d'un formulaire

```xml
<!-- view/adminhtml/ui_component/alphacommerce_blog_post_form.xml -->
<form xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <dataSource name="post_form_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                AlpineCommerce\Blog\Ui\DataProvider\PostFormDataProvider
            </argument>
            <argument name="name" xsi:type="string">post_form_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">entity_id</argument>
            <argument name="requestFieldName" xsi:type="string">id</argument>
        </argument>
    </dataSource>
    
    <fieldset name="general">
        <field name="title">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="label" xsi:type="string">Title</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">input</item>
                    <item name="sortOrder" xsi:type="number">10</item>
                </item>
            </argument>
        </field>
        <field name="content">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="label" xsi:type="string">Content</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">textarea</item>
                    <item name="sortOrder" xsi:type="number">20</item>
                </item>
            </argument>
        </field>
    </fieldset>
</form>
```

### 5.2 Les boutons du formulaire

Dans le layout `_edit.xml` :

```xml
<referenceContainer name="content">
    <block class="AlpineCommerce\Blog\Block\Adminhtml\Post\Edit\GenericButton" name="edit_form"/>
</referenceContainer>
```

Les boutons sont définis via `ButtonProviderInterface` :

```php
// Block/Adminhtml/Post/Edit/GenericButton.php
class GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'back' => [
                'label' => __('Back'),
                'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/')),
                'class' => 'back',
                'sort_order' => 10
            ],
            'delete' => [
                'label' => __('Delete'),
                'on_click' => 'deleteConfirm("Are you sure?")',
                'class' => 'delete',
                'sort_order' => 20
            ],
            'save' => [
                'label' => __('Save'),
                'on_click' => 'saveAndContinueEdit()',
                'class' => 'save primary',
                'sort_order' => 90
            ]
        ];
    }
}
```

---

## 6. Les modules AlpineCommerce dans l'admin

### 6.1 Modules avec interface admin

| Module | Menu | ACL | Listing | Formulaire |
|--------|------|-----|---------|------------|
| Blog | Content > Blog | `AlpineCommerce_Blog::post`, `::category` | ✅ | ✅ |
| Faq | Content > FAQ | `AlpineCommerce_Faq::main` | ✅ | ✅ |
| LegalPages | Content > Legal Pages | `AlpineCommerce_LegalPages::main` | ✅ | ✅ |
| ProductReviews | Marketing > Product Reviews | `AlpineCommerce_ProductReviews::main` | ✅ | ✅ |
| ProductQuestions | Marketing > Product Questions | `AlpineCommerce_ProductQuestions::main` | ✅ | ✅ |
| ProductLabels | Catalog > Product Labels | `AlpineCommerce_ProductLabels::main` | ✅ | ✅ |
| Gdpr | GDPR > Consent Log | `AlpineCommerce_Gdpr::consent_log`, `::export` | ✅ | ❌ |
| StorePickup | Content > Store Pickup | `AlpineCommerce_StorePickup::main` | ✅ | ✅ |
| StoreLocator | Content > Store Locator | `AlpineCommerce_StoreLocator::main` | ✅ | ✅ |
| CustomerCare | Customers > Customer Care | `AlpineCommerce_CustomerCare::manage` | ✅ | ✅ |
| CustomerGrid | (none — override natif) | (none — utilise ACL natif) | ✅ | ❌ |

### 6.2 Modules sans interface admin

| Module | Rôle | Admin |
|--------|------|-------|
| StoreSetup | Configuration + observers | System.xml seulement |
| LoyaltyProgram | Total collector + minicart | System.xml seulement |
| EuVat | Validation + CLI | System.xml seulement |
| Hreflang | Tags SEO | System.xml seulement |

---

## 7. Créer une nouvelle entrée admin

### 7.1 Étapes

1. **Créer l'ACL** (`etc/acl.xml`)
2. **Créer le menu** (`etc/adminhtml/menu.xml`)
3. **Créer les routes** (`etc/adminhtml/routes.xml`)
4. **Créer les Controllers** (`Controller/Adminhtml/...`)
5. **Créer les layouts** (`view/adminhtml/layout/...`)
6. **Créer les UI Components** (`view/adminhtml/ui_component/...`)
7. **Créer les DataProviders** (`Ui/DataProvider/...`)

### 7.2 Exemple : routes.xml

```xml
<!-- etc/adminhtml/routes.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <router id="admin">
        <route id="alphacommerce_blog" frontName="alphacommerce_blog">
            <module name="AlpineCommerce_Blog" before="Magento_Backend"/>
        </route>
    </router>
</config>
```

L'URL admin sera : `/admin/alphacommerce_blog/post/index`

### 7.3 Exemple : Controller

```php
// Controller/Adminhtml/Post/Index.php
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';
    
    public function execute(): \Magento\Backend\Model\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_Blog::post');
        $resultPage->getConfig()->getTitle()->prepend(__('Blog Posts'));
        return $resultPage;
    }
}
```

---

## 8. Résumé

| Concept | Rôle | Exemple AlpineCommerce |
|---------|------|------------------------|
| ACL | Contrôle les permissions | `etc/acl.xml` |
| Menu | Entrée dans le sidebar admin | `etc/adminhtml/menu.xml` |
| Routes | URLs admin | `etc/adminhtml/routes.xml` |
| Controller | Logique admin | `Controller/Adminhtml/Post/Index.php` |
| Layout admin | Structure de la page admin | `view/adminhtml/layout/` |
| UI Component | Grille admin | `view/adminhtml/ui_component/listing.xml` |
| DataProvider | Données de la grille/formulaire | `Ui/DataProvider/` |
| system.xml | Configuration dans Stores > Configuration | `etc/adminhtml/system.xml` |

---

*Last updated: 2026-08-11.*
