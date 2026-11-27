# Magento 2 from Scratch — PHTML Templates

> **Series**: [Learning Path](../README.md) → Phase 2 (HTML), Article 2 of 2
> **Previous**: [`01-layout-xml.md`](01-layout-xml.md) — Layout XML
> **Next**: [`../css/01-themes-and-less.md`](../css/01-themes-and-less.md) — Themes & LESS
> **Deep dive**: [`../magento-layout-templates.md`](../magento-layout-templates.md)

---

Layout XML declares **which Block** renders in **which container**. The
**template** (a `.phtml` file) is what actually generates the HTML.

A PHTML file is **PHP + HTML** — the last stop before the browser.

---

## 1. What a PHTML looks like

```php
<?php /** @var $block \AlpineCommerce\Blog\Block\Listing */ ?>
<?php $posts = $block->getPosts(); ?>

<div class="blog-post-list">
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <article class="blog-post-item">
                <h2>
                    <a href="<?= $block->escapeUrl($post->getUrl()) ?>">
                        <?= $block->escapeHtml($post->getTitle()) ?>
                    </a>
                </h2>
                <time datetime="<?= $block->escapeHtml($post->getCreatedAt()) ?>">
                    <?= $block->escapeHtml($block->formatDate($post->getCreatedAt())) ?>
                </time>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-posts"><?= $block->escapeHtml(__('No posts found.')) ?></p>
    <?php endif; ?>
</div>
```

You see:
- `<?php ... ?>` tags for PHP logic
- `<?= ... ?>` shorthand for `echo`
- `$block->getSomething()` to read data from the Block
- `escapeHtml()` and `escapeUrl()` for **security**

---

## 2. The `$block` variable

Every PHTML file has a `$block` variable automatically injected by Magento. It
is the **Block instance** you declared in Layout XML:

```xml
<block class="AlpineCommerce\Blog\Block\Listing"
       name="blog.post.list"
       template="AlpineCommerce_Blog::listing.phtml"/>
```

So `$block` is an instance of `AlpineCommerce\Blog\Block\Listing`. It has
access to everything the Block provides:

```php
// In the Block (PHP)
public function getPosts(): array
{
    return $this->repository->getList(/* criteria */)->getItems();
}

// In the template (PHTML)
$block->getPosts();  // calls the method above
```

You can also read raw data passed via `<arguments>` in Layout XML:

```php
$block->getData('posts_per_page');  // from XML
```

---

## 3. Essential Block helper methods

| Method | What it does |
|--------|--------------|
| `$block->escapeHtml($str)` | Escape HTML (prevents XSS) |
| `$block->escapeUrl($url)` | Escape a URL |
| `$block->escapeJs($str)` | Escape for use inside JavaScript strings |
| `$block->getUrl('route/path')` | Generate a frontend URL |
| `$block->getUrl('module/index/index')` | URL to a specific controller |
| `$block->formatDate($date)` | Format a date using store locale |
| `$block->formatPrice($amount)` | Format a price with currency |
| `$block->escapeXssInUrl($str)` | Escape URL fragments from user input |
| `$block->__("Hello")` | Translate a string |

> **Security rule**: *everything* that comes from the database or user input
> **must** be escaped before output. Magento core never outputs raw data.

---

## 4. The `__()` translation function

`$block->__("Hello")` and the standalone `__("Hello")` both translate strings.
Magento looks up the string in `i18n/fr_FR.csv`, `i18n/de_DE.csv`, etc.

```php
// In a .phtml template
__('Add to Cart')          // looks up in i18n/*.csv
```

```csv
# i18n/fr_FR.csv
"Add to Cart","Ajouter au panier"
```

---

## 5. Template fallback — overriding templates

When Magento looks for `AlpineCommerce_Blog::listing.phtml`, it searches:

1. **Current theme**: `app/design/frontend/<Vendor>/<theme>/AlpineCommerce_Blog/templates/listing.phtml`
2. **Module**: `app/code/AlpineCommerce/Blog/view/frontend/templates/listing.phtml`

So to override a template, you copy it into the theme at the same path.
Magento uses the theme version automatically — no configuration needed.

> This is how the LumaCheckout theme overrides checkout templates: files in
> `src/vendor/hyva-themes/magento2-luma-checkout/Magento_Checkout/templates/`
> are copied into the theme and customized.

---

## 6. Template inheritance and child blocks

You can add blocks **inside** a parent block in Layout XML:

```xml
<block class="AlpineCommerce\Blog\Block\Listing"
       name="blog.post.list"
       template="AlpineCommerce_Blog::listing.phtml">
    <block class="AlpineCommerce\Blog\Block\View\Toolbar"
           name="blog.toolbar"
           template="AlpineCommerce_Blog::view/toolbar.phtml"/>
</block>
```

Inside the parent template, the child block is available:

```php
// listing.phtml
echo $block->getChildHtml('blog.toolbar');
```

This is how Magento builds complex pages — parent templates render children.

---

## 7. What a PHTML should NOT do

❌ **Wrong** — PHTML with business logic:

```php
<?php
// DON'T: business logic in template
$connection = \Magento\Framework\App\ObjectManager::getInstance()
    ->get(\Magento\Framework\DB\Adapter\AdapterInterface::class);
$result = $connection->fetchAll("SELECT * FROM alpinecommerce_blog_post");
?>
```

✅ **Right** — PHTML only displays data from the Block:

```php
<?php $posts = $block->getPosts(); ?>
<!-- render the list -->
```

The **Block** should contain all logic (calling repositories, formatting
data). The **template** should only display it.

---

## 8. What you should remember

1. **PHTML = PHP + HTML** — the final rendering step.
2. `$block` is the Block instance declared in Layout XML — call its methods.
3. **Always escape** output: `escapeHtml()`, `escapeUrl()`, `escapeJs()`.
4. `escapeUrl($block->getUrl('route'))` for links; `escapeHtml($value)` for text.
5. **Templates fall back** theme → module; override by copying to the theme.
6. Keep logic in the **Block**, not in the template.

---

## 9. Where to go next

- **Previous**: [Layout XML](01-layout-xml.md)
- **Phase 2 CSS**: [`../css/01-themes-and-less.md`](../css/01-themes-and-less.md)
- **Deep dive**: [`../magento-layout-templates.md`](../magento-layout-templates.md)
  — all template methods, fallback rules, and AlpineCommerce examples.
- **Next phase**: [`../core/06-events-observers.md`](../core/06-events-observers.md)

---

*Sources: `src/app/code/AlpineCommerce/Blog/view/frontend/templates/listing.phtml`,
`src/vendor/magento/framework/View/Element/Template.php`,
`src/vendor/magento/module-catalog/view/frontend/templates/product/view.phtml`*
