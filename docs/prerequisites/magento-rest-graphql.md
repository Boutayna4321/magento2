# Magento 2 — REST & GraphQL API

> **Objective**: learn how Magento 2 exposes its data and business logic
> through APIs. This guide covers both REST (JSON over HTTP) and GraphQL,
> with AlpineCommerce examples.

---

## 1. Why APIs in Magento?

APIs allow external systems to interact with Magento **without PHP**:
- Mobile apps (iOS, Android)
- Frontend frameworks (React, Vue, Angular)
- Third-party integrations (ERP, CRM, PIM)
- Headless commerce (Magento as a backend only)

**AlpineCommerce uses APIs for**:
- Product reviews submission (frontend AJAX)
- Product questions submission (frontend AJAX)
- Store pickup selection (checkout)
- Loyalty points redemption (checkout)
- CustomerCare VIP status (admin + customer)

---

## 2. REST API

### 2.1 Architecture

```
Client (JS, mobile app, curl)
    ↓ HTTP Request (JSON)
Magento WebAPI Router
    ↓ matches route
Service Contract (Interface)
    ↓
Implementation (Model/Repository)
    ↓
ResourceModel (SQL)
    ↓
MySQL
```

### 2.2 Configuration

```xml
<!-- etc/webapi.xml -->
<routes xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <route url="/V1/alphacommerce/blog/posts" method="GET">
        <service class="AlpineCommerce\Blog\Api\PostRepositoryInterface" method="getList"/>
        <resources>
            <resource ref="anonymous"/>
        </resources>
    </route>
    
    <route url="/V1/alphacommerce/blog/posts" method="POST">
        <service class="AlpineCommerce\Blog\Api\PostRepositoryInterface" method="save"/>
        <resources>
            <resource ref="AlpineCommerce_Blog::post"/>
        </resources>
    </route>
</routes>
```

### 2.3 Key elements

| Element | Purpose | Example |
|---------|---------|---------|
| `url` | API endpoint path | `/V1/alphacommerce/blog/posts` |
| `method` | HTTP method | `GET`, `POST`, `PUT`, `DELETE` |
| `service` | Interface + method | `PostRepositoryInterface::getList` |
| `resources` | ACL required | `anonymous` or `AlpineCommerce_Blog::post` |

### 2.4 HTTP methods

| Method | Action | Example |
|--------|--------|---------|
| `GET` | Read data | `GET /V1/blog/posts` |
| `POST` | Create data | `POST /V1/blog/posts` |
| `PUT` | Update data | `PUT /V1/blog/posts/1` |
| `DELETE` | Delete data | `DELETE /V1/blog/posts/1` |

### 2.5 Authentication

#### 2.5.1 Integration Token (server-to-server)

```bash
# Create integration token
curl -X POST "https://magento.com/rest/V1/integration/admin/token" \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}'
```

#### 2.5.2 Customer Token (logged-in customer)

```bash
curl -X POST "https://magento.com/rest/V1/integration/customer/token" \
     -H "Content-Type: application/json" \
     -d '{"username":"customer@example.com","password":"password123"}'
```

#### 2.5.3 Using the token

```bash
curl -H "Authorization: Bearer <token>" \
     "https://magento.com/rest/V1/alphacommerce/blog/posts"
```

### 2.6 Response format

**Success**:
```json
{
    "id": 1,
    "title": "Hello World",
    "content": "This is my first post",
    "created_at": "2026-08-11T10:00:00"
}
```

**List with pagination**:
```json
{
    "items": [
        {"id": 1, "title": "Post 1"},
        {"id": 2, "title": "Post 2"}
    ],
    "total_count": 42,
    "page_info": {
        "page": 1,
        "page_size": 10
    }
}
```

**Error**:
```json
{
    "message": "The post with ID \"999\" does not exist.",
    "parameters": {
        "id": "999"
    }
}
```

---

## 3. GraphQL API

### 3.1 Concept

GraphQL is an alternative to REST where the client **specifies exactly**
what data it needs in a single request.

```graphql
query {
  posts(filter: {status: "published"}, pageSize: 10) {
    items {
      id
      title
      content
    }
    total_count
  }
}
```

**Response**:
```json
{
  "data": {
    "posts": {
      "items": [
        {"id": 1, "title": "Hello", "content": "World"}
      ],
      "total_count": 1
    }
  }
}
```

### 3.2 Configuration

```xml
<!-- etc/webapi.xml -->
<routes xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <route url="/graphql" method="POST">
        <service class="Magento\GraphQl\Controller\Graphql" method="execute"/>
        <resources>
            <resource ref="anonymous"/>
        </resources>
    </route>
</routes>
```

