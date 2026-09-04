#!/bin/bash
set -e

echo "=== AKAIV SaaS Codespace Setup ==="

# Wait for Docker-in-Docker to be ready
echo "Waiting for Docker daemon..."
timeout 120 bash -c 'until docker info >/dev/null 2>&1; do sleep 2; done'
echo "Docker is ready!"

# Navigate to project
cd /workspaces/akaiv-saas/akaiv-saas

# 1. Install PHP dependencies
echo "=== Installing Composer dependencies ==="
docker run --rm -v "$(pwd):/app" -w /app composer:2.7 composer install --no-interaction --prefer-dist

# 2. Environment setup
echo "=== Setting up environment ==="
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi

# Generate APP_KEY if not set
if ! grep -q '^APP_KEY=base64:' .env; then
    docker run --rm -v "$(pwd):/app" -w /app --user 1000:1000 php:8.3-cli-alpine php artisan key:generate --force
    echo "Generated APP_KEY"
fi

# 3. Start containers
echo "=== Starting Docker containers ==="
docker compose up -d --build

# Wait for containers to be healthy
echo "Waiting for containers to be healthy..."
sleep 30

# Check container status
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# 4. Run migrations
echo "=== Running migrations ==="
docker exec akaiv-php php artisan migrate --force

# Publish vendor migrations
docker exec akaiv-php php artisan vendor:publish --tag=activitylog-migrations --force
docker exec akaiv-php php artisan vendor:publish --tag=permission-migrations --force
docker exec akaiv-php php artisan vendor:publish --tag=medialibrary-migrations --force
docker exec akaiv-php php artisan vendor:publish --tag=tags-migrations --force

# Run migrations again
docker exec akaiv-php php artisan migrate --force

# 5. Install Filament Shield
echo "=== Installing Filament Shield ==="
docker exec akaiv-php php artisan shield:install --fresh

# 6. Create SuperAdmin (non-interactive)
echo "=== Creating SuperAdmin ==="
docker exec -T akaiv-php php artisan make:filament-user --email=admin@myarchivesonline.com --password=password --name="Super Admin" 2>/dev/null || true

# Assign SuperAdmin role via Shield
docker exec akaiv-php php artisan db:seed --class="BezhanSalleh\\FilamentShield\\FilamentShieldSeeder" 2>/dev/null || true

# Update user to have SuperAdmin role
docker exec akaiv-php php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
\$user = \App\Models\User::where('email', 'admin@myarchivesonline.com')->first();
if (\$user) {
    \$user->assignRole('Platform SuperAdmin');
    echo 'Assigned Platform SuperAdmin role to admin@myarchivesonline.com';
}
"

echo "=== Setup Complete ==="
echo ""
echo "Access URLs (forwarded ports):"
echo "  Filament Admin: https://\$(hostname)-8000.preview.app.github.dev/admin"
echo "  Meilisearch:    https://\$(hostname)-7700.preview.app.github.dev"
echo "  Horizon:        https://\$(hostname)-9631.preview.app.github.dev/horizon"
echo ""
echo "Login: admin@myarchivesonline.com / password"