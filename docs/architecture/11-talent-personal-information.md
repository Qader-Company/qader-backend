# 11 — Talent Personal Information

## Purpose

This is the first editable section after a Talent verifies their account. It saves personal contact and residence details independently of professional details, work preferences, and application submission.

The source design is the Talent application screen in Figma (`671:9328`).

## Scope of This Task

The API will expose the registered identity in the profile response and allow the Talent to save the following personal details gradually:

| Field | Storage | Notes |
| --- | --- | --- |
| Full name | `users.first_name`, `users.last_name` | Displayed from the verified account. Name editing is outside this first task. |
| Email | `users.email` | Displayed from the verified account. Email changes require a separate verified-email flow. |
| Phone number | `talents.phone_country_code`, `talents.phone_number` | Store country code and local number separately. |
| WhatsApp number | `talents.whatsapp_country_code`, `talents.whatsapp_number` | Store country code and local number separately. |
| Nationality | `talents.nationality_country_id` | Active country lookup. |
| Gender | `talents.gender` | Nullable string cast to the existing `Gender` backed enum. |
| Date of birth | `talents.date_of_birth` | ISO date. |
| Residence country | `talents.residence_country_id` | Active country lookup. |
| Residence city | `talents.residence_city_id` | Must be an active city belonging to the selected residence country. |

All of these fields already exist in the current Talent schema. No migration is needed for this core personal-information task.

## Fixed-Choice Storage Rule

For a closed product-controlled list, Qader stores the enum's backed value in a string column and casts that column to the corresponding PHP backed enum in the domain model. It does not create a lookup table for such values.

Examples include `gender`, and `religion` if it is approved. The API accepts the enum's string value and returns the same value.

`countries` and `cities` remain reference data rather than enums: their values can change, cities depend on the selected country, and they are already modeled as active relational records. The API therefore continues to store their IDs and validates that the selected city belongs to the selected country.

## Religion Field Decision

The Figma screen also includes a `Religion` dropdown. Religion is sensitive personal data and the design does not define its allowed values or the product reason for collecting it.

It is intentionally excluded from the first API contract until the product decision is confirmed. If approved later, it must be an explicit optional string column cast to a `Religion` backed enum, with documented allowed values and access rules; it must not be inferred or collected silently.

## API Contract

```text
GET /api/v1/talent/profile
PUT /api/v1/talent/profile/onboarding/personal-information
```

Both routes require a Talent access token:

```http
Authorization: Bearer <access-token>
```

`PUT` is a partial-save operation. Every field is optional at this stage, but a supplied field must be valid. This preserves the agreed rule that a Talent may complete the application progressively. The response includes the full profile onboarding state, including the current step and missing fields.

Example request:

```json
{
  "phone_country_code": "+20",
  "phone_number": "1000000000",
  "whatsapp_country_code": "+20",
  "whatsapp_number": "1000000000",
  "nationality_country_id": 1,
  "gender": "male",
  "date_of_birth": "1998-05-12",
  "residence_country_id": 1,
  "residence_city_id": 3
}
```

The response returns the Talent profile with safe identity, contact, and lookup data. It does not issue or rotate authentication tokens.

## Implementation Shape

The Talent module owns the flow:

```text
Talents/
  Application/UseCases/UpdateTalentPersonalInformationUseCase.php
  Domain/Repositories/TalentRepositoryInterface.php
  Infrastructure/Persistence/Repositories/EloquentTalentRepository.php
  Infrastructure/Providers/TalentModuleServiceProvider.php
  Presentation/Http/Requests/UpdateTalentPersonalInformationRequest.php
  Presentation/Http/Controllers/TalentProfileController.php
  Presentation/Http/Resources/TalentProfileResource.php
```

The Controller delegates only. The Use Case validates the authenticated Talent ownership, ensures city/country consistency, updates the record atomically, and advances onboarding only when its agreed completion rule is met.

## Explicitly Deferred

- Professional summary and languages shown elsewhere in the Figma flow.
- Job title, seniority, experience, skills, projects, and work history.
- Salary, currency, availability, and work preferences.
- Application submission and admin review.
