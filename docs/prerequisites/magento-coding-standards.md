# Magento 2 — Coding Standards

> **Objectif** : connaître les règles de codage obligatoires dans Magento 2
> et dans le projet AlpineCommerce. Ces standards garantissent que le code
> est lisible, maintenable et compatible avec les outils Magento.

---

## 1. Pourquoi des standards ?

| Sans standards | Avec standards |
|----------------|----------------|
| Chaque développeur écrit différemment | Tout le monde écrit de la même façon |
| Difficile de lire le code des autres | Code homogène, lisible |
| Outils de validation échouent | Outils passent du premier coup |
| Code non portable | Code portable entre projets |

---

## 2. PHP — PSR-12 + Magento

### 2.1 PSR-12 : la base

Magento 2 respecte **PSR-12** (norme PHP-FIG). Règles essentielles :

**Indentation** : 4 espaces (pas de tabulations)
```php
// ✅ Correct
class Post
{
    public function getTitle(): string
    {
        return $this->title;
    }
}

// ❌ Faux
class Post {
public function getTitle(): string {
return $this->title;
}}
```

**Accolades** : K&R style ( accolade ouvrante sur la même ligne )
```php
// ✅ Correct
if ($condition) {
    // ...
}

// ❌ Faux
if ($condition)
{
    // ...
}
```

**Espaces** :
```php
// ✅ Correct
$sum = $a + $b;
$array = ['key' => 'value'];

// ❌ Faux
$sum=$a+$b;
$array=['key'=>'value'];
```

### 2.2 `declare(strict_types=1)` — OBLIGATOIRE

Tout fichier PHP Magento doit commencer par :

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model;

use Magento\Framework\Model\AbstractModel;

class Post extends AbstractModel
{
    // ...
}
```

**Effet** :
- Les arguments de fonction sont typés strictement (pas de cast automatique)
- `int $id` refuse un string `'123'` → `TypeError`
- Rend le code plus fiable

### 2.3 Namespaces et PSR-4

Le namespace correspond à l'arborescence des dossiers :

```
Fichier : src/app/code/AlpineCommerce/Blog/Model/Post.php
Namespace : AlpineCommerce\Blog\Model
```

### 2.4 Classes : conventions de nommage

| Type | Convention | Exemple |
|------|-----------|---------|
| **Entity Model** | `Model/{Entity}.php` | `Model/Post.php` |
| **ResourceModel** | `Model/ResourceModel/{Entity}.php` | `Model/ResourceModel/Post.php` |
| **Collection** | `Model/ResourceModel/{Entity}/Collection.php` | `Model/ResourceModel/Post/Collection.php` |
| **Repository** | `Model/{Entity}Repository.php` | `Model/PostRepository.php` |
| **Interface** | `Api/Data/{Entity}Interface.php` | `Api/Data/PostInterface.php` |
| **Repository Interface** | `Api/{Entity}RepositoryInterface.php` | `Api/PostRepositoryInterface.php` |
| **Block** | `Block/{Name}.php` | `Block/PostList.php` |
| **Controller** | `Controller/{Area}/{Controller}/{Action}.php` | `Controller/Index/Index.php` |
| **Plugin** | `Plugin/{Entity}/{Method}.php` | `Plugin/Post/Slugify.php` |
| **Observer** | `Observer/{Event}.php` | `Observer/SavePostAfter.php` |
| **Helper** | `Helper/Data.php` | `Helper/Data.php` |
| **Data Patch** | `Setup/Patch/Data/{Name}.php` | `Setup/Patch/Data/CreateDefaultCategory.php` |

### 2.5 Visibilité des propriétés

```php
// ✅ Correct : propriétés privées + getters/setters publics
class Post
{
    private int $id;
    private string $title;
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
}

// ❌ Faux : propriétés publiques
class Post
{
    public $id;
    public $title;
}
```

### 2.6 Injection de dépendances (constructeur)

```php
// ✅ Correct : toutes les dépendances en paramètres du constructeur
class PostRepository
{
    private PostRepositoryInterface $repository;
    private ResourceModel\Post $resource;
    
    public function __construct(
        PostRepositoryInterface $repository,
        ResourceModel\Post $resource
    ) {
        $this->repository = $repository;
        $this->resource = $resource;
    }
}

// ❌ Faux : new explicite (anti-pattern)
class PostRepository
{
    public function __construct()
    {
        $this->resource = new ResourceModel\Post();
    }
}
```

### 2.7 Commentaires de documentation (PHPDoc)

```php
/**
 * Save a post.
 *
 * @param PostInterface $post
 * @return PostInterface
 * @throws CouldNotSaveException
 */
public function save(PostInterface $post): PostInterface
{
    // ...
}
```

**Tags obligatoires** :
- `@param` pour chaque paramètre
- `@return` pour le retour
- `@throws` pour les exceptions levées

---

## 3. XML — Conventions Magento

### 3.1 Indentation

```xml
<!-- ✅ Correct : 4 espaces -->
<?xml version="1.0"?>
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <module name="AlpineCommerce_Blog" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Catalog"/>
        </sequence>
    </module>
