# 12 — Talent Onboarding and Application Creation

## Contract

The Talent profile is one progressive onboarding flow with three required sections:

| Step | Route key | Required | Purpose |
| --- | --- | --- | --- |
| Personal information | `personal-information` | Yes | Identity-adjacent contact, nationality, and residence data. |
| Skills and experiences | `skills-and-experiences` | Yes | Professional identity, languages, skills, tools, work history, portfolio links, and projects. |
| Work preferences | `work-preferences` | Yes | Work type/mode, availability, and salary expectations. |

The exact field labels remain owned by the Figma screens; route keys are stable API identifiers.

## Endpoints

```text
GET   /api/v1/talent/profile
PUT   /api/v1/talent/profile/onboarding/{step}
PATCH /api/v1/talent/profile
GET   /api/v1/talent/profile/application
```

`GET /profile` is the frontend's entry point after login. It returns the profile split by section, each section's completion status and missing fields, the current required step, whether an application can exist, and the current application summary.

`PUT /onboarding/{step}` accepts only fields belonging to that named step. A step may be saved partially. The server recalculates completion from persisted data after every save.

`PATCH /profile` accepts any combination of the three sections in one atomic update. It is for later profile editing; it does not create an application.

## Application Creation

There is intentionally no application-submission endpoint. Saving `work-preferences` creates the application inside the same transaction only when all three required steps are complete. Creation is idempotent: repeated saves do not create duplicate applications.

An application stores a snapshot of the profile at the time it is created. Later profile changes do not silently rewrite the submitted snapshot reviewed by admins.

## Module Ownership

`TalentApplications` owns the application model, repository, creation use case, read use case, and response resource. `Talents` owns profile completion only. When the third onboarding step is complete, `SaveTalentOnboardingStepUseCase` calls `CreateTalentApplicationUseCase`; it does not create or query application records directly.
