# AlpineCommerce_EuVat Module — European VAT Validation

> **Status**: ✅ Stable (v1.7.0)

## 1. Responsibility

**European VAT number validation** via the **VIES** service (VAT Information
Exchange System, SOAP request), with CLI command, REST API, and admin configuration.

## 2. Scope & features

| Feature | Description |
|---|---|
| **VIES validation** | VIES service integration via SOAP |
| **CLI** | `alphacommerce:euvat:validate` command |
| **REST API** | POST/GET validation endpoints |
| **Admin configuration** | Activation, WSDL URL, timeout |
| **Admin UI** | Validation history grid, manual validation form |
| **Validation history** | Stores results in `alphacommerce_euvat_validation` |
| **i18n** | French translation |

## 3. Architecture

```
AlpineCommerce/EuVat/
├── Api/
│   ├── Data/
│   │   ├── VatValidationInterface.php
│   │   └── VatValidationSearchResultsInterface.php
│   ├── VatValidationInterface.php
│   └── VatValidationRepositoryInterface.php
├── Console/
│   └── Command/
│       └── ValidateVatCommand.php       # alphacommerce:euvat:validate
├── Controller/
│   └── Adminhtml/
│       └── Validation/
│           ├── Index.php                # Validation history listing
│           └── Validate.php             # Manual VAT validation form
├── Model/
│   ├── ResourceModel/
│   │   ├── VatValidation.php
│   │   └── VatValidation/Collection.php
│   ├── SoapClientFactory.php            # VIES SOAP client factory
│   ├── VatValidation.php                # model
│   ├── VatValidationRepository.php      # repository implementation
│   └── VatValidationService.php         # business logic
├── Ui/
│   ├── Component/
│   │   └── Listing/
│   │       └── Column/
│   │           └── ValidationActions.php # View action column
│   └── DataProvider/
│       └── ValidationDataProvider.php    # Listing data provider
├── etc/
│   ├── acl.xml                          # config, validation, validation_history, validation_validate
│   ├── adminhtml/
│   │   ├── menu.xml                     # Sales → EU VAT Validation
│   │   └── routes.xml                   # frontName: euvat
│   ├── config.xml                       # defaults
│   ├── db_schema.xml                    # alphacommerce_euvat_validation
│   ├── di.xml                           # preferences + SOAP client
│   ├── module.xml
│   ├── system.xml                       # admin config
│   └── webapi.xml                       # REST routes
├── view/
│   └── adminhtml/
│       ├── layout/
│       │   ├── euvat_validation_index.xml
│       │   └── euvat_validation_validate.xml
│       ├── templates/
│       │   └── validation/
│       │       └── validate.phtml       # Manual validation form
│       └── ui_component/
│           └── euvat_validation_listing.xml
└── registration.php
```

## 4. Database

| Table | Role |
|---|---|
| `alphacommerce_euvat_validation` | Validation results (country_id, vat_number, is_valid, name, address, request_date, created_at) |

Index: `country_id + vat_number` (unique lookup)

## 5. REST API

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/V1/alphacommerce/euvat/validate` | anonymous | Validate VAT number |
| GET | `/V1/alphacommerce/euvat/validate/:countryId/:vatNumber` | anonymous | Get validation by country/number |

## 6. Admin

- **Menu**: Sales → EU VAT Validation (History, Validate VAT)
- **Route**: `/admin/euvat/validation` (frontName: `euvat`)
- **Validation History Grid**: lists country_id, vat_number, is_valid, name, request_date, created_at
- **Manual Validation Form**: country selection, VAT number input, VIES validation
- **Stores > Configuration > General > EU VAT**: enable flag, VIES WSDL URL, request timeout (per store view)
- **ACL Resources**: `AlpineCommerce_EuVat::config`, `AlpineCommerce_EuVat::validation`, `AlpineCommerce_EuVat::validation_history`, `AlpineCommerce_EuVat::validation_validate`

## 7. Frontend

No dedicated frontend.

## 8. CLI

| Command | Role |
|---|---|
| `alphacommerce:euvat:validate` | Validate a VAT number (VIES SOAP) |

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| VIES via SOAP | Official European Commission service for intra-community validation |
| Anonymous REST | VAT validation is a public utility, no authentication required |
| Repository pattern | `VatValidationRepository` persists results for audit/history |
| `SoapClientFactory` | Factory for VIES SOAP client (testable, configurable) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Admin interface | ✅ Done — validation history grid + manual validation form |

## 11. Magento concepts taught

- **Console** commands (`bin/magento`)
- External **SOAP** client (wsdl)
- System configuration (`system.xml`)
- Service contracts + Repository pattern
- REST API with anonymous access
- **Admin UI Components** (listing, dataSource, columns, actions)
- **Admin menu & routes** (`adminhtml/menu.xml`, `adminhtml/routes.xml`)
- **ACL resources** (hierarchical permissions)
- **Data persistence forms** (manual validation)

## 12. Validation & status

- **Status**: ✅ Stable — admin UI completed (v1.7.0)

---

*Sources: `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
