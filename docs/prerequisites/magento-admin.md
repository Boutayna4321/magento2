# Magento Admin Panel

The Magento admin panel is where merchants manage the store. Understanding its
structure is essential for module development.

## Navigation

| Section | Purpose |
|---|---|
| **Dashboard** | Sales overview, charts, recent orders |
| **Sales** | Orders, invoices, shipments, credit memos, transactions |
| **Customers** | Customer list, segments, groups |
| **Catalog** | Products, categories, attributes, inventory |
| **Marketing** | Promotions, cart rules, email templates |
| **Content** | Pages, blocks, widgets, CMS |
| **Stores** | Configuration, settings, stores, currencies |
| **System** | Cache, tools, permissions, integrations |

## Key admin concepts

### ACL (Access Control List)

Controls what each admin user can see/do:

```xml
<acl>
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="Vendor_Module::main" title="My Module">
                <resource id="Vendor_Module::config" title="Configuration"/>
            </resource>
        </resource>
    </resources>
</acl>
```

### System Configuration

Admin-configurable settings defined in `system.xml`:

```xml
<system>
    <section id="vendor_module">
        <group id="general">
            <field id="enabled" type="select">
                <label>Enable Module</label>
            </field>
        </group>
    </section>
</system>
```

### UI Components

Admin grids and forms use UI Components (XML-based):

```xml
<listing xmlns:xsi="...">
    <columns>
        <column name="entity_id">
            <label>ID</label>
        </column>
    </columns>
</listing>
```

## Where to go next

- **Full reference**: [`../magento2/magento-admin.md`](../magento2/magento-admin.md)
- **Engineering guide**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: admin module development, configuration management*
