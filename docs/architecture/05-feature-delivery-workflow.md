# 05 — Feature Delivery Workflow

## 1. Define One Small Flow

For every step, define:

- Who is the user, and which portal do they use?
- What do they submit?
- What result do they receive?
- What are the states and constraints?
- Which tables change?
- Which permission is required?

If the answer needs a long document, the feature is too large and should be split first.

## 2. Match Figma and Product Documents

Figma defines fields and UX, but it does not define database schema by itself. Compare the screen with product documents and the real business flow before adding database columns.

## 3. Choose the Module

- Account and token changes: `Authentication` or `Users`.
- Talent business logic: `Talents`.
- Roles and permissions: `AccessControl`.
- Logic across modules: a clear use case or contract, never random Controller imports.

## 4. Prepare the Database

Create one focused migration, then add the needed relations, casts, and Enums. Do not add fields for a future phase.

## 5. Implement the API

1. Domain enum, contract, or model when needed.
2. Infrastructure repository/provider when needed.
3. Application use case.
4. Presentation Request, Resource, and Controller.
5. Route registration in `config/modules.php` and the correct portal file.
6. Permission catalog and middleware when the endpoint is protected.

## 6. Verify Runtime Behavior

The current Qader agreement is: do not write or run tests unless explicitly requested. At minimum, verify:

```powershell
php -l <changed-file>
php artisan route:list --path=api/v1
php artisan migrate:status
```

When a migration is added, run it only after confirming that it is additive and does not destructively alter existing data.

## 7. Document the Decision

Update the relevant flow document with each meaningful decision: status names, endpoint contract, required permissions, or schema rationale. This prevents inconsistent implementation across work sessions.

## Definition of Done

A feature is not complete until its API route is protected correctly, validation rejects invalid input, its response is stable for the frontend, its migration represents the current need only, its provider/binding is registered when needed, and its flow documentation is updated.
