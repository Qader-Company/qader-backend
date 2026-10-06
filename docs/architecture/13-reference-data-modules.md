# Reference data modules

Reference data is owned by seven independent modules: `Countries`, `Cities`,
`Languages`, `JobTitles`, `Skills`, `Tools`, and `Currencies`. Each module owns
its model, repository contract and implementation, use cases, validation,
controllers, routes, provider, and seed data.

Only reusable technical CRUD building blocks live under `Shared/ReferenceData`.
They do not select a model or table and expose no routes.

## Endpoints

Public active-data endpoints use `/api/v1/{module}`. Admin CRUD and status
endpoints use `/api/v1/admin/{module}`.

Skills, tools, and job titles can be filtered by `category`; seed data includes
Software Development, Marketing, Sales, and Customer Service.

Every module has independent `{module}.view` and `{module}.manage` permissions.
Records are deactivated instead of deleted to preserve historical references.

## Seed data

`DatabaseSeeder` calls the seven independent seeders. Every seeder uses upsert
and can be rerun safely with:

```sh
php artisan db:seed
```
