# AlpineCommerce_EuVat Module — European VAT Validation

> **Status**: 🔄 In finalization (v1.0.0)

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
├── Model/
│   ├── ResourceModel/
│   │   ├── VatValidation.php
│   │   └── VatValidation/Collection.php
│   ├── SoapClientFactory.php            # VIES SOAP client factory
│   ├── VatValidation.php                # model
│   ├── VatValidationRepository.php      # repository implementation
│   └── VatValidationService.php         # business logic
├── etc/
│   ├── acl.xml                          # config ACL
│   ├── config.xml                       # defaults
│   ├── db_schema.xml                    # alphacommerce_euvat_validation
│   ├── di.xml                           # preferences + SOAP client
│   ├── module.xml
│   ├── system.xml                       # admin config
│   └── webapi.xml                       # REST routes
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

- **Stores > Configuration > General > EU VAT**: enable flag, VIES WSDL URL, request timeout (per store view)

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
| — | Complete admin interface to finalize | 📋 v1.1 — `ROADMAP.md` |

## 11. Magento concepts taught

- **Console** commands (`bin/magento`)
- External **SOAP** client (wsdl)
- System configuration (`system.xml`)
- Service contracts + Repository pattern
- REST API with anonymous access

## 12. Validation & status

- **Status**: 🔄 In finalization — global validation OK (Sprint 6), admin finalization planned

---

*Sources: `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
