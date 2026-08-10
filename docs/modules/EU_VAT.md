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
| **REST API** | Exposed validation |
| **Admin configuration** | Activation and setup |
| **i18n** | French translation |

## 3. Architecture

```
AlpineCommerce/EuVat/
├── Api/                        # Service Contracts
├── Console/                    # alphacommerce:euvat:validate command
├── Model/                      # VIES SOAP client + validation logic
├── Controller/                 # REST
└── etc/
    ├── system.xml              # admin configuration
    └── (webapi.xml)            # REST API
```

## 4. Database

No dedicated table (real-time validation via VIES).

## 5. REST API

REST route for VAT number validation (exposed Service Contract).

## 6. Admin

- System configuration (activation, VIES parameters)

## 7. Frontend

No dedicated frontend.

## 8. CLI

| Command | Role |
|---|---|
| `alphacommerce:euvat:validate` | Validate a VAT number (VIES) |

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| VIES via SOAP | Official European Commission service for intra-community validation |
| CLI + REST | Two call modes (ops and integration) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Complete admin interface to finalize | 📋 v1.1 — `ROADMAP.md` |

## 11. Magento concepts taught

- **Console** commands (`bin/magento`)
- External **SOAP** client (wsdl)
- System configuration (`system.xml`)

## 12. Validation & status

- **Status**: 🔄 In finalization — global validation OK (Sprint 6), admin finalization planned

---

*Sources: `docs/08_CHANGELOG.md` (v1.0.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
