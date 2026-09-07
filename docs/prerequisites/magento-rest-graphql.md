# Magento REST API & GraphQL

Magento 2 provides **REST API** and **GraphQL** for headless commerce and integrations.

## REST API

REST endpoints follow the pattern:

```
GET    /V1/<entity>              # List
GET    /V1/<entity>/:id          # Get by ID
POST   /V1/<entity>              # Create
PUT    /V1/<entity>/:id          # Update
DELETE /V1/<entity>/:id          # Delete
```

### Authentication

- **Integration tokens**: for server-to-server
- **Customer tokens**: for logged-in customers
- **Guest access**: limited to public endpoints

### Example

```bash
curl -X GET "https://magento.local/rest/V1/orders/1" \
  -H "Authorization: Bearer <token>"
```

## GraphQL

GraphQL provides flexible queries:

```graphql
query {
  products(filter: { name: { like: "%jacket%" } }) {
    items {
      name
      price {
        regularPrice {
          amount {
            value
            currency
          }
        }
      }
    }
  }
}
```

## Service Contracts

Both REST and GraphQL use **service contracts** (interfaces in `Api/`):

```
Controller/GraphQL/Query
    ↓
Service Contract (interface)
    ↓
Repository/Model (implementation)
    ↓
ResourceModel (database)
```

## Where to go next

- **Full reference**: [`../magento2/magento-rest-graphql.md`](../magento2/magento-rest-graphql.md)
- **Order lifecycle**: [`../magento2/magento-order-lifecycle.md`](../magento2/magento-order-lifecycle.md)

---

*Prerequisite for: headless development, integrations, mobile apps*
