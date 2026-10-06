# 04 — Database and Migration Rules

## Principle

The database grows from real flows, not from every possible product idea. Start with the smallest schema that supports the current phase, then add a focused migration when the next phase requires it.

## Migration Rules

- Do not edit a migration that has run in a shared environment; add a new migration.
- Name each migration clearly, for example `add_application_status_to_talents_table`, not `update_talents`.
- Every foreign key states its behavior: use `cascadeOnDelete` for fully owned data, and `restrictOnDelete` or `nullOnDelete` for reference data when appropriate.
- Every meaningful business state has a dedicated field and a clear Enum; do not hide state in a generic boolean.
- Add indexes for repeated lookups and filters, not for every column.
- Store money as `decimal` together with currency, never as float.
- Store dates with a clear timezone policy; UTC is preferred at application level.

## Table Categories in Qader

| Type | Examples | Rule |
| --- | --- | --- |
| Identity | `users` | Shared account data only: email, password, type, verification, and active state. |
| Profile | `talents`, `clients`, `admins` | Data specific to the user type. Never duplicate credentials here. |
| Lookup | `countries`, `cities`, `skills`, `languages`, `currencies` | Reference values that can be selected and enabled/disabled. |
| Detail/child | `talent_experiences`, `talent_projects` | Rows owned by one profile and optionally ordered. |
| Pivot | `talent_skills`, `talent_languages`, `talent_tools` | Many-to-many relationships with extra attributes when needed. |
| Lifecycle/audit | future status histories and submissions | Track events and transitions when auditability matters. |

## Talent Profile and Application States

Do not mix account state, profile progress, and application review state:

- `users.is_active`: can the account sign in?
- `users.email_verified_at`: is the email verified?
- `talents.onboarding_step`: where is the user in profile completion?
- `talents.profile_completion_percentage`: how much profile data is currently available?
- `talent_applications.status`: is an application `submitted`, `under_review`, `approved`, `rejected`, or withdrawn?

The Talent profile is allowed to be incomplete when an application is submitted. An application is a separate record that references the Talent profile and enters the admin-review lifecycle. Never use `is_active` to mean "approved".

## Transactions

Any workflow that changes more than one required aggregate runs in a transaction. Registration, for example:

1. Create the user.
2. Create the related Talent profile.
3. Create any required internal record or token.

External side effects such as email and notifications run only after the transaction has committed.

## Seed Data

Do not add lookup data manually through the UI in every environment. Use idempotent seeders for core countries, currencies, and permissions. A seeder must be safe to run more than once without creating duplicates.