### 3.3 GraphQL vs REST

| Aspect | REST | GraphQL |
|--------|------|---------|
| **Endpoint** | Multiple (`/posts`, `/posts/1`) | Single (`/graphql`) |
| **Data fetching** | Fixed by server | Client specifies fields |
| **Over-fetching** | Common (gets all fields) | None (gets only requested fields) |
| **Under-fetching** | Common (needs multiple requests) | None (nested queries) |
| **Caching** | HTTP caching (simple) | Complex (no HTTP caching by default) |
| **Learning curve** | Low | Higher (schema, queries, mutations) |

---

## 4. Service Contracts and APIs

### 4.1 The same interface, multiple access methods

```php
// Api/PostRepositoryInterface.php
interface PostRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;
    public function save(PostInterface $post): PostInterface;
    public function getById(int $id): PostInterface;
    public function delete(PostInterface $post): bool;
}
```

This single interface is used by:
- **REST API**: via `webapi.xml`
- **GraphQL**: via resolvers
- **Controllers**: directly in PHP
- **Blocks**: directly in PHP
- **CLI commands**: directly in PHP

### 4.2 Data Objects

```php
// Api/Data/PostInterface.php
interface PostInterface
{
    public function getId(): ?int;
    public function getTitle(): string;
    public function setTitle(string $title): PostInterface;
    public function getContent(): ?string;
    public function setContent(?string $content): PostInterface;
}
```

Data Objects are:
- Simple property bags (getters/setters)
- Used in API responses
- Used as method parameters
- Automatically serialized to JSON by Magento

---

## 5. AlpineCommerce API Examples

### 5.1 ProductReviews REST API

```xml
<!-- etc/webapi.xml -->
<route url="/V1/alphacommerce/product-reviews" method="POST">
    <service class="AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface" method="save"/>
    <resources>
        <resource ref="customer"/>
    </resources>
</route>
```

```php
// Api/ReviewRepositoryInterface.php
interface ReviewRepositoryInterface
{
    public function save(ReviewInterface $review): ReviewInterface;
}
```

**Frontend AJAX call** (`review-form.js`):
```js
$.ajax({
    url: '/rest/V1/alphacommerce/product-reviews',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        productId: 1,
        title: 'Great product',
        detail: 'Love it!',
        rating: 5
    }),
    headers: {
        'Authorization': 'Bearer ' + customerToken
    }
});
```

### 5.2 ProductQuestions REST API

```xml
<route url="/V1/alphacommerce/product-questions" method="POST">
    <service class="AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface" method="save"/>
    <resources>
        <resource ref="customer"/>
    </resources>
</route>
```

### 5.3 StorePickup REST API (checkout)

```php
// Controller/Index/StorePickup.php (checkout)
public function execute(): \Magento\Framework\Controller\Result\Json
{
    $data = $this->getRequest()->getContent();
    $sourceCode = json_decode($data, true)['sourceCode'] ?? '';
    
    $this->checkoutSession->setShippingPickupSourceCode($sourceCode);
    
    return $this->resultFactory->create(ResultFactory::TYPE_JSON)
        ->setData(['success' => true]);
}
```

Called via `mage/storage.post('/carts/mine/store-pickup', ...)`.

### 5.4 CustomerCare REST API

```xml
<!-- etc/webapi.xml -->
<route url="/V1/customercare/vip-status/:customerId" method="GET">
    <service class="AlpineCommerce\CustomerCare\Api\CustomerCareInterface" method="getVipStatus"/>
    <resources>
        <resource ref="AlpineCommerce_CustomerCare::config"/>
    </resources>
</route>

<route url="/V1/customercare/me/vip-status" method="GET">
    <service class="AlpineCommerce\CustomerCare\Api\CustomerCareInterface" method="getMyVipStatus"/>
    <resources>
        <resource ref="customer"/>
    </resources>
</route>
```

---

## 6. API Security

### 6.1 ACL resources

```xml
<resources>
    <resource ref="anonymous"/>           <!-- No auth required -->
    <resource ref="customer"/>            <!-- Logged-in customer -->
    <resource ref="AlpineCommerce_Blog::post"/>  <!-- Admin with permission -->
</resources>
```

| Resource | Who can access |
|----------|---------------|
| `anonymous` | Everyone (no token needed) |
| `customer` | Any logged-in customer |
| `AlpineCommerce_Blog::post` | Admin users with that ACL permission |

### 6.2 Rate limiting

