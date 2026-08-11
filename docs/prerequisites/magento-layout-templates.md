# Magento 2 — Templates (PHTML) & Layout XML

> **Objectif** : apprendre à lire et écrire les fichiers de template et de
> layout Magento. Ce sont les fichiers qui contrôlent **ce qui s'affiche**
> dans le navigateur et **où** ça s'affiche.

---

## 1. Le concept de Layout

### 1.1 Qu'est-ce que le Layout ?

Le **Layout** est le squelette d'une page Magento. Il répond à deux questions :
- **Quels blocs** (PHP) doivent être créés ?
- **Où** les placer dans la page ?

Le Layout est défini en **XML**. Chaque page a son propre fichier XML.

### 1.2 La structure d'une page Magento

```
Page HTML
├── <html>
├── <head>               ← titre, CSS, JS (géré par Magento automatiquement)
└── <body>
    ├── page.top         ← header, logo, menu
    │   └── header        ← block Magento
    ├── content           ← contenu principal (variable selon la page)
    │   └── blog.post.list ← block personnalisé (AlpineCommerce Blog)
    ├── sidebar.main      ← colonne gauche (filtres, catégories)
    ├── sidebar.additional ← colonne droite (widgets)
    └── page.bottom       ← footer
```

Les noms `page.top`, `content`, `sidebar.main` sont des **containers**
définis par Magento. Les modules ajoutent des **blocks** dans ces containers.

### 1.3 Containers vs Blocks

| Concept | Rôle | Exemple |
|---------|------|---------|
| **Container** | Emplacement vide (comme un panneau vide) | `content`, `page.top` |
| **Block** | Élément concret (PHP class + template) | `blog.post.list`, `store.info` |

---

## 2. Anatomie d'un fichier Layout XML

### 2.1 Exemple minimal

```xml
<!-- view/frontend/layout/blog_index_index.xml -->
<page xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\PostList"
                   name="blog.post.list"
                   template="AlpineCommerce_Blog::post/list.phtml"
                   before="-"/>
        </referenceContainer>
    </body>
</page>
```

### 2.2 Explication ligne par ligne

```xml
<page>                                          ← Racine : une page entière
    <body>                                       ← Corps de la page
        <referenceContainer name="content">      ← Cible : le container "content"
            <block                                ← Ajoute un nouveau block
                class="AlpineCommerce\Blog\Block\PostList"   ← Classe PHP
                name="blog.post.list"                             ← Identifiant unique
                template="AlpineCommerce_Blog::post/list.phtml"   ← Template .phtml
                before="-"/>                                      ← Position : avant tout
        </referenceContainer>
    </body>
</page>
```

### 2.3 Les attributs de `<block>`

| Attribut | Obligatoire | Rôle | Exemple |
|----------|-------------|------|---------|
| `class` | Oui | Classe PHP qui fournit les données | `AlpineCommerce\Blog\Block\PostList` |
| `name` | Oui | Identifiant unique dans la page | `blog.post.list` |
| `template` | Non | Chemin vers le fichier `.phtml` | `AlpineCommerce_Blog::post/list.phtml` |
| `before` | Non | Positionner AVANT un autre block | `before="-"` (premier) |
| `after` | Non | Positionner APRÈS un autre block | `after="page.bottom"` |
| `ifConfig` | Non | Afficher si une config est activée | `ifConfig="blog/general/enabled"` |
| `if` | Non | Afficher selon une expression | `if="1 == 1"` |

---

## 3. Les fichiers Layout dans Magento

### 3.1 Où se trouvent les fichiers layout ?

```
Module/
├── view/
│   ├── frontend/
│   │   └── layout/                    ← Layouts frontend
│   │       ├── default.xml            ← Appliqué à TOUTES les pages frontend
│   │       ├── catalog_product_view.xml ← Page produit
│   │       ├── catalog_category_view.xml ← Page catégorie
│   │       └── blog_index_index.xml   ← Page /blog (route: blog/index/index)
│   └── adminhtml/
│       └── layout/                    ← Layouts admin
│           ├── adminhtml_dashboard_index.xml ← Dashboard admin
│           └── alphacommerce_blog_post_index.xml ← Listing admin Blog
```

### 3.2 Comment Magento trouve le bon fichier layout

Magento construit le nom du fichier à partir de l'URL :

| URL | Route | Fichier layout |
|-----|-------|----------------|
| `/blog` | `blog/index/index` | `blog_index_index.xml` |
| `/blog/post/view/id/1` | `blog/post/view` | `blog_post_view.xml` |
| `/catalog/product/view/id/1` | `catalog/product/view` | `catalog_product_view.xml` |
| `/admin/blog/post/index` | `adminhtml/blog/post/index` | `adminhtml_blog_post_index.xml` |

