#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

seed_reference=0
skip_npm=0
skip_migrate=0
skip_reload=0
skip_maintenance=0
maintenance_started=0

usage() {
    cat <<'USAGE'
Usage: scripts/deploy-production.sh [options]

Options:
  --seed-reference       Seed starter reference data after migrations.
                         Use only on a new product site before real content exists.
  --skip-npm             Skip npm ci and npm run build.
  --skip-migrate         Skip php artisan migrate --force.
  --skip-reload          Skip php artisan reload.
  --skip-maintenance     Do not run php artisan down/up around the deployment.
  -h, --help             Show this help.
USAGE
}

restore_maintenance_mode() {
    if [[ "$maintenance_started" == "1" ]]; then
        php artisan up || true
    fi
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --seed-reference)
            seed_reference=1
            shift
            ;;
        --skip-npm)
            skip_npm=1
            shift
            ;;
        --skip-migrate)
            skip_migrate=1
            shift
            ;;
        --skip-reload)
            skip_reload=1
            shift
            ;;
        --skip-maintenance)
            skip_maintenance=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

if [[ ! -f .env ]]; then
    echo "Missing .env. Create it from .env.example before deploying." >&2
    exit 1
fi

if [[ "$skip_maintenance" != "1" && -d vendor ]]; then
    if php artisan down --render="errors::503" --retry=60; then
        maintenance_started=1
        trap restore_maintenance_mode EXIT
    fi
fi

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if [[ "$skip_npm" != "1" ]]; then
    npm ci
    npm run build
fi

if [[ ! -e public/storage && ! -L public/storage ]]; then
    php artisan storage:link
fi

php artisan optimize:clear

if [[ "$skip_migrate" != "1" ]]; then
    php artisan migrate --force
fi

if [[ "$seed_reference" == "1" ]]; then
    php artisan db:seed --class=ReferenceDataSeeder --force
fi

php artisan optimize

if [[ "$skip_reload" != "1" ]]; then
    php artisan reload
fi

restore_maintenance_mode
trap - EXIT
