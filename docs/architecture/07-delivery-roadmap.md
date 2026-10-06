# 07 — Qader Delivery Roadmap

This roadmap breaks Qader into small deliverable stages. We complete and verify one stage before opening the next one.

## Working Rules

- Build the Talent journey first.
- Keep every task small enough to review and release independently.
- Do not add future database fields before their corresponding flow starts.
- Do not write or run tests unless explicitly requested; use the runtime checks in the engineering playbook.
- Update the relevant flow document whenever a product or schema decision is made.

## Status Legend

- `Planned` — agreed but not started.
- `In progress` — current implementation scope.
- `Done` — implemented and runtime-verified.
- `Deferred` — intentionally postponed until its dependencies are ready.

---

## Stage 0 — Engineering Baseline

**Status:** `Done`

Goal: establish the common development system before building business features.

- [x] Define the modular architecture and layer responsibilities.
- [x] Define the Qader portals: public, admin, client, and talent.
- [x] Document API, access-control, and database conventions.
- [x] Create the Talent registration flow specification.

---

## Stage 1 — Talent Account Registration

**Status:** `In progress`

Goal: allow a new Talent to create and verify an account from the Figma sign-up screen.

### Tasks

- [x] Confirm the public registration contract: first name, last name, email, password, password confirmation.
- [x] Ensure email uniqueness is scoped correctly for the Talent portal.
- [x] Create the `users` record with `type = talent`.
- [x] Create the related empty `talents` record in the same database transaction.
- [x] Set initial onboarding state to step 1.
- [x] Send an email-verification OTP only after the transaction commits.
- [x] Use one channel-agnostic Sanctum access/refresh token contract for web and mobile.
- [x] Issue limited Sanctum verification and reset tokens before the related action is complete.
- [x] Verify email and issue access/refresh tokens.
- [x] Support Talent login, logout, refresh token, and password reset.
- [ ] Support Google sign-in after `GOOGLE_CLIENT_ID` is configured.
- [x] Verify the loaded routes, migration status, and PHP syntax.

### Deliverable

```text
POST /api/v1/auth/talent/register
POST /api/v1/auth/talent/login
PATCH /api/v1/auth/talent/email-verification
POST /api/v1/auth/talent/refresh
DELETE /api/v1/auth/logout
```

### Explicitly Not Included

CV uploads, skills, profile completion, admin review, and marketplace visibility.

### Remaining Acceptance Check

Before marking this stage as `Done`, perform one manual token-based flow with real email delivery. Confirm OTP delivery, email verification, access/refresh renewal, and logout without creating automated tests.

---

## Stage 2 — Talent Basic Profile

**Status:** `In progress`

Goal: allow an authenticated Talent to save the first profile step as part of the application form, without requiring a complete profile.

### Tasks

- [x] Read the exact Personal Information section from Figma before adding fields.
- [x] Define the personal-information input contract and validation rules.
- [x] Confirm the required personal-information schema already exists; add no migration.
- [x] Save phone and WhatsApp numbers.
- [x] Save date of birth and gender.
- [x] Save residence and nationality using country/city lookups.
- [ ] Save current job title, seniority level, and years of experience.
- [x] Expose a Talent profile resource with only safe fields.
- [ ] Advance `onboarding_step` only after the step is valid.
- [x] Add the `talent/profile` route group with Talent token abilities.

### Deliverable

```text
GET /api/v1/talent/profile
PUT /api/v1/talent/profile/personal-information
```

### Explicitly Not Included

Experience history, skills, work preferences, application submit, and admin review.

---

## Stage 3 — Talent Professional Profile

**Status:** `Planned`

Goal: let the Talent build their professional information gradually.

### Tasks

- [ ] Read the Professional Profile screens from Figma.
- [ ] Add and manage skills with proficiency and optional years of experience.
- [ ] Add and manage languages with proficiency.
- [ ] Add and manage tools.
- [ ] Add, edit, reorder, and delete work experiences.
- [ ] Add, edit, reorder, and delete portfolio projects.
- [ ] Save professional links such as LinkedIn, GitHub, Behance, and portfolio URL.
- [ ] Recalculate profile completion details after each update.

### Deliverable

```text
PUT /api/v1/talent/profile/professional
POST|PUT|DELETE /api/v1/talent/profile/experiences
POST|PUT|DELETE /api/v1/talent/profile/projects
```

### Explicitly Not Included

Application review, client search, matching, interviews, and contracts.

---

## Stage 4 — Work Preferences and Application Submission

**Status:** `Planned`

Goal: collect availability and expectations, then allow the Talent to submit an application for review.

### Tasks

- [ ] Read the Work Preferences screen from Figma.
- [ ] Save work type and work mode preferences.
- [ ] Save availability, notice period, and relocation preference.
- [ ] Save current and expected salary with currency.
- [ ] Add a separate `talent_applications` table when this stage starts.
- [ ] Allow submitting the current profile even when profile completion is below 100%.
- [ ] Create one application record with `submitted` status atomically.
- [ ] Record the profile completion percentage and submission timestamp on the application.
- [ ] Keep the profile editable during review unless a later product decision requires locking specific fields.

### Deliverable

```text
PUT /api/v1/talent/profile/preferences
POST /api/v1/talent/application/submit
GET /api/v1/talent/application/status
```

### Explicitly Not Included

Admin review actions, client search, applications to jobs, and contracts.

---

## Stage 5 — Admin Talent Review

**Status:** `Deferred`

Goal: allow authorized admins to review submitted Talent applications.

### Tasks

- [ ] Define review statuses and allowed transitions.
- [ ] Add permissions for viewing and reviewing Talent applications.
- [ ] List submitted applications with filters and pagination.
- [ ] Show the available Talent profile data and profile-completion percentage to authorized admins.
- [ ] Approve or reject an application with an optional or required reason.
- [ ] Notify the Talent about the decision.
- [ ] Keep an audit history of review decisions.

### Deliverable

```text
GET /api/v1/admin/talent-applications
GET /api/v1/admin/talent-applications/{talent}
POST /api/v1/admin/talent-applications/{talent}/approve
POST /api/v1/admin/talent-applications/{talent}/reject
```

---

## Stage 6 — Client and Talent Marketplace

**Status:** `Deferred`

Goal: let approved clients discover approved Talents and begin hiring workflows.

### Tasks

- [ ] Define the Client account and approval flow.
- [ ] Define what Talent data is visible publicly to clients.
- [ ] Build Talent search, filters, and profile cards.
- [ ] Add shortlist or hiring-request behavior only after its product flow is approved.
- [ ] Design matching, interviews, contracts, and notification flows as separate stages.

This stage must not begin until Stages 1–5 are stable and the client-side Figma flows are reviewed.

---

## Current Next Task

Start **Stage 1 — Talent Account Registration** only. The first implementation ticket is:

> Align the Talent registration API with the Figma sign-up screen and create the `users` and `talents` records atomically.