**Règle** : `{frontName}_{controller}_{action}.xml`

### 3.3 La cascade de layout (fallback)

Magento applique plusieurs fichiers layout dans un ordre précis :

```
1. default.xml                  (toutes les pages)
2. {module}_default.xml        (ex: blog_default.xml)
3. {area}_default.xml          (ex: frontend_default.xml)
4. {full_action_name}.xml      (ex: blog_index_index.xml)
```

Les fichiers sont **fusionnés** : ce qui est déclaré dans `blog_index_index.xml`
s'ajoute à ce qui est dans `default.xml`.

---

## 4. Les instructions XML essentielles

### 4.1 `<referenceContainer>` et `<referenceBlock>`

Pour ajouter du contenu dans un container ou un block existant :

```xml
<!-- Ajouter un block dans le container "content" -->
<referenceContainer name="content">
    <block class="AlpineCommerce\Blog\Block\PostList"
           name="blog.post.list"
           template="AlpineCommerce_Blog::post/list.phtml"/>
</referenceContainer>

<!-- Ajouter un block APRÈS le block "page.main.title" -->
<referenceBlock name="page.main.title">
    <block class="AlpineCommerce\Blog\Block\Breadcrumbs"
           name="blog.breadcrumbs"
           template="AlpineCommerce_Blog::breadcrumbs.phtml"
           after="-"/>
</referenceBlock>
```

**Différence** :
- `<referenceContainer>` : pour les conteneurs (`content`, `page.top`, etc.)
- `<referenceBlock>` : pour les blocks existants (`page.main.title`, `product.info.main`, etc.)

### 4.2 `<block>` autonome

Pour créer un block sans référence (page entièrement custom) :

```xml
<page>
    <body>
        <block class="Magento\Framework\View\Element\Template"
               name="my.custom.page"
               template="AlpineCommerce_Blog::custom/page.phtml"/>
    </body>
</page>
```

### 4.3 `<container>`

Pour créer un nouveau container (rare, réservé aux cas avancés) :

```xml
<referenceContainer name="content">
    <container name="blog.container" label="Blog Container" htmlTag="div" htmlClass="blog-container">
        <block class="AlpineCommerce\Blog\Block\PostList"
               name="blog.post.list"
               template="AlpineCommerce_Blog::post/list.phtml"/>
    </container>
</referenceContainer>
```

### 4.4 `<move>`

Pour déplacer un block existant :

```xml
<!-- Déplacer le block "product.info.main" dans "sidebar.main" -->
<move element="product.info.main" destination="sidebar.main" before="-"/>
```

### 4.5 `<remove>`

Pour supprimer un block :

```xml
<!-- Supprimer le block "breadcrumbs" de cette page -->
<referenceBlock name="breadcrumbs" remove="true"/>
```

---

## 5. Les arguments de block

### 5.1 Arguments simples

```xml
<block class="AlpineCommerce\Blog\Block\PostList"
       name="blog.post.list">
    <arguments>
        <argument name="page_size" xsi:type="number">10</argument>
        <argument name="show_date" xsi:type="boolean">true</argument>
        <argument name="title" xsi:type="string">Latest Posts</argument>
    </arguments>
</block>
```

Dans le Block PHP :
```php
public function getPageSize(): int
{
    return (int) $this->getData('page_size'); // 10
}
```

### 5.2 Arguments complexes

```xml
<arguments>
    <argument name="js_config" xsi:type="array">
        <item name="component" xsi:type="string">alphacommerceStorePickup</item>
    </argument>
    <argument name="data" xsi:type="array">
        <item name="availableStores" xsi:type="object">
            AlpineCommerce\StorePickup\Block\Adminhtml\Store\Source\StoreInfo
        </item>
    </argument>
</arguments>
```

### 5.3 L'argument `data` (modèle de données)

Quand un block reçoit un argument `data`, Magento le fusionne dans le
modèle du block. Les clés deviennent accessibles via `$block->getData()` :

```xml
<block class="..." name="...">
    <arguments>
        <argument name="data" xsi:type="array">
            <item name="available_stores" xsi:type="object">StoreInfo</item>
            <item name="carrier_code" xsi:type="string">storepickup</item>
        </argument>
    </arguments>
</block>
```

```php
// Dans le template .phtml :
$availableStores = $block->getAvailableStores(); // objet StoreInfo
$carrierCode = $block->getCarrierCode(); // 'storepickup'
```

---

## 6. Les fichiers PHTML (templates)

### 6.1 Qu'est-ce qu'un PHTML ?

