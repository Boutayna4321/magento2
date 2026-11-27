# Magento 2 from Scratch — Layout XML

> **Series**: [Learning Path](../README.md) → Phase 2 (HTML), Article 1 of 2
> **Previous**: [`../core/05-database-model.md`](../core/05-database-model.md) — EAV vs Flat
> **Next**: [`../html/02-phtml-templates.md`](02-phtml-templates.md) — PHTML templates
> **Deep dive**: [`../magento-layout-templates.md`](../magento-layout-templates.md)

---

You know the Controller returns a `ResultPage`. But where does the actual
HTML come from? The answer is **Layout XML** — a declarative file that says
"put this block here, that block there."

This article explains Layout XML without drowning you in XML noise.

---

## 1. The naming convention

Magento maps URLs to layout files automatically:

```
/blog             →  blog_index_index.xml
/blog/post/view   →  blog_post_view.xml
/cart             →  checkout_cart_index.xml
/checkout         →  checkout_index_index.xml
```

Pattern: `{route}_{controller}_{action}.xml` (lowercase, underscores).

> **Tip**: if you are unsure which layout file a page uses, open Chrome DevTools
> → Network → reload the page → check the response HTML. The `<body>` class
> tells you: `checkout-index-index`, `blog-index-index`, etc.

---

## 2. The basic structure

Every layout file looks like this:

```xml
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      layout="1column">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\Listing"
                   name="blog.post.list"
                   template="AlpineCommerce_Blog::listing.phtml"/>
        </referenceContainer>
    </body>
</page>
```

Three things to understand:

| Element | Role | Example |
|---------|------|---------|
| `<page>` | Root wrapper | — |
| `<body>` | Everything goes here | — |
| `layout="1column"` | Page template (1col, 2col-main, 3col) | — |

### Containers

Magento defines **containers** in the default layout. Think of them as empty
slots:

| Container | Holds | Defined in |
|-----------|-------|------------|
| `page.top` | Header, nav | `Magento_Theme/layout/default.xml` |
| `content` | Main content | `Magento_Theme/layout/default.xml` |
| `sidebar.main` | Left sidebar | `Magento_Theme/layout/default.xml` |
| `sidebar.additional` | Right sidebar | `Magento_Theme/layout/default.xml` |
| `page.bottom` | Footer | `Magento_Theme/layout/default.xml` |

---

## 3. Adding a block: `<block>`

```xml
<referenceContainer name="content">
    <block class="AlpineCommerce\Blog\Block\Listing"
           name="blog.post.list"
           template="AlpineCommerce_Blog::listing.phtml"
           before="-"/>
</referenceContainer>
```

| Attribute | Required | Purpose |
|-----------|----------|---------|
| `class` | Yes | PHP class for the block |
| `name` | Yes | Unique ID within the page |
| `template` | No | `.phtml` file path |
| `before` / `after` | No | Ordering (`-` means first/last) |

The **`template`** path uses a shorthand:
`AlpineCommerce_Blog::listing.phtml` means:

```
app/code/AlpineCommerce/Blog/view/frontend/templates/listing.phtml
```

The `::` separates the module name from the relative template path.

---

## 4. Layout merge — the cascade

Magento **merges** layout files. Your `blog_index_index.xml` is layered on top
of `default.xml` (global), and module-level `default.xml`s from dependencies.

This means:
- You can add a block to `content` without redefining the entire page.
- The header, footer, breadcrumbs come from `Magento_Theme/layout/default.xml`.
- You only declare what is **unique** to your page.

### Example: adding a sidebar

```xml
<!-- blog_index_index.xml -->
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      layout="2columns-left">
    <body>
        <referenceContainer name="sidebar.main">
            <block class="AlpineCommerce\Blog\Block\CategoryList"
                   name="blog.category.sidebar"
                   template="AlpineCommerce_Blog::category/sidebar.phtml"/>
        </referenceContainer>
    </body>
</page>
```

The `2columns-left` layout gives you the `sidebar.main` container. You just
add your block into it.

---

## 5. Moving and removing blocks

### `<move>` — relocate a block

```xml
<!-- Move breadcrumbs below the page title -->
<move element="breadcrumbs" destination="content" before="-"/>
```

### `<referenceBlock name="..." remove="true">` — remove a block

```xml
<!-- Remove the "Add to Cart" button from a specific page -->
<referenceBlock name="product.info.addtocart" remove="true"/>
```

---

## 6. Block arguments — passing data

You can pass configuration to a block via `<arguments>`:

```xml
<block class="AlpineCommerce\Blog\Block\Listing"
       name="blog.post.list"
       template="AlpineCommerce_Blog::listing.phtml">
    <arguments>
        <argument name="posts_per_page" xsi:type="number">10</argument>
        <argument name="show_date" xsi:type="boolean">true</argument>
    </arguments>
</block>
```

In the Block:

```php
public function getPostsPerPage(): int
{
    return (int) $this->getData('posts_per_page'); // 10
}
```

In the template:

```php
<?php if ($block->getData('show_date')): ?>
    <time><?= $block->escapeHtml($post->getCreatedAt()) ?></time>
<?php endif; ?>
```

---

## 7. Layout XML for the checkout (LumaCheckout)

The LumaCheckout theme overrides checkout styles **through Layout XML** — but
the actual checkout page is rendered by Magento's own
`Magento_Checkout/layout/checkout_index_index.xml`.

The `_extend.less` you have open targets `.checkout-index-index` — that body
class is set by the checkout's layout file. The theme doesn't redefine the
layout at all; it only adds CSS overrides via the Less import chain.

---

## 8. Common Layout XML pitfalls

| Mistake | Symptom | Fix |
|---------|---------|-----|
| Wrong filename | Block doesn't appear | Follow `{route}_{controller}_{action}.xml` |
| `name` not unique | One block silently overwrites the other | Use a unique name |
| Missing `class` | Fatal error: class not found | Every `<block>` needs a PHP class |
| `template` path typo | Blank page | Check the module name before `::` |

---

## 9. What you should remember

1. **Layout file name** = `{route}_{controller}_{action}.xml`.
2. **`<referenceContainer name="content">`** targets an existing container.
3. **`<block>`** adds content: `class` (PHP), `name` (unique ID), `template` (`.phtml`).
4. Layout files are **merged** — declare only what is unique to your page.
5. `<move>` relocates, `<referenceBlock remove="true">` removes.
6. `<arguments>` passes configuration data to the Block.

---

## 10. Where to go next

- **Article 2**: [PHTML Templates](02-phtml-templates.md) — the `.phtml` files
  that generate HTML.
- **Previous**: [Database Model](../core/05-database-model.md)
- **Phase 2 CSS**: [`../css/01-themes-and-less.md`](../css/01-themes-and-less.md)
- **Deep dive**: [`../magento-layout-templates.md`](../magento-layout-templates.md)
  — every XML instruction, template fallback, and AlpineCommerce examples.

---

*Sources: `src/vendor/magento/module-theme/view/frontend/layout/default.xml`,
`src/app/code/AlpineCommerce/Blog/view/frontend/layout/blog_index_index.xml`,
`src/vendor/magento/theme-frontend-luma/Magento_Checkout/layout/checkout_index_index.xml`*