</config>

<!-- ❌ Faux : tabulations ou 2 espaces -->
<config>
    <module>
```

### 3.2 Attributs XML

```xml
<!-- ✅ Correct : ordre des attributs, guillemets doubles -->
<block class="AlpineCommerce\Blog\Block\PostList"
       name="blog.post.list"
       template="AlpineCommerce_Blog::post/list.phtml"
       before="-"/>

<!-- ❌ Faux : guillemets simples, ordre aléatoire -->
<block template="AlpineCommerce_Blog::post/list.phtml" 
       name="blog.post.list" 
       class="AlpineCommerce\Blog\Block\PostList"/>
```

### 3.3 Strings et traductions

```xml
<!-- ✅ Correct : translate="true" pour les labels affichés -->
<item name="label" xsi:type="string" translate="true">Title</item>

<!-- ❌ Faux : pas de translate pour un label affiché -->
<item name="label" xsi:type="string">Title</item>
```

---

## 4. JavaScript — Conventions Magento

### 4.1 Structure de fichier

```js
// ✅ Correct
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';
    
    return {
        init: function () {
            // ...
        }
    };
});
```

### 4.2 'use strict' obligatoire

```js
// ✅ Correct
define(['jquery'], function ($) {
    'use strict';
    
    // code
});

// ❌ Faux
define(['jquery'], function ($) {
    // code sans 'use strict'
});
```

### 4.3 Nommage des variables

```js
// ✅ Correct : camelCase
var selectedStore = ko.observable('');
var isSaving = ko.observable(false);
var syncMessage = ko.observable('');

// ❌ Faux : snake_case ou PascalCase
var selected_store = ko.observable('');
var SelectedStore = ko.observable('');
```

---

## 5. PHTML — Conventions

### 5.1 Structure minimale

```php
<?php /** @var $block AlpineCommerce\Blog\Block\PostList */ ?>
<?php /** @var $posts AlpineCommerce\Blog\Model\Post[] */ ?>

<div class="blog-post-list">
    <?php foreach ($posts as $post): ?>
        <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
    <?php endforeach; ?>
</div>
```

### 5.2 Sécurité : toujours échapper

```php
<!-- ✅ Correct -->
<?= $block->escapeHtml($title) ?>
<?= $block->escapeUrl($url) ?>
<?= $block->escapeJs($js) ?>

<!-- ❌ DANGEREUX : XSS possible -->
<?= $title ?>
<?= $url ?>
```

### 5.3 Pas de logique complexe dans les PHTML

```php
<!-- ✅ Correct : logique dans le Block, template simple -->
<?= $block->getPosts() ?>
<?php foreach ($block->getPosts() as $post): ?>
    <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
<?php endforeach; ?>

<!-- ❌ Faux : logique métier dans le template -->
<?php
$posts = [];
$collection = $objectManager->create(\AlpineCommerce\Blog\Model\ResourceModel\Post\Collection::class);
foreach ($collection as $post) {
    if ($post->getIsActive()) {
        $posts[] = $post;
    }
}
?>
```

---

## 6. Module — Structure canonique

### 6.1 Arborescence obligatoire

```
AlpineCommerce/Blog/
├── registration.php          ← OBLIGATOIRE
├── etc/
│   ├── module.xml            ← OBLIGATOIRE (nom, version, sequence)
│   ├── db_schema.xml         ← OBLIGATOIRE si tables
│   ├── acl.xml               ← OBLIGATOIRE si admin
│   ├── adminhtml/
│   │   ├── routes.xml        ← OBLIGATOIRE si admin
│   │   ├── menu.xml          ← OBLIGATOIRE si admin
│   │   └── system.xml        ← OBLIGATOIRE si config
│   ├── frontend/
│   │   ├── routes.xml        ← OBLIGATOIRE si frontend
│   │   └── di.xml            ← Optionnel
│   ├── webapi.xml            ← OBLIGATOIRE si REST API
│   ├── events.xml            ← Optionnel (observers)
│   ├── di.xml                ← Optionnel (plugins, preferences)
│   └── crontab.xml           ← Optionnel (cron)
├── Api/
│   ├── Data/
│   │   └── PostInterface.php ← OBLIGATOIRE si entité
│   └── PostRepositoryInterface.php ← OBLIGATOIRE si repository
├── Model/
│   ├── Post.php              ← Entity Model
│   ├── PostInterface.php     ← Interface
│   ├── PostRepository.php    ← Repository
│   ├── ResourceModel/
│   │   ├── Post.php          ← ResourceModel
│   │   └── Post/
│   │       └── Collection.php ← Collection
│   └── ...
├── Block/                    ← Blocks frontend + admin
├── Controller/
│   ├── Frontend/             ← Controllers frontend
│   └── Adminhtml/            ← Controllers admin
├── Ui/
│   ├── DataProvider/         ← DataProviders admin
│   └── Component/            ← Colonnes UI Component
├── view/
│   ├── frontend/
│   │   ├── layout/           ← Layouts frontend
│   │   ├── templates/        ← Templates .phtml
│   │   └── web/              ← CSS, JS, images
│   └── adminhtml/
│       ├── layout/           ← Layouts admin
│       ├── ui_component/     ← UI Components XML
│       └── web/              ← CSS, JS admin
├── Setup/
│   └── Patch/
│       ├── Data/             ← Data Patches
│       └── Schema/           ← Schema Patches
└── i18n/                     ← Traductions CSV
```

### 6.2 Noms de fichiers

| Type | Convention | Exemple |
|------|-----------|---------|
| Classes | PascalCase + `.php` | `PostRepository.php` |
| Layouts | `{frontName}_{controller}_{action}.xml` | `blog_index_index.xml` |
| UI Components | `{module}_{entity}_{type}.xml` | `alphacommerce_blog_post_listing.xml` |
| Templates | `{entity}/{action}.phtml` | `post/list.phtml` |
| JS | `{type}/{name}.js` | `view/store-pickup.js` |
| CSS/Less | `_module.less` | `_module.less` |
| i18n | `{locale}.csv` | `fr_FR.csv` |

---

## 7. Git — Conventions de commits

### 7.1 Format des messages

```
type(scope): description

