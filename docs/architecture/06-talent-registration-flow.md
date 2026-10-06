# 06 — Flow: Talent Registration

This is the first Talent flow to implement. It does not include profile completion, Talent approval, or the marketplace.

## Reference

The Figma `sign up as talent` screen contains only:

- First Name
- Last Name
- Email
- Password
- Confirm Password
- A provider option such as Google

The user then moves to a separate Talent application form. That form saves data into the Talent profile gradually. The Talent may submit the application even when the profile is not fully complete; the submission creates an application record that is reviewed by an admin later.

## Phase One Scope

### Input

```json
{
  "first_name": "...",
  "last_name": "...",
  "email": "...",
  "password": "...",
  "password_confirmation": "..."
}
```

### Result

1. Create a `users` record with type `talent`.
2. Create the related `talents` record in the same transaction.
3. Send an email-verification OTP.
4. Issue a short-lived verification token.
5. Start the Talent at the first onboarding step.

### Registration Architecture

The public route is shared with other registrable portals:

```text
POST /api/v1/auth/{type}/register
```

For `type=talent`, `PortalRegistrationRequestFactory` resolves `RegisterTalentRequest`, and `PortalRegistrationFactory` resolves `TalentRegistrationHandler`. The handler calls `CreateTalentAccountUseCase` in the `Talents` module. That use case creates the `users` and `talents` records; the shared `RegisterUseCase` only coordinates the transaction, OTP, and verification token.

Google registration uses the same `PortalRegistrationFactory`, so provider registration and password registration always provision the same Talent profile structure.

### Out of Scope Now

- CVs and attachments.
- Skills, tools, and languages.
- Work experience and projects.
- Salary, availability, and work preferences.
- Application submit/review/approval.
- Talent availability in the marketplace.

## Future Talent Journey

| Phase | Data | Expected existing tables |
| --- | --- | --- |
| Account | name, email, password | `users`, `talents` |
| Basic profile | phone, date of birth, gender, country/city, job title, seniority | `talents`, lookup tables |
| Professional profile | skills, languages, tools, experience, projects | pivot/detail tables |
| Preferences | work type/mode, availability, notice period, salary | `talents`, work-preference tables |
| Application submission | submit the current, possibly incomplete profile for review | `talent_applications` |
| Review | admin decision, rejection reason, and notes | dedicated module/records when the admin flow starts |

## State Rules

- Registration does not create an application.
- Email verification does not mean the profile is complete.
- Profile completion does not block application submission unless a future product rule explicitly requires it.
- Submitting creates a separate Talent application for admin review.
- Approval is a separate decision and does not follow automatically from profile completion.

## Proposed Endpoint

```text
POST /api/v1/auth/talent/register
```

The account/profile creation workflow is identical for every client. Registration returns a limited verification token until the email OTP is verified. See [09 — Token-Based Authentication](09-web-and-mobile-auth.md).

Do not add profile endpoints in the same task. When phase two begins, document it as a separate `Talent Basic Profile` flow first.

## Next Decision

After registration is stable, open a new `Talent Basic Profile` flow and take its fields from the relevant Figma screen before changing schema.