Un fichier **PHTML** = **P**HP + **H**TML. C'est le fichier qui génère
le HTML final. Il contient :
- Du HTML
- Du PHP pour afficher des variables
- Des appels au Block (`$block->getSomething()`)
- Des boucles et conditions PHP

### 6.2 Chemin d'un template

```xml
template="AlpineCommerce_Blog::post/list.phtml"
```

Se décompose en :
```
AlpineCommerce_Blog  ← Module (Vendor_Module)
::                   ← Séparateur
post/list.phtml      ← Chemin dans view/frontend/templates/
```

**Chemin complet sur le disque** :
```
src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml
```

### 6.3 Exemple de PHTML

```php
<?php /** @var $block AlpineCommerce\Blog\Block\PostList */ ?>
<?php /** @var $posts AlpineCommerce\Blog\Model\Post[] */ ?>

<div class="blog-post-list">
    <?php if ($posts = $block->getPosts()): ?>
        <?php foreach ($posts as $post): ?>
            <article class="blog-post-item">
                <h2>
                    <a href="<?= $block->escapeUrl($post->getUrl()) ?>">
                        <?= $block->escapeHtml($post->getTitle()) ?>
                    </a>
                </h2>
                
                <div class="post-content">
                    <?= $block->escapeHtml($post->getContent()) ?>
                </div>
                
                <div class="post-meta">
                    <span><?= $block->formatDate($post->getCreatedAt()) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-posts"><?= $block->escapeHtml(__('No posts found.')) ?></p>
    <?php endif; ?>
</div>
```

### 6.4 Les méthodes essentielles dans un PHTML

| Méthode | Rôle | Exemple |
|---------|------|---------|
| `$block->getData('key')` | Lire un argument du layout | `$block->getData('page_size')` |
| `$block->getUrl('route/path')` | Générer une URL | `$block->getUrl('blog/index/view')` |
| `$block->escapeHtml($str)` | Échapper le HTML (sécurité) | `$block->escapeHtml($title)` |
| `$block->escapeUrl($url)` | Échapper une URL | `$block->escapeUrl($post->getUrl())` |
| `$block->formatDate($date)` | Formater une date | `$block->formatDate($post->getCreatedAt())` |
| `$block->formatPrice($amount)` | Formater un prix | `$block->formatPrice(29.99)` |
| `__('string')` | Traduire | `__('No posts found')` |

### 6.5 Raccourcis dans les PHTML

```php
<?= /* équivalent à <?php echo */ ?>
<?php /* ... */ ?>       ← Commentaire PHP
<?= $block->... ?>       ← Appel au block
```

### 6.6 Sécurité : toujours échapper

```php
<!-- ❌ DANGEREUX : XSS possible -->
<p><?= $post->getTitle() ?></p>

<!-- ✅ SÛR : échappé -->
<p><?= $block->escapeHtml($post->getTitle()) ?></p>
```

**Règle d'or** : tout ce qui vient de la base de données ou de l'utilisateur
doit être échappé avant d'être affiché.

---

## 7. Le fallback system de templates

### 7.1 Ordre de recherche

Quand Magento cherche un template `AlpineCommerce_Blog::post/list.phtml` :

```
1. Thème actif :
   src/app/design/frontend/AlpineCommerce/theme/AlpineCommerce/Blog/templates/post/list.phtml

2. Module parent :
   src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml

3. Module Magento (fallback) :
   src/app/code/Magento/Theme/view/frontend/templates/html/header.phtml
```

### 7.2 Override un template dans le thème

Pour modifier un template **sans toucher au module**, copie-le dans le thème :

```bash
# Original (module)
cp src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml \
   src/app/design/frontend/AlpineCommerce/theme/AlpineCommerce/Blog/templates/post/list.phtml

# Puis modifier la copie dans le thème
```

Magento utilisera automatiquement la version du thème.

---

## 8. Exemples concrets AlpineCommerce

### 8.1 Layout Blog + Template

**Layout** (`view/frontend/layout/blog_index_index.xml`) :
```xml
<page xmlns:xsi="..." layout="1column">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\PostList"
                   name="blog.post.list"
                   template="AlpineCommerce_Blog::post/list.phtml"/>
        </referenceContainer>
    </body>
</page>
```

**Block PHP** (`Block/PostList.php`) :
```php
class PostList extends Template
{
    private PostRepositoryInterface $postRepository;
    
    public function getPosts(): array
    {
        return $this->postRepository->getList($searchCriteria)->getItems();
    }
}
```

**Template** (`templates/post/list.phtml`) :
```php
<?php /** @var $block AlpineCommerce\Blog\Block\PostList */ ?>
<?php $posts = $block->getPosts(); ?>

<div class="blog-posts">
    <?php foreach ($posts as $post): ?>
        <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
        <p><?= $block->escapeHtml($post->getContent()) ?></p>
    <?php endforeach; ?>
</div>
```

