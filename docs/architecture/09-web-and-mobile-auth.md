# 09 — Token-Based Authentication

## Decision

Qader uses one channel-agnostic authentication contract for web and mobile clients. Every client receives and sends Sanctum bearer tokens; no authentication state is stored in browser cookies, and Sanctum does not fall back to the Laravel `web` guard.

```text
/api/v1/auth/{portal}/...
```

The portal remains explicit in the URL. The API does not infer it from the client device or user agent.

## Core Routes

```text
POST   /api/v1/auth/talent/register
POST   /api/v1/auth/talent/login
PATCH  /api/v1/auth/talent/email-verification
POST   /api/v1/auth/talent/refresh
POST   /api/v1/auth/talent/social/google/login
GET    /api/v1/auth/me
DELETE /api/v1/auth/logout
```

The same routes exist for `client`. `admin` is never publicly registered.

## Token Flow

1. Registration, or an unverified login, returns a short-lived `verification_token`.
2. The client sends that token as `Authorization: Bearer <token>` when verifying the email OTP.
3. Successful verification, password login, and Google login return `access_token` and `refresh_token`.
4. Protected API calls send the access token as a bearer token.
5. The refresh endpoint accepts a refresh token only and returns a new access/refresh pair.
6. Password and social login may include `fcm_token` and `platform`; a successful login upserts the matching `user_devices` record.
7. Access and refresh tokens issued together share one `token_family_id`.
8. Logout revokes the complete token family and removes its device registration when no other token family uses it.

Password reset uses the same pattern: OTP verification returns a limited `reset_password_token`, which is required to submit the new password.

## Client Responsibilities

- No client calls `/sanctum/csrf-cookie`.
- No client sends `Origin`, `X-XSRF-TOKEN`, or session cookies as part of this authentication design.
- Web clients keep tokens out of persistent browser storage where possible. Prefer in-memory state and a deliberate renewal strategy.
- Native clients store tokens only in Keychain or Keystore.
- Never log tokens, place them in URLs, or include them in crash reports.
- Send `fcm_token` only with login. If notification permission is denied, omit both `fcm_token` and `platform`; login still succeeds.

## Google Provider Contract

Clients send a Google **ID token**, not a Google access token. Qader verifies its signature, issuer, verified email, and audience against `GOOGLE_CLIENT_ID` before creating or linking a `social_accounts` record.

Public authentication routes require an active API key in `X-Authorization` in addition to rate limits, validation, OTP controls, verified provider tokens, and the user token lifecycle.
