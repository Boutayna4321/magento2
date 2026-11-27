# Magento 2 from Scratch — REST & GraphQL

> **Series**: [Learning Path](../README.md) → Phase 4, Article 1 of 2
> **Previous**: [`08-preferences.md`](08-preferences.md) — Preferences
> **Next**: [`10-ui-components.md`](10-ui-components.md) — Admin UI Components
> **Deep dive**: [`../magento-rest-graphql.md`](../magento-rest-graphql.md)

---

Magento exposes its functionality through **REST** and **GraphQL** APIs. The
key insight: **you don't write API controllers**. You declare the route in
XML, and Magento calls your Repository interface directly.

This article shows how it works.

---

## 1. REST — the `webapi.xml` file

### Declaring an endpoint

```xml
<!-- etc/webapi.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <service class="AlpineCommerce\Blog\Api\BlogPostRepositoryInterface"
             method="getList"
             alias="alpineBlogGetList"/>
</config>
```

This single declaration creates:

```
GET /rest/V1/blog/posts     →  BlogPostRepositoryInterface::getList()
```

| Attribute | Purpose |
|-----------|---------|
| `class` | The Service Contract interface |
| `method` | The method to call |
| `alias` | The REST route alias |

### How Magento maps the method to an HTTP verb

Magento reads the method name and decides:

| Method prefix | HTTP verb |
|---------------|-----------|
| `get` | GET |
| `getList` | GET (list operation) |
| `save` | POST |
| `create` | POST |
| `update` | PUT |
| `delete` | DELETE |

```xml
<!-- Multiple endpoints from one interface -->
<service class="AlpineCommerce\Blog\Api\BlogPostRepositoryInterface"
         method="getList" alias="getBlogPosts"/>
<!-- → GET /rest/V1/blog-posts -->

<service class="AlpineCommerce\Blog\Api\BlogPostRepositoryInterface"
         method="getById" alias="getBlogPost"/>
<!-- → GET /rest/V1/blog-posts/{id} -->
```

### Authentication

| `webapi.xml` setting | Who can access |
|----------------------|----------------|
| `<acl>... <resource ref="anonymous"/>` | Anonymous (guest) |
| `<acl>resource ref="Magento_Backend::admin"` | Admin token |
| `<acl>... <resource ref="self"/>` | Customer token |

Most read endpoints are anonymous (products, categories, blog posts).
Write endpoints (save, delete) require authentication.

---

## 2. REST in practice

### Calling the API

```bash
# List blog posts
curl -X GET "http://localhost:8080/rest/V1/blog/posts"

# Get a single post
curl -X GET "http://localhost:8080/rest/V1/blog/posts/1"

# Create (requires admin token)
curl -X POST "http://localhost:8080/rest/V1/blog/posts" \
  -H "Authorization: Bearer <admin-token>" \
  -H "Content-Type: application/json" \
  -d '{"title": "Hello", "content": "World"}'
```

### Getting an admin token

```bash
curl -X POST "http://localhost:8080/rest/V1/admin/token" \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'
```

Returns a JWT token you use as `Authorization: Bearer <token>`.

---

## 3. GraphQL — the modern alternative

GraphQL is a single endpoint (`/graphql`) that takes a **query** describing
exactly what data you want:

```graphql
query {
    blogPosts {
        items {
            entity_id
            title
            content
            created_at
        }
    }
}
```

```bash
curl -X POST "http://localhost:8080/graphql" \
  -H "Content-Type: application/json" \
  -d '{"query": "{ blogPosts { items { title } } }"}'
```

### How GraphQL works in Magento

Instead of `webapi.xml`, GraphQL uses **resolvers**:

```
View file: etc/schema.graphqls
Resolver:  src/app/code/Module/.../Model/Resolver/BlogPosts.php
            → calls the same Repository interface
```

```graphql
# etc/schema.graphqls
type Query {
    blogPosts: BlogPosts!
}

type BlogPosts {
    items: [BlogPost!]!
    total_count: Int!
}

type BlogPost {
    entity_id: Int!
    title: String!
    content: String!
    created_at: String!
}
```

```php
// Model/Resolver/BlogPosts.php
class BlogPosts implements ResolverInterface
{
    public function __invoke(Field $field, $context, ResolveInfo $info, ?array $args)
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $result = $this->repository->getList($searchCriteria);
        return [
            'items' => $result->getItems(),
            'total_count' => $result->getTotalCount()
        ];
    }
}
```

> **REST and GraphQL use the same Repository interfaces under the hood.**
> The difference is only in how the query is structured and the response format.

---

## 4. How AlpineCommerce exposes APIs

| Module | Interface | REST endpoints | GraphQL |
|--------|-----------|----------------|---------|
| Blog | `BlogPostRepositoryInterface` | GET `/rest/V1/blog/posts`, GET `/rest/V1/blog/posts/{id}` | Custom resolver |
| StorePickup | `StoreInfoRepositoryInterface` | GET `/rest/V1/alpinecommerce/stores` | — |
| ProductReviews | `ReviewInterface` | POST `/rest/V1/alpinecommerce/product-reviews` | — |
| CustomerCare | `CustomerCareInterface` | GET `/rest/V1/alpinecommerce/customers/{id}/vip` | — |
| HealthCheck | Custom | GET `/rest/V1/alpinecommerce/health` | — |

---

## 5. What you should remember

1. **REST** endpoints are declared in `webapi.xml` — no controller code needed.
2. The **method name** determines the HTTP verb (`get` → GET, `save` → POST).
3. **Authentication** is controlled in `webapi.xml` (anonymous, admin, customer).
4. **GraphQL** uses `schema.graphqls` (type definitions) + Resolver classes.
5. Both REST and GraphQL call the **same Service Contract interfaces**.
6. The `LumaCheckout` theme does **not** expose any APIs — it only overrides
   CSS.

---

## 6. Where to go next

- **Article 2**: [UI Components](10-ui-components.md) — the admin's JS-driven rendering
- **Previous**: [Preferences](08-preferences.md)
- **Phase 5**: [`../README.md#phase-5--deep-dives`](../README.md#phase-5--deep-dives)
- **Deep dive**: [`../magento-rest-graphql.md`](../magento-rest-graphql.md),
  [`../magento-cli.md`](../magento-cli.md) — CLI for testing APIs.

---

*Sources: `src/app/code/AlpineCommerce/Blog/etc/webapi.xml`,
`src/app/code/AlpineCommerce/Blog/Api/BlogPostRepositoryInterface.php`,
`src/vendor/magento/module-webapi/etc/webapi.xsd`,
`src/vendor/magento/module-graph-ql/etc/schema.graphqls`*