### 8.2 Layout Admin + Template

**Layout** (`view/adminhtml/layout/alphacommerce_blog_post_index.xml`) :
```xml
<page xmlns:xsi="...">
    <body>
        <referenceContainer name="content">
            <uiComponent name="alphacommerce_blog_post_listing"/>
        </referenceContainer>
    </body>
</page>
```

Ici, pas de `.phtml` classique : c'est un **UI Component** (grille admin)
défini en XML (`ui_component/alphacommerce_blog_post_listing.xml`).

### 8.3 Layout avec arguments

**Layout** (`view/frontend/layout/checkout_index_index.xml`) :
```xml
<referenceContainer name="checkout.cart.totals">
    <block class="Magento\Checkout\Block\Cart\Totals"
           name="store.pickup"
           template="AlpineCommerce_StorePickup::store-pickup.phtml">
        <arguments>
            <argument name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">alphacommerceStorePickup</item>
            </argument>
        </arguments>
    </block>
</referenceContainer>
```

---

## 9. Les instructions XML avancées

### 9.1 `<update>` — inclure un autre layout

```xml
<!-- Dans catalog_product_view.xml, inclure tout le layout default.xml -->
<update handle="default"/>
```

### 9.2 `<reference name="head">` — ajouter du CSS/JS

```xml
<page>
    <head>
        <css src="AlpineCommerce_Blog::css/blog.css"/>
        <js src="AlpineCommerce_Blog::js/blog.js"/>
        <link src="https://fonts.googleapis.com/css?family=Roboto" src_type="url"/>
    </head>
</page>
```

### 9.3 `<block>` avec `t:type`

Pour utiliser un VirtualType (défini dans `di.xml`) :

```xml
<block class="Magento\Framework\View\Element\Template"
       name="my.block"
       template="...::template.phtml">
    <arguments>
        <argument name="data" xsi:type="object">myVirtualType</argument>
    </arguments>
</block>
```

---

## 10. Tableau de correspondance

| Élément Layout | Élément PHP | Élément HTML |
|----------------|-------------|--------------|
| `<page>` | — | `<html>`, `<head>`, `<body>` |
| `<referenceContainer name="content">` | Container `content` | `<div class="columns">` |
| `<block class="...">` | Classe Block PHP | `<div>` généré par le template |
| `template="Vendor::path/template.phtml"` | Block PHP | Contenu HTML du block |
| `<arguments>` | `$block->getData()` | Variables dans le template |

---

## 11. Erreurs courantes

### 11.1 "Template file not found"

**Cause** : chemin du template incorrect.

**Vérifier** :
```xml
template="AlpineCommerce_Blog::post/list.phtml"
```

Doit correspondre à :
```
src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml
```

### 11.2 Block qui ne s'affiche pas

**Causes possibles** :
- Le `name` du block est en double (conflit)
- Le layout XML n'est pas chargé (mauvais nom de fichier)
- `before`/`after` place le block en dehors de la zone visible
- Le block est supprimé par un autre layout (`remove="true"`)

### 11.3 Variable vide dans le template

**Cause** : l'argument n'est pas passé correctement.

**Vérifier** :
```xml
<!-- Layout -->
<argument name="my_var" xsi:type="string">value</argument>

<!-- Block PHP -->
public function getMyVar(): string
{
    return $this->getData('my_var'); // 'value'
}

<!-- Template -->
<?= $block->escapeHtml($block->getMyVar()) ?>
```

---

## 12. Résumé

| Question | Réponse |
|----------|---------|
| **C'est quoi un layout XML ?** | Le squelette de la page : quels blocks afficher et où |
| **Où les mettre ?** | `view/frontend/layout/` ou `view/adminhtml/layout/` |
| **Comment nommer le fichier ?** | `{frontName}_{controller}_{action}.xml` |
| **C'est quoi un container ?** | Un emplacement vide (`content`, `page.top`) |
| **C'est quoi un block ?** | Un élément concret (PHP class + template) |
| **Comment ajouter un block ?** | `<referenceContainer name="content"><block .../></referenceContainer>` |
| **C'est quoi un PHTML ?** | Un fichier PHP qui génère du HTML |
| **Où trouver les templates ?** | `view/frontend/templates/` ou `view/adminhtml/templates/` |
| **Comment appeler un block depuis le template ?** | `$block->getPosts()`, `$block->getUrl()`, etc. |
| **Comment sécuriser l'affichage ?** | `$block->escapeHtml()`, `$block->escapeUrl()` |

---

*Last updated: 2026-08-11.*
