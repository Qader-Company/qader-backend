# Qader Backend Docker & GitHub Actions

## Prerequisites
- Ubuntu VPS with Docker Engine and Docker Compose plugin
- SSH `deploy` user in docker group
- Docker registry pull access to the organization's GHCR images (if private)
- Server-to-GitHub read-only deploy key for cloning repo
- GitHub Actions-to-server SSH private key saved in environment secrets
- DNS and reverse proxy + TLS configured independently

## Repo files
Copy this folder's files into the repository root, preserving paths. Commit to a branch, test locally, then merge.

## Local (Docker)
Create `.env` from `.env.docker.example`; choose unique passwords and generate APP_KEY using the running app container (`docker compose ... run --rm app php artisan key:generate --show`). Put APP_KEY in `.env`.

```sh
cp .env.docker.example .env
# Edit APP_ENV=local APP_DEBUG=true APP_URL=http://localhost:8000 and passwords
# Generate a key locally if PHP is installed: php artisan key:generate --show
# If PHP is not installed, first build app and generate key from its container:
docker compose -p qader-local -f compose.yaml -f compose.local.yaml build app nginx
# For first build, artisan package discovery should be safe without .env key
# Print key and put into .env:
docker compose -p qader-local -f compose.yaml -f compose.local.yaml run --rm app php artisan key:generate --show

docker compose -p qader-local -f compose.yaml -f compose.local.yaml up -d
# New database needs migrations:
docker compose -p qader-local -f compose.yaml -f compose.local.yaml exec -T app php artisan migrate --force
curl -i http://localhost:8000/up
```

## Server layout
```text
/opt/qader/testing/{.env,compose.yaml,compose.testing.yaml, scripts/deploy.sh,...}
/opt/qader/production/{.env,compose.yaml,compose.production.yaml,scripts/deploy.sh,...}
```
Each environment is its own git clone and Docker Compose project. `.env` is created on VPS, never committed.

On zsh, commands run as normal: Docker Compose, `git`, and `bash scripts/deploy.sh` do not require changing the login shell. Use Bash to execute deploy.sh explicitly.

```sh
sudo apt update
sudo apt install -y ca-certificates curl git openssh-client
# Install Docker Engine and Compose plugin from Docker's official Ubuntu repository.
sudo mkdir -p /opt/qader
sudo chown -R deploy:deploy /opt/qader
sudo -iu deploy
mkdir -p /opt/qader/{testing,production}
git clone -b develop git@github.com:Qader-Company/qader-backend.git /opt/qader/testing
git clone -b main git@github.com:Qader-Company/qader-backend.git /opt/qader/production
# Put respective .env.docker.example contents into .env then replace secrets and APP_URL
# For each environment, initial manual deploy uses the git commit SHA:
cd /opt/qader/testing
bash scripts/deploy.sh testing "$(git rev-parse HEAD)"
```

## Reverse proxy
Set each subdomain's reverse proxy to 127.0.0.1:9001 for testing or 127.0.0.1:9101 for production. Enable HTTPS at the proxy. Keep MySQL and Redis private.

## GitHub Secrets (per Environment)
- testing: TESTING_HOST, TESTING_SSH_KEY, TESTING_KNOWN_HOSTS
- production: PRODUCTION_HOST, PRODUCTION_SSH_KEY, PRODUCTION_KNOWN_HOSTS

GitHub Actions automatically pushes image on branch pushes after tests, then deploys SHA-tagged images via SSH.

## Security / caveats
- Never run `docker compose down -v`: would remove database/uploads.
- `.env` changes in Compose env_file require recreating app/queue/scheduler containers with current immutable SHA.
- Keep MYSQL_ROOT_PASSWORD separate from DB_PASSWORD and never expose DB/Redis ports on VPS.
- `storage/app/public` and `storage/app/private` are persisted across deployments; backup both plus MySQL externally.
- This template stores Laravel logs inside ephemeral containers unless LOG_CHANNEL=stderr; use `docker compose logs app`.
- The image's PHP extensions should be verified against actual composer.lock and runtime needs.
- Verify media library disk config; some media files might use disk `public`, others a custom disk.
- Deployment script migrates before restarting app; DB migration changes cannot be automatically rolled back with an image rollback.
- Build workflow requires GHCR visibility/access configured so VPS can pull packages.
- Dockerfile assumes conventional Vite config and compatible Node 22 version; validate against lockfiles before pushing.
- Disable auto-deploy to production or require environment approval until manually verified.
- No existing tests directory found in repo during inspection; add meaningful tests before enforcing CI.

The bundle includes a minimal PHPUnit health test because the inspected repository had no `tests/` directory. Replace example tests with real integration/feature coverage.
