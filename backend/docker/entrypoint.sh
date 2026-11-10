#!/bin/sh
# Prepares the container, then runs the given command (API server or queue worker).
set -e

# .env is never committed. Real environment variables set by docker compose
# take precedence over anything in this file.
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

if [ -n "$DB_DATABASE" ] && [ "$DB_CONNECTION" = "sqlite" ]; then
    touch "$DB_DATABASE"
fi

# Only the API container migrates, so two containers never race on it.
# Seeding is idempotent (updateOrCreate), so running it on every start is safe.
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force --seed --no-interaction
fi

exec "$@"
