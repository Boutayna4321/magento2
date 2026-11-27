# Magento 2 from Scratch — The Request Lifecycle

> **Series**: [Learning Path](../README.md) → Phase 1, Article 3 of 5
> **Previous**: [`02-module-anatomy.md`](02-module-anatomy.md) — Inside a module
> **Next**: [`04-dependency-injection.md`](04-dependency-injection.md) — DI & Service Contracts
> **Deep dive**: [`../magento-components.md`](../magento-components.md)

---

You now know what a module is. Let's follow a real request through Magento
to see how everything fits together.

We will trace `GET /blog` — the AlpineCommerce Blog listing page.

---

## 1. The entry point: `index.php`

Every HTTP request goes to `pub/index.php`:

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->createApplication(\Magento\Framework\App\http::class);
$bootstrap->run($obj);
```

This bootstraps the Magento framework, creates the application object, and
**runs it**. Everything below happens inside `bootstrap->run()`.

---

## 2. Area detection

Magento looks at the URL and determines the **area**:

- URL starts with `/admin` → `adminhtml`
- URL starts with `/rest` → `webapi_rest`
- URL starts with `/graphql` → `graphql`
- Everything else → `frontend`

The area determines **which configuration files** are loaded (e.g.,
`etc/frontend/di.xml` vs `etc/adminhtml/di.xml`).

---

## 3. The Front Controller dispatches to a Router

The Front Controller asks each **Router** to match the URL:

1. **Base router** — matches standard module routes (`/blog` → `Blog` module).
2. **CMS router** — matches CMS pages (`/about-us`).
3. **Default router** — the fallback (matches nothing → 404).

For `/blog`, the Base router reads `etc/frontend/routes.xml`:

```xml
<route id="blog" frontName="blog">
    <module name="AlpineCommerce_Blog"/>
</route>
```

It maps the URL to the **Controller class**:

```
/blog → AlpineCommerce\Blog\Controller\Index\Index
```

---

## 4. The Controller runs

```php
// src/app/code/AlpineCommerce/Blog/Controller/Index/Index.php
class Index extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\PageFactory $pageFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()->getTitle()->set('Blog');
        return $resultPage;
    }
}
```

The controller's job is to:
1. **Orchestrate** — call the right service to get data.
2. **Return a Result** — usually a `ResultPage` (full HTML) or `Json` (API).

> **The Controller never touches the database.** It calls a Repository (Service
> Contract). The Repository delegates to a ResourceModel for SQL.

---

## 5. Layout XML assembles the page

When the Controller returns a `ResultPage`, Magento loads the **Layout XML**:

```
/blog  →  route=blog, controller=Index, action=Index
       →  layout file: blog_index_index.xml
```

**`view/frontend/layout/blog_index_index.xml`**:

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

This says: "put a Block called `blog.post.list` inside the `content`
container, and use `listing.phtml` as its template."

---

## 6. The Block prepares data

```php
// src/app/code/AlpineCommerce/Blog/Block/Listing.php
class Listing extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \AlpineCommerce\Blog\Api\BlogPostRepositoryInterface $repository
    ) {
        parent::__construct($context);
        $this->repository = $repository;
    }

    public function getPosts(): array
    {
        return $this->repository->getList(/* search criteria */)->getItems();
    }
}
```

The Block receives the **Repository interface** via dependency injection and
fetches the data. It exposes `getPosts()` to the template.

---

## 7. The template renders HTML

**`view/frontend/templates/listing.phtml`**:

```php
<?php /** @var $block \AlpineCommerce\Blog\Block\Listing */ ?>
<div class="blog-post-list">
    <?php foreach ($block->getPosts() as $post): ?>
        <article class="blog-post-item">
            <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
            <div class="content">
                <?= /* @noEscapeOutput */ $post->getContent() ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
```

The template calls `$block->getPosts()` (which we defined above), escapes
everything for security, and outputs HTML.

---

## 8. The HTML reaches the browser

The final HTML is wrapped in the **default layout** (header, footer, etc.)
and sent to the browser along with CSS and JavaScript.

---

## 9. The REST API version (simpler path)

For a REST call like `GET /rest/V1/blog/posts`, the flow skips the Controller
and Template:

```
Browser
  → Nginx → index.php
    → WebAPI Router (reads webapi.xml)
      → Service Contract (BlogPostRepositoryInterface::getList)
        → Repository implementation
          → ResourceModel → SQL → MySQL
      → JSON response
```

No Controller, no Block, no Template — the **Service Contract is called
directly**.

---

## 10. The admin version (different path)

For `GET /admin/blog/post`, the Controller returns a `ResultPage`, but instead
of Layout XML + Templates, the page uses **UI Components** (XML → JavaScript
→ HTML). The flow is:

```
Controller → ResultPage → Layout (_index.xml) → UI Component XML (listing)
  → DataProvider → Repository → Collection → MySQL
  → JavaScript (Magento_Ui/js/core/app) → KO Templates → HTML
```

We'll cover UI Components in depth in
[Article 10](09-rest-graphql-and-ui-components.md), but for now just know the
admin uses a **different rendering pipeline** (JS-driven, not server-driven).

---

## 11. What you should remember

1. **Every request** starts at `pub/index.php`.
2. Magento detects the **area** (frontend, adminhtml, webapi_rest).
3. The **Router** maps URL → Controller class via `routes.xml`.
4. The **Controller** orchestrates and returns a Result (never touches SQL).
5. **Layout XML** (`{route}_{controller}_{action}.xml`) defines the page
   structure: which blocks go where.
6. The **Block** prepares data; the **Template** (.phtml) renders HTML.
7. **REST API** calls skip Controller/Template — they go straight to the
   Repository via `webapi.xml`.
8. **Admin pages** use UI Components instead of Layout XML + Templates.

---

## 12. Where to go next

- **Previous**: [Module Anatomy](02-module-anatomy.md)
- **Article 4**: [Dependency Injection](04-dependency-injection.md) — how the
  Repository and Block get their dependencies injected.
- **Deep dive**: [`../magento-components.md`](../magento-components.md#2-the-complete-flow-of-a-magento-page)
  — the full component interaction diagram with flowcharts.

---

*Sources: `src/vendor/magento/framework/App/http.php`,
`src/vendor/magento/framework/App/FrontController.php`,
`src/app/code/AlpineCommerce/Blog/Controller/Index/Index.php`*
