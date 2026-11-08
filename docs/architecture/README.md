# Qader Engineering Playbook

This folder is the official reference for how Qader is built. Its architectural approach comes from ShelfSpot, while its portal names and business domain are adapted to Qader.

## Goals

- Build every feature as a clear module, not as scattered files.
- Give every portal its own routes, permissions, and API contracts.
- Expand the database incrementally; do not create fields or tables before a real flow needs them.
- Treat the API as the security authority; the frontend uses permissions only for presentation.

## Documents

1. [01 — Architecture and Layers](01-architecture.md)
2. [02 — Module Contract](02-module-contract.md)
3. [03 — API, Portals, and Access Control](03-api-portals-access-control.md)
4. [04 — Database and Migration Rules](04-database-migrations.md)
5. [05 — Feature Delivery Workflow](05-feature-delivery-workflow.md)
6. [06 — First Flow: Talent Registration](06-talent-registration-flow.md)
7. [07 — Delivery Roadmap](07-delivery-roadmap.md)
8. [08 — Currency and Salary Conversion](08-currency-and-salary-conversion.md)
9. [09 — Token-Based Authentication](09-web-and-mobile-auth.md)
10. [10 — Authentication Implementation Record](10-authentication-implementation-record.md)
11. [11 — Talent Personal Information](11-talent-personal-information.md)
12. [12 — Talent Onboarding and Application](12-talent-onboarding-and-application.md)
13. [13 — Reference Data Modules](13-reference-data-modules.md)
14. [Project Memory — Arabic](../project-memory.ar.md)

## Qader Mapping

We do not copy ShelfSpot business logic or database tables into Qader. We reuse its **engineering system** only.

| ShelfSpot | Qader |
| --- | --- |
| `admin` | `admin` |
| `company` | `client` |
| `worker` | `talent` |

## Rule Before Starting

Before implementing a feature, define its user, inputs, result, states, and affected tables in one short document. If it cannot be described simply, split it into smaller flows before implementation.
