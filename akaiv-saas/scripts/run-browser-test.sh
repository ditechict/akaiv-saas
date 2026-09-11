#!/usr/bin/env bash
set -euo pipefail

# AKAIV SaaS — browser test bootstrap for GitHub Codespaces
# Runs a plain HTTP dev server on port 8000 (Codespaces-friendly) and seeds an admin user.

cd "$(dirname "$0")/.."

echo "==> 1/7 Fixing Docker socket permissions (best effort)"
if [ -S /var/run/docker.sock ]; then
  sudo chown root:docker /var/run/docker.sock 2>/dev/null || true
  sudo chmod 660 /var/run/docker.sock 2>/dev/null || true
fi
docker version >/dev/null

echo "==> 2/7 Installing PHP dependencies (container, platform-reqs ignored)"
if [ ! -d vendor ]; then
  docker run --rm -v "$(pwd):/app" -w /app composer:2.7 \
    composer install --no-interaction --prefer-dist --ignore-platform-reqs
fi

echo "==> 3/7 Preparing .env for browser testing"
if [ ! -f .env ]; then
  cp .env.example .env
fi

set_env() {
  local key="$1" value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

set_env APP_ENV local
set_env APP_DEBUG true
set_env APP_URL http://localhost:8000
set_env DB_CONNECTION pgsql
set_env DB_HOST pgsql
set_env DB_PORT 5432
set_env DB_DATABASE akaiv
set_env DB_USERNAME akaiv
set_env DB_PASSWORD akaiv_ChangeMe_2026!
set_env SESSION_DRIVER file
set_env CACHE_STORE file
set_env QUEUE_CONNECTION sync
set_env SCOUT_DRIVER collection
set_env FILESYSTEM_DISK local
set_env SESSION_SECURE_COOKIE false
set_env BROADCAST_CONNECTION log

echo "==> 4/7 Generating APP_KEY"
docker run --rm -v "$(pwd):/app" -w /app --user 1000:1000 php:8.3-cli-alpine \
  php artisan key:generate --force >/dev/null

echo "==> 5/7 Starting database, cache and web server"
docker compose up -d pgsql redis serve

echo "==>     Waiting for PostgreSQL to become ready"
for i in $(seq 1 30); do
  if docker exec akaiv-pgsql pg_isready -U akaiv >/dev/null 2>&1; then break; fi
  sleep 2
done

echo "==> 6/7 Running migrations and Shield"
docker exec akaiv-serve php artisan migrate --force
docker exec akaiv-serve php artisan shield:install --fresh || true

echo "==> 7/7 Creating admin user"
docker exec akaiv-serve php artisan make:filament-user \
  --name="Admin" --email="admin@myarchivesonline.com" --password="password" || true

docker exec akaiv-serve php artisan tinker --execute="
\$u = App\Models\User::where('email','admin@myarchivesonline.com')->first();
if (\$u) {
    \$role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Platform SuperAdmin', 'guard_name' => 'web']);
    \$u->assignRole(\$role);
    echo 'Admin ready: '.\$u->email.PHP_EOL;
}
"

echo
echo "======================================================"
echo " AKAIV SaaS is running."
echo " In the Codespace PORTS tab: forward port 8000 and set it Public."
echo " Open:  <forwarded-https-url>/admin"
echo " Login: admin@myarchivesonline.com / password"
echo "======================================================"
