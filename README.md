<p align="center">
  <img src="docs/assets/qader-logo.svg" width="180" alt="Qader logo">
</p>

<h1 align="center">Qader Backend</h1>

Laravel API for the Qader talent and hiring platform. It serves four consumers:

- `talent`: registration, onboarding, evaluation, interviews, and hiring.
- `client`: workspaces, teams, talent discovery, and hiring requests.
- `admin`: master data, access control, reviews, and platform operations.
- `website`: public pages, published jobs, and external application forms.

## Stack

PHP 8.3, Laravel 13, MySQL 8.4, Redis 7, Sanctum, Scribe, PHPUnit, and Docker Compose.

The codebase follows a modular layered structure under `app/Modules/V1`:

```text
<Module>/
├── Application
├── Domain
├── Infrastructure
└── Presentation
```

## Local setup

Docker is the supported local runtime; PHP and Composer are not required on the host.

```bash
cp .env.example .env
```

Set at least:

```dotenv
APP_NAME=Qader
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=qader
DB_USERNAME=qader
DB_PASSWORD=CHANGE_ME
MYSQL_ROOT_PASSWORD=CHANGE_ME_TOO
```

Build the images, generate the application key, and start the stack:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml build app nginx

docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml run --rm --no-deps app php artisan key:generate

docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml up -d
```

Prepare the database:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan migrate --seed
```

The API is available at `http://localhost:8000`; verify it with:

```bash
curl http://localhost:8000/up
```

Do not run `docker compose down -v` unless deleting the local database and volumes is intentional.

## Demo accounts

| Portal | Email | Password |
| --- | --- | --- |
| Talent | `talent@qader.test` | `Password123!` |
| Client | `client@qader.test` | `Password123!` |
| Admin | `admin@qader.test` | `Password123!` |

Create or update the platform super admin and generate an API key:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan qader:bootstrap-access admin@example.com
```

## API headers

```http
Accept: application/json
Accept-Language: en
X-Authorization: <api-key>
Authorization: Bearer <token>
```

`Authorization` is required only for authenticated endpoints. The API language is controlled only by `Accept-Language` and supports `en`, `ar`, and regional forms such as `ar-EG`.

## API documentation

Regenerate Scribe after changing routes, Form Requests, or examples:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

| Document | URL |
| --- | --- |
| Scribe UI | `http://localhost:8000/docs` |
| Postman | `http://localhost:8000/docs.postman` |
| Talent OpenAPI | `http://localhost:8000/docs/talent.openapi` |
| Client OpenAPI | `http://localhost:8000/docs/client.openapi` |
| Admin OpenAPI | `http://localhost:8000/docs/admin.openapi` |
| Website OpenAPI | `http://localhost:8000/docs/website.openapi` |

Use the four separate OpenAPI URLs as Apidog data sources. See [Scribe and Apidog setup](docs/scribe-local.ar.md).

## Development commands

```bash
# Tests use SQLite in memory, not the local MySQL database.
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan test

# Code style check.
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app ./vendor/bin/pint --test

# Logs.
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml logs -f app queue scheduler nginx
```

## Optional integrations

Environment placeholders are available for:

- Firebase Cloud Messaging: `FIREBASE_CREDENTIALS`
- Google Calendar and Meet: `GOOGLE_CALENDAR_*`
- Gemini resume parsing: `GEMINI_*`
- Email delivery: `MAIL_*`

Service-account files belong in `storage/app/private/` and must not be committed.

## Documentation

- [Project memory and current decisions](docs/project-memory.ar.md)
- [Architecture index](docs/architecture/README.md)
- [API enum reference](docs/api-enums-reference.ar.md)
- [Database schema review](docs/database-schema-review.ar.md)
- [Database diagram](docs/database-schema-diagram.html)
- [Sprint plans and handoffs](docs/sprints/)
- [Deployment guide](README-DEPLOY.md)

Never commit `.env`, API keys, service-account files, or production credentials.