[optionnel: body]

[optionnel: footer]
```

**Types** :

| Type | Usage | Exemple |
|------|-------|---------|
| `feat` | Nouvelle fonctionnalité | `feat(blog): add category management` |
| `fix` | Correction de bug | `fix(blog): prevent XSS in post title` |
| `docs` | Documentation | `docs: add StoreSetup module doc` |
| `style` | Formatage (pas de changement de logique) | `style: fix indentation in PostRepository` |
| `refactor` | Refactoring (pas de bug fix, pas de feature) | `refactor(blog): extract slugify to plugin` |
| `test` | Ajout/modification de tests | `test(blog): add unit test for PostRepository` |
| `chore` | Maintenance | `chore: update composer dependencies` |
| `ci` | CI/CD | `ci: add PHP lint to GitHub Actions` |

### 7.2 Exemples AlpineCommerce

```
feat(blog): add REST API endpoints for posts
fix(storepickup): correct carrier plugin TypeError
docs: add StoreSetup module documentation
refactor(loyalty): move discount logic to total collector
test(faq): add integration test for REST API
ci: add markdown lint to GitHub Actions
```

---

## 8. Outils de validation

### 8.1 PHP Lint

```bash
# Vérifier la syntaxe d'un fichier
php -l src/app/code/AlpineCommerce/Blog/Model/PostRepository.php

# Vérifier tous les fichiers PHP d'un module
find src/app/code/AlpineCommerce/Blog -name '*.php' -print0 | xargs -0 -n1 php -l
```

### 8.2 XML Lint

```bash
# Valider un fichier XML
xmllint --noout src/app/code/AlpineCommerce/Blog/etc/module.xml

# Valider tous les XML d'un module
find src/app/code/AlpineCommerce/Blog -name '*.xml' -print0 | xargs -0 -n1 xmllint --noout
```

### 8.3 PHPStan (analyse statique)

```bash
# Installer PHPStan
composer require --dev phpstan/phpstan

# Lancer l'analyse
vendor/bin/phpstan analyse src/app/code/AlpineCommerce/Blog --level=5
```

### 8.4 PHP_CodeSniffer (PSR-12)

```bash
# Installer
composer require --dev magento/magento-coding-standard

# Vérifier le code
vendor/bin/phpcs --standard=PSR12 src/app/code/AlpineCommerce/Blog/

# Corriger automatiquement
vendor/bin/phpcbf --standard=PSR12 src/app/code/AlpineCommerce/Blog/
```

---

## 9. Checklist avant commit

| Vérification | Commande |
|--------------|----------|
| Syntaxe PHP | `find src/app/code/AlpineCommerce -name '*.php' -print0 \| xargs -0 -n1 php -l` |
| XML valide | `find src/app/code/AlpineCommerce -name '*.xml' -print0 \| xargs -0 -n1 xmllint --noout` |
| PSR-12 | `vendor/bin/phpcs --standard=PSR12 src/app/code/AlpineCommerce/Module/` |
| Module activé | `php bin/magento module:status` |
| DB à jour | `php bin/magento setup:upgrade` (si db_schema.xml modifié) |
| Cache vidé | `php bin/magento cache:flush` |
| Message de commit | Format `type(scope): description` |

---

## 10. Résumé

| Règle | Pourquoi |
|-------|----------|
| `declare(strict_types=1)` | Typage strict, pas de cast implicite |
| 4 espaces, pas de tabs | Cohérence avec PSR-12 |
| `private` + getters/setters | Encapsulation |
| Injection DI dans le constructeur | Testabilité, flexibilité |
| `escapeHtml()` dans les PHTML | Sécurité XSS |
| `translate="true"` dans XML | Internationalisation |
| PHPDoc complet | Documentation, IDE support |
| Message de commit formaté | Historique lisible |

---

*Last updated: 2026-08-11.*
