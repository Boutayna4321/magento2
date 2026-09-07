# Magento 2 — REST & GraphQL API

> **Objective**: learn how Magento 2 exposes its data and business logic
> through APIs. This guide covers both REST (JSON over HTTP) and GraphQL,
> with generic Magento examples and AlpineCommerce reference.

---

## Table of Contents

1. [Why APIs in Magento?](#1-why-apis-in-magento)
2. [REST API](#2-rest-api)
3. [GraphQL API](#3-graphql-api)
4. [Service Contracts and APIs](#4-service-contracts-and-apis)
5. [API Security](#5-api-security)
6. [Testing APIs](#6-testing-apis)
7. [Common Issues](#7-common-issues)
8. [Summary](#8-summary)
9. [AlpineCommerce Reference](#9-alpinecommerce-reference)

---

## 1. Why APIs in Magento?

APIs allow external systems to interact with Magento **without PHP**:
- Mobile apps (iOS, Android)
- Frontend frameworks (React, Vue, Angular)
- Third-party integrations (ERP, CRM, PIM)
- Headless commerce (Magento as a backend only)

**Source**: `src/vendor/magento/module-webapi/` — Magento's WebAPI module handles all REST and GraphQL requests.

**Official documentation**: [Web APIs](https://developer.adobe.com/commerce/php/architecture/web-api/)

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
    <route url="/V1/vendor/module/posts" method="GET">
        <service class="Vendor\Module\Api\PostRepositoryInterface" method="getList"/>
        <resources>
            <resource ref="anonymous"/>
        </resources>
    </route>
    
    <route url="/V1/vendor/module/posts" method="POST">
        <service class="Vendor\Module\Api\PostRepositoryInterface" method="save"/>
        <resources>
            <resource ref="Vendor_Module::post"/>
        </resources>
    </route>
</routes>
```

**Source**: `src/vendor/magento/module-catalog/etc/webapi.xml` — Magento core defines REST routes for catalog operations.

### 2.3 Key elements

| Element | Purpose | Example |
|---------|---------|---------|
| `url` | API endpoint path | `/V1/vendor/module/posts` |
| `method` | HTTP method | `GET`, `POST`, `PUT`, `DELETE` |
| `service` | Interface + method | `PostRepositoryInterface::getList` |
| `resources` | ACL required | `anonymous` or `Vendor_Module::post` |

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
     "https://magento.com/rest/V1/vendor/module/posts"
```

**Source**: `src/vendor/magento/module-integration/` — handles integration and customer token authentication.

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

### 3.2 GraphQL vs REST

| Aspect | REST | GraphQL |
|--------|------|---------|
| **Endpoint** | Multiple (`/posts`, `/posts/1`) | Single (`/graphql`) |
| **Data fetching** | Fixed by server | Client specifies fields |
| **Over-fetching** | Common (gets all fields) | None (gets only requested fields) |
| **Under-fetching** | Common (needs multiple requests) | None (nested queries) |
| **Caching** | HTTP caching (simple) | Complex (no HTTP caching by default) |
| **Learning curve** | Low | Higher (schema, queries, mutations) |

**Source**: `src/vendor/magento/module-graph-ql/` — Magento's GraphQL module.

**Official documentation**: [GraphQL](https://developer.adobe.com/commerce/php/architecture/modules/graphql/)

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

**Source**: `src/vendor/magento/module-catalog/Api/PostRepositoryInterface.php` — Magento core uses the same interface for all access methods.

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

**Source**: `src/vendor/magento/module-catalog/Api/Data/ProductInterface.php` — Magento core data object interface.

---

## 5. API Security

### 5.1 ACL resources

```xml
<resources>
    <resource ref="anonymous"/>           <!-- No auth required -->
    <resource ref="customer"/>            <!-- Logged-in customer -->
    <resource ref="Vendor_Module::post"/>  <!-- Admin with permission -->
</resources>
```

| Resource | Who can access |
|----------|---------------|
| `anonymous` | Everyone (no token needed) |
| `customer` | Any logged-in customer |
| `Vendor_Module::post` | Admin users with that ACL permission |

**Source**: `src/vendor/magento/module-backend/etc/acl.xml` — defines ACL resources.

### 5.2 Rate limiting

```xml
<route url="/V1/vendor/module/posts" method="GET">
    <service class="..." method="getList"/>
    <resources>
        <resource ref="anonymous"/>
    </resources>
    <rate limit="100" period="3600"/>  <!-- 100 requests per hour -->
</route>
```

### 5.3 Input validation

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

**Source**: `src/vendor/magento/module-webapi/Controller/Rest.php` — validates API input and enforces ACL.

---

## 6. Testing APIs

### 6.1 REST API with curl

```bash
# GET
curl -H "Authorization: Bearer <token>" \
     https://localhost:8080/rest/V1/vendor/module/posts

# POST
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{"title":"New Post","content":"Hello"}' \
     https://localhost:8080/rest/V1/vendor/module/posts

# DELETE
curl -X DELETE \
     -H "Authorization: Bearer <token>" \
     https://localhost:8080/rest/V1/vendor/module/posts/1
```

### 6.2 GraphQL with curl

```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"query": "{ posts { items { id title } } }"}' \
     https://localhost:8080/graphql
```

### 6.3 REST API with JavaScript

```js
// Using mage/storage
define(['mage/storage', 'mage/translate'], function (storage, $t) {
    'use strict';
    
    function submitReview(reviewData) {
        return storage.post(
            '/rest/V1/vendor/module/reviews',
            JSON.stringify(reviewData),
            false,
            'application/json'
        );
    }
});
```

**Source**: `src/vendor/magento/module-ui/view/base/web/js/lib/storage/base.js` — Magento's storage library.

---

## 7. Common Issues

### 7.1 401 Unauthorized

**Cause**: missing or invalid token.

**Solution**:
```bash
# Get a new token
curl -X POST "https://localhost:8080/rest/V1/integration/admin/token" \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}'
```

### 7.2 403 Forbidden

**Cause**: user doesn't have the required ACL permission.

**Solution**:
```bash
# Check ACL in etc/acl.xml
# Assign permission in admin: System > Permissions > User Roles
# Or use a different ACL resource in webapi.xml
```

### 7.3 Route not found (404)

**Cause**: URL doesn't match any route in `webapi.xml`.

**Solution**:
```bash
# Check webapi.xml
# Verify URL matches exactly (case-sensitive)
# Check HTTP method (GET vs POST)
```

### 7.4 "Class not found" for service

**Cause**: interface not found or method doesn't exist.

**Solution**:
```bash
# Verify interface exists
ls app/code/Vendor/Module/Api/PostRepositoryInterface.php

# Verify method exists
grep "function getList" app/code/Vendor/Module/Api/PostRepositoryInterface.php
```

---

## 8. Summary

| Concept | Purpose | Example |
|---------|---------|---------|
| **REST API** | HTTP + JSON endpoints | `/rest/V1/vendor/module/posts` |
| **GraphQL** | Single endpoint, flexible queries | `/graphql` |
| **webapi.xml** | Route configuration | URL, method, service, ACL |
| **Service Contract** | Business logic interface | `PostRepositoryInterface` |
| **Authentication** | Bearer token (admin/customer) | `Authorization: Bearer <token>` |
| **ACL resource** | Permission check | `anonymous`, `customer`, `Vendor_Module::post` |
| **Data Object** | Property bag for API data | `PostInterface`, `ReviewInterface` |

---

## 9. AlpineCommerce Reference

### 9.1 AlpineCommerce REST endpoints

| Module | Endpoint | Method | ACL |
|--------|----------|--------|-----|
| Blog | `/rest/V1/vendor/module/posts` | GET/POST | `Vendor_Module::post` |
| Faq | `/rest/V1/vendor/module/faqs` | GET/POST | `Vendor_Module::faq` |
| StorePickup | `/rest/V1/carts/mine/store-pickup` | POST | `customer` |
| CustomerCare | `/rest/V1/customercare/vip-status/:customerId` | GET | `AlpineCommerce_CustomerCare::config` |
| CustomerCare | `/rest/V1/customercare/me/vip-status` | GET | `customer` |

### 9.2 AlpineCommerce webapi.xml examples

```xml
<!-- etc/webapi.xml -->
<routes xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <route url="/V1/alphacommerce/product-reviews" method="POST">
        <service class="AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface" method="save"/>
        <resources>
            <resource ref="customer"/>
        </resources>
    </route>
</routes>
```

**Source**: `src/app/code/AlpineCommerce/ProductReviews/etc/webapi.xml`

```xml
<route url="/V1/customercare/vip-status/:customerId" method="GET">
    <service class="AlpineCommerce\CustomerCare\Api\CustomerCareInterface" method="getVipStatus"/>
    <resources>
        <resource ref="AlpineCommerce_CustomerCare::config"/>
    </resources>
</route>
```

**Source**: `src/app/code/AlpineCommerce/CustomerCare/etc/webapi.xml`

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| REST API | [developer.adobe.com/commerce/php/architecture/modules/webapi/rest/](https://developer.adobe.com/commerce/php/architecture/modules/webapi/rest/) |
| GraphQL | [developer.adobe.com/commerce/php/architecture/modules/webapi/graphql/](https://developer.adobe.com/commerce/php/architecture/modules/webapi/graphql/) |
| ACL | [developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/acl/](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/acl/) |
| Service Contracts | [developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under `src/vendor/magento/`.

AlpineCommerce-specific implementations are referenced from `src/app/code/AlpineCommerce/`.

*Last updated: 2026-09-07*
