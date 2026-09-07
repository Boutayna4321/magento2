# Magento Layout & Templates

Magento 2 uses **Layout XML** to define page structure and **PHTML templates** for rendering.

## Layout XML

Layout XML defines:
- Which blocks appear on a page
- Their hierarchy (parent/child)
- Which templates they use
- Which CSS/JS to load

### Key concepts

| Concept | Description |
|---|---|
| **Page** | Top-level layout handle |
| **Container** | Holds blocks (e.g., `content`, `sidebar`, `head`) |
| **Block** | PHP class that renders output |
| **Template** | PHTML file that contains the HTML |
| **Handle** | Layout XML file name (e.g., `catalog_product_view.xml`) |

### Example

```xml
<page xmlns:xsi="...">
    <body>
        <referenceContainer name="content">
            <block class="Magento\Catalog\Block\Product\View"
                   name="product.view"
                   template="Magento_Catalog::product/view.phtml"/>
        </referenceContainer>
    </body>
</page>
```

## PHTML Templates

PHTML = PHP + HTML. Templates receive block data via `$block->getData()`.

```php
<?php /** @var $block \Magento\Catalog\Block\Product\View */ ?>
<h1><?= $block->escapeHtml($block->getProduct()->getName()) ?></h1>
```

## Where to go next

- **Full reference**: [`../magento2/magento-layout-templates.md`](../magento2/magento-layout-templates.md)
- **JavaScript**: [`magento-js.md`](magento-js.md)

---

*Prerequisite for: frontend customization, theme development*
