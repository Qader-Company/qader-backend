#!/usr/bin/env bash
set -euo pipefail
ENV_NAME="${1:?testing or production}"
TAG="${2:?commit SHA required}"
case "$ENV_NAME" in
  testing) BRANCH=develop ;;
  production) BRANCH=main ;;
  *) echo 'Invalid environment' >&2; exit 2 ;;
esac
cd "/opt/qader/${ENV_NAME}"
COMPOSE=(docker compose -p "qader-${ENV_NAME}" -f compose.yaml -f "compose.${ENV_NAME}.yaml")
git fetch origin "$BRANCH"
git checkout -B "$BRANCH" "origin/$BRANCH"
[ "$(git rev-parse HEAD)" = "$TAG" ] || { echo 'Commit mismatch' >&2; exit 1; }
export IMAGE_TAG="$TAG"
"${COMPOSE[@]}" config --quiet
"${COMPOSE[@]}" pull app queue scheduler nginx
"${COMPOSE[@]}" up -d mysql redis
"${COMPOSE[@]}" run --rm -T app php artisan migrate --force </dev/null
"${COMPOSE[@]}" up -d --remove-orphans
"${COMPOSE[@]}" exec -T app php artisan optimize:clear
"${COMPOSE[@]}" ps
