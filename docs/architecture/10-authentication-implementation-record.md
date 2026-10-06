# 10 — Authentication Implementation Record

**Updated:** 2026-08-25  
**Scope:** the authentication foundation before Talent profile work begins.

## What Is Implemented

### Shared Account Provisioning

- One portal-aware registration workflow.
- `PortalRegistrationRequestFactory` selects the validation request for `talent` or `client`.
- `PortalRegistrationFactory` selects the matching registration handler.
- `CreateTalentAccountUseCase` creates the `users` and initial `talents` records.
- `CreateClientAccountUseCase` creates the `users` and initial `clients` records.
- Account/profile creation is transactional. The email OTP is queued only after that transaction succeeds.
- Portal profile models are owned by their modules: `Talents/Domain/Models/Talent`, `Clients/Domain/Models/Client`, and `Admins/Domain/Models/Admin`.

### Authentication Contract

- One channel-agnostic Sanctum bearer-token contract serves web and mobile clients.
- Registration and unverified login return a limited verification token.
- Email verification, login, and Google login return an access/refresh token pair.
- Password-reset verification returns a limited reset token.
- Logout revokes the token used for the request.

### Endpoints

The public authentication surface is `routes/V1/public/auth.php`. It supports registration, login, Google sign-in, OTP delivery, email verification, password reset, refresh, and logout for all clients.

### Security Rules Applied

- Every authenticated endpoint uses `auth:sanctum` and requires the correct ability (`verification`, `refresh`, or `reset_password`) where applicable.
- CSRF cookies and Laravel session authentication are intentionally not part of the public API authentication flow.
- Google sign-in validates the configured server client ID as the ID-token audience and fails closed when it is missing.
- Public authentication does not accept client-distributed API keys as a security control.
- Internal routes still receive `api.key` by default through `AppServiceProvider`.
- Access-control routes require the existing `access` ability, not the obsolete `access_token` value.

## Legacy Code Removed

The following ShelfSpot-oriented or superseded files were removed because no active route or service uses them:

- the former combined `routes/V1/public/auth.php` route contract;
- its old `AuthController`, `EmailVerificationController`, and `ResetPasswordController`;
- `WorkerSocialRegistrar`, which depended on a non-existent Qader `Workers` module.

The active implementation uses `TokenAuthController`, the token authentication operations, and `PortalSocialRegistrar` instead.

## Required Deployment Configuration

Before manual acceptance, set real values for:

- `MAIL_*` so OTP emails are delivered;
- `GOOGLE_CLIENT_ID` for Google ID-token verification;
- `APP_URL` and `CORS_ALLOWED_ORIGINS` for the actual client domains.

For production, use HTTPS and an exact CORS origin allowlist. Full token-handling rules are in [09 — Token-Based Authentication](09-web-and-mobile-auth.md).

## Local OTP Delivery

During local development, Qader uses Laravel's log mailer instead of an SMTP provider:

```dotenv
MAIL_MAILER=log
QUEUE_CONNECTION=database
```

OTP mails are queued. Run a worker while manually checking registration or password reset:

```bash
php artisan queue:work
```

Then read the one-time code from `storage/logs/laravel.log`. Do not expose the generated code in an API response. Replace the log mailer with a real provider before production acceptance.

## Verification Performed

- PHP syntax checks for the changed Auth classes and configuration.
- Route registration checks for the unified `auth` contract.
- Configuration checks for Sanctum token authentication and CORS settings.
- No automated tests were written or run, by project agreement.

## Deliberately Deferred

- Manual cross-client token-flow checks with real mail and Google credentials.
- Talent Basic Profile fields and tables.
- Talent application submission and admin review.
- Currency conversion and salary preferences.
