# 03 — API, Portals, and Access Control

## Qader Portals

| Portal | Purpose | Route folder |
| --- | --- | --- |
| `public` | registration, login, OTP, and public enums | `routes/V1/public` |
| `admin` | platform management, access control, and reviews | `routes/V1/admin` |
| `client` | client account, hiring requests, and team management | `routes/V1/client` |
| `talent` | Talent profile, opportunities, and applications | `routes/V1/talent` |

## Route Loading

`config/modules.php` is the registry for routes and providers. `AppServiceProvider` loads each registered route file under:

```text
/api/v1/<prefix>
```

Example route group registration:

```php
'talent' => [
    [
        'prefix' => 'talent/profile',
        'file' => 'profile.php',
        'middlewares' => ['auth:sanctum', 'abilities:access,talent'],
    ],
],
```

Registered routes pass through `api`, `locale`, and `api.key`. Every current API request must include the active key in the `X-Authorization` header. Protected portal endpoints additionally require the appropriate Sanctum token and abilities. See [09 — Token-Based Authentication](09-web-and-mobile-auth.md).

## Authentication

Sanctum tokens carry abilities for token type and portal:

- access token: access to a protected portal endpoint
- refresh token: session refresh only
- verification/reset token: one limited flow only

Every protected endpoint declares required token abilities explicitly. Do not rely on `user.type` inside a Controller instead of middleware unless a business rule specifically needs it.

## Access Control

Qader uses Roles and Permissions through Spatie Permission.

- A permission is a stable machine-readable name, such as `talent-applications.view`.
- A label is for display only; it must not be used for frontend or backend authorization.
- Protect a route through `permission:<permission-name>` middleware.
- The frontend may show or hide actions from permissions, but the backend remains the final authority.

The initial admin uses a protected `super-admin` role. The API must not allow this role to be deleted or stripped of permissions without an explicit system operation.

## Response Shape

Use the project's `ApiResponse` facade for standard responses:

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

- Use `201` for creation.
- Use `200` for successful reads and updates.
- Do not return `204` with a body; use a consistent `200` message if a response body is needed.
- Use `401` for invalid or missing identity, `403` for a valid identity without permission, and `422` for validation errors.

## Versioning

The current API version is `V1`. Do not create `V2` for an internal refactor. Create a new version only when a public API contract changes in a way that breaks current clients.
