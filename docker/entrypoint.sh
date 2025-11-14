#!/bin/sh

set -e

echo "Starting application setup..."

# Wait for PostgreSQL to be ready (if DB_HOST is set)
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "Waiting for PostgreSQL database at ${DB_HOST}:${DB_PORT:-5432}..."
    
    # Use netcat to check if PostgreSQL is ready
    # Retry up to 30 times (30 seconds)
    max_attempts=30
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if nc -z "${DB_HOST}" "${DB_PORT:-5432}" 2>/dev/null; then
            echo "PostgreSQL is ready!"
            break
        fi
        
        attempt=$((attempt + 1))
        echo "PostgreSQL is unavailable - sleeping (attempt $attempt/$max_attempts)"
        sleep 1
    done
    
    if [ $attempt -eq $max_attempts ]; then
        echo "Warning: PostgreSQL connection timeout after $max_attempts attempts"
    fi
else
    echo "Using local PostgreSQL or external database connection..."
fi

# Generate application key if not exists
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generating application key..."
    php artisan key:generate --ansi --force || true
fi

# Run migrations (only if AUTO_MIGRATE is set to true or RENDER is true)
if [ "$AUTO_MIGRATE" = "true" ] || [ "$RENDER" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force || true
    
    # Run seeders (only if AUTO_SEED is set to true or RENDER_SEED is true)
    if [ "$AUTO_SEED" = "true" ] || [ "$RENDER_SEED" = "true" ]; then
        echo "Running seeders..."
        php artisan db:seed --force || true
    fi
else
    echo "Skipping migrations (set AUTO_MIGRATE=true to enable)"
fi

# Optimize Laravel for production
if [ "$APP_ENV" = "production" ] || [ "$RENDER" = "true" ]; then
    echo "Optimizing Laravel for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    echo "Clearing cache for development..."
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

echo "Application setup complete!"
echo "Starting application..."

# Execute the command passed to the container
# Render will pass the command with PORT already set, so we just execute it
exec "$@"
