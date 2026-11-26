#!/bin/sh

set -eu

# The test service owns an isolated bootstrap/cache volume. Rebuild package
# discovery there so package-provided migrations and service providers are
# available without reading the application's cached configuration.
php artisan package:discover --no-interaction --quiet

exec vendor/bin/phpunit "$@"
