#!/bin/bash
set -e

echo "=== AKAIV SaaS Post-Create Setup ==="

# Ensure we're in the right directory
cd /workspaces/akaiv-saas

# 1. Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

# 2. Environment setup
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate --force
fi

# 3. Start Docker containers
echo "Starting Docker containers..."
docker compose up -d --build

# 4. Wait for containers to be healthy
echo "Waiting for containers to be healthy..."
sleep 30

# 5. Run migrations
echo "Running migrations..."
docker exec akaiv-php php artisan migrate --force

# 6. Publish vendor migrations
docker exec akaiv-php php artisan vendor:publish --tag=activitylog-migrations --force
docker exec akaiv-php php artisan vendor:publish --tag=permission-migrations --force
docker exec akaiv-php php artisan vendor:publish --tag=medialibrary-migrations --force
docker exec akaiv-php php artisan vendor:publish --tag=tags-migrations --force
docker exec akaiv-php php artisan migrate --force

# 7. Install Filament Shield
echo "Installing Filament Shield..."
docker exec akaiv-php php artisan shield:install --fresh

echo "=== Post-create setup complete ==="
echo "Next steps:"
echo "1. Create SuperAdmin: docker exec -it akaiv-php php artisan make:filament-user"
echo "2. Access panel at: https://<codespace-name>-8000.preview.app.github.dev/admin"