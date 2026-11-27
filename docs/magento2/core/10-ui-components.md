# Magento 2 from Scratch — Admin UI Components

> **Series**: [Learning Path](../README.md) → Phase 4, Article 2 of 2
> **Previous**: [`09-rest-graphql.md`](09-rest-graphql.md) — REST & GraphQL
> **Next**: [Learning Path](../README.md) — you've reached the end of the quick-start!
> **Deep dive**: [`../magento-admin.md`](../magento-admin.md), [`../magento-js.md`](../magento-js.md)

---

The **frontend** uses Layout XML + PHTML templates (server-rendered HTML).
The **admin** uses **UI Components** — a different system entirely.

UI Components are XML files that Magento translates into **JavaScript** which
generates **HTML** with **Knockout bindings**. It is a client-side rendering
pipeline, very different from the frontend's server-side approach.

This article explains the concept at a high level. Full details are in the
deep-dive docs.

---

## 1. Frontend vs Admin rendering

| Aspect | Frontend | Admin |
|--------|----------|-------|
| Rendering | Server-side (PHP → HTML) | Client-side (JS → HTML) |
| Layout | Layout XML + PHTML | UI Component XML → RequireJS → KO |
| Templates | `.phtml` files | `.html` KO templates |
| Interactivity | jQuery + `data-mage-init` | Knockout + UI Component JS |
| Performance | Slower initial, full HTML on first render | Faster interactions, JS-heavy |

---

## 2. The UI Component pipeline

When you open an admin grid like `/admin/blog/post`:

```
Browser → Controller → ResultPage → Layout (_index.xml)
    ↓
UI Component XML (blog_post_listing.xml)
    ↓
JavaScript (Magento_Ui/js/core/app interprets the XML)
    ↓
JS Components (grid, columns, filters, toolbar)
    ↓
Knockout Templates (HTML rendered by the browser)
    ↓
AJAX → DataProvider → Repository → Collection → MySQL
    ↓
JSON → Grid updated
```

### The XML definition

```xml
<!-- view/adminhtml/ui_component/blog_post_listing.xml -->
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dataSource name="blog_post_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                AlpineCommerce\Blog\Ui\DataProvider\PostListingDataProvider
            </argument>
        </argument>
    </dataSource>

    <columns name="blog_post_columns">
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
            </settings>
        </column>
    </columns>
</listing>
```

### How the pieces connect

| Element | Role |
|---------|------|
| `<listing>` | Root element for a grid |
| `<dataSource>` | Tells Magento which PHP class fetches data |
| `<argument name="dataProvider">` | The DataProvider class (implements `DataProviderInterface`) |
| `<columns>` | Column definitions |
| `<column name="title">` | Individual column (label, sort order) |

### The DataProvider

```php
// src/app/code/AlpineCommerce/Blog/Ui/DataProvider/PostListingDataProvider.php
class PostListingDataProvider extends \Magento\Framework\View\Element\UiComponents\DataProvider\DataProvider
{
    public function getData()
    {
        // This is called via AJAX by the grid
        // Returns: { items: [...], totalRecords: 42 }
        return $this->collection->toArray();
    }
}
```

The grid calls `getData()` via AJAX. The DataProvider talks to the Repository
(which returns a Collection with SQL results).

---

## 3. Admin forms — UI Component form

Admin **forms** (like the blog post edit page) also use UI Components:

```xml
<!-- view/adminhtml/ui_component/blog_post_form.xml -->
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <fieldset name="blog_post_fieldset">
        <argument name="data" xsi:type="array">
            <item name="config" xsi:type="array">
                <item name="label" xsi:type="string">Blog Post</item>
            </item>
        </argument>

        <field name="title" form_element="input">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="label" xsi:type="string">Title</item>
                    <item name="formElement" xsi:type="string">input</item>
                </item>
            </argument>
        </field>

        <field name="content" form_element="textarea">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="label" xsi:type="string">Content</item>
                    <item name="formElement" xsi:type="string">textarea</item>
                </item>
            </argument>
        </field>
    </fieldset>
</form>
```

The form is rendered by JavaScript. The `dataProvider` in the layout XML
(`_edit.xml`) feeds the form with data from the Repository.

---

## 4. What you should remember

1. **UI Components** are XML → JavaScript → HTML. Used **only in admin**.
2. **Frontend** uses Layout XML + PHTML (server-rendered). Admin uses UI
   Components (client-rendered).
3. Every admin grid has: UI Component XML → DataProvider → Repository →
   Collection → MySQL.
4. Every admin form has: UI Component XML (fields) → DataProvider →
   Repository → save on submit.
5. The **DataProvider** is the bridge between XML and PHP.
6. You will spend most of your time **configuring** (not writing) UI Components.
   Copy an existing example and adapt it.

---

## 5. Where to go next

- You've reached the end of the **quick-start learning path**! 🎉
- **Deep dives**:
  - [`../magento-admin.md`](../magento-admin.md) — admin UI components, grids, forms
  - [`../magento-js.md`](../magento-js.md) — RequireJS, Knockout, UI Component JS
  - [`../magento-layout-templates.md`](../magento-layout-templates.md) — frontend templates
- **Project docs**: [`../../modules/BLOG.md`](../../modules/BLOG.md) — Blog module with full UI Component examples
- **Reference index**: [`../../README.md`](../../README.md)

---

*Sources: `src/app/code/AlpineCommerce/Blog/view/adminhtml/ui_component/blog_post_listing.xml`,
`src/app/code/AlpineCommerce/Blog/view/adminhtml/ui_component/blog_post_form.xml`,
`src/vendor/magento/module-ui/Component/UiComponentInterface.php`,
`src/vendor/magento/module-catalog/view/adminhtml/ui_component/product_listing.xml`*