```xml
<route url="/V1/alphacommerce/blog/posts" method="GET">
    <service class="..." method="getList"/>
    <resources>
        <resource ref="anonymous"/>
    </resources>
    <rate limit="100" period="3600"/>  <!-- 100 requests per hour -->
</route>
```

### 6.3 Input validation

```php
// Api/Data/ReviewInterface.php
interface ReviewInterface
{
    public function getProductId(): int;
    public function getRating(): int;
    public function getTitle(): string;
}

// Api/ReviewRepositoryInterface.php
interface ReviewRepositoryInterface
{
    /**
     * @param ReviewInterface $review
     * @return ReviewInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save(ReviewInterface $review): ReviewInterface;
}
```

Validation happens in the implementation:
```php
public function save(ReviewInterface $review): ReviewInterface
{
    $rating = $review->getRating();
    
    if ($rating < 1 || $rating > 5) {
        throw new InputException(__('Rating must be between 1 and 5.'));
    }
    
    if (empty($review->getTitle())) {
        throw new InputException(__('Title is required.'));
    }
    
    // Save...
}
```

---

## 7. Testing APIs

### 7.1 REST API with curl

```bash
# GET
curl -H "Authorization: Bearer <token>" \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts

# POST
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{"title":"New Post","content":"Hello"}' \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts

# DELETE
curl -X DELETE \
     -H "Authorization: Bearer <token>" \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts/1
```

### 7.2 GraphQL with curl

```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"query": "{ posts { items { id title } } }"}' \
     https://localhost:8080/graphql
```

### 7.3 REST API with JavaScript

```js
// Using mage/storage
define(['mage/storage', 'mage/translate'], function (storage, $t) {
    'use strict';
    
    function submitReview(reviewData) {
        return storage.post(
            '/rest/V1/alphacommerce/product-reviews',
            JSON.stringify(reviewData),
            false,
            'application/json'
        );
    }
});
```

---

## 8. Common Issues

### 8.1 401 Unauthorized

**Cause**: missing or invalid token.

**Solution**:
```bash
# Get a new token
curl -X POST "https://localhost:8080/rest/V1/integration/admin/token" \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}'
```

### 8.2 403 Forbidden

**Cause**: user doesn't have the required ACL permission.

**Solution**:
```bash
# Check ACL in etc/acl.xml
# Assign permission in admin: System > Permissions > User Roles
# Or use a different ACL resource in webapi.xml
```

### 8.3 Route not found (404)

**Cause**: URL doesn't match any route in `webapi.xml`.

**Solution**:
```bash
# Check webapi.xml
# Verify URL matches exactly (case-sensitive)
# Check HTTP method (GET vs POST)
```

### 8.4 "Class not found" for service

**Cause**: interface not found or method doesn't exist.

**Solution**:
```bash
# Verify interface exists
ls src/app/code/AlpineCommerce/Blog/Api/PostRepositoryInterface.php

# Verify method exists
grep "function getList" src/app/code/AlpineCommerce/Blog/Api/PostRepositoryInterface.php
```

---

## 9. Summary

| Concept | Purpose | Example |
|---------|---------|---------|
| **REST API** | HTTP + JSON endpoints | `/rest/V1/alphacommerce/blog/posts` |
| **GraphQL** | Single endpoint, flexible queries | `/graphql` |
| **webapi.xml** | Route configuration | URL, method, service, ACL |
| **Service Contract** | Business logic interface | `PostRepositoryInterface` |
| **Authentication** | Bearer token (admin/customer) | `Authorization: Bearer <token>` |
| **ACL resource** | Permission check | `anonymous`, `customer`, `AlpineCommerce_Blog::post` |
| **Data Object** | Property bag for API data | `PostInterface`, `ReviewInterface` |

### REST vs GraphQL in AlpineCommerce

| Module | API Type | Endpoint |
|--------|----------|----------|
| Blog | REST | `/rest/V1/alphacommerce/blog/posts` |
| Faq | REST | `/rest/V1/alphacommerce/faqs` |
| ProductReviews | REST | `/rest/V1/alphacommerce/product-reviews` |
| ProductQuestions | REST | `/rest/V1/alphacommerce/product-questions` |
| StorePickup | REST (checkout) | `/rest/V1/carts/mine/store-pickup` |
| LoyaltyProgram | REST (checkout) | `/rest/V1/carts/mine/loyalty-points` |
| CustomerCare | REST | `/rest/V1/customercare/vip-status/:customerId` |

---

*Last updated: 2026-08-11.*
