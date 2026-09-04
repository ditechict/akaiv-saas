# Repository Guidance

## Repository Layout

- `akaiv-saas/` is the active greenfield application: Laravel 11, PHP 8.3, Filament 3, PostgreSQL, Redis, Meilisearch, and Docker Compose.
- `myarchivesonline.com/` is the legacy Laravel 6 application. Treat it as a compatibility-sensitive reference and do not apply Laravel 11 conventions there unless a task explicitly targets it.
- Root-level `README.md`, `README_QUICKSTART.md`, checkpoints, and roadmap files describe project status and operational context.

## Working Rules

- Keep changes scoped to the application named by the task. Prefer the existing patterns, services, policies, scopes, and models in that application.
- For the active app, preserve tenant isolation through `App\\Scopes\\OrganizationScope` and organization-aware models. Do not bypass authorization or expose permanent storage URLs.
- Uploads must remain compatible with the documented ClamAV scan, OCR, queue, and signed temporary URL workflow.
- Avoid changing generated/vendor/build artifacts, secrets, `.env` files, or storage data.
- Do not modernize the legacy app as part of active-app work.

## Validation

From `akaiv-saas/`:

- Install dependencies: `composer install --no-interaction --prefer-dist`
- Run tests: `php artisan test`
- Check style: `vendor/bin/pint --test`
- Inspect routes/config when relevant: `php artisan route:list` and `php artisan config:show`
- Use Docker services for PostgreSQL, Redis, Meilisearch, ClamAV, Horizon, and scheduler when a check needs infrastructure.

Before submitting changes, run the narrowest relevant test or lint command, then broader tests when the change crosses shared application boundaries.

## Code Style

- Follow Laravel conventions and the surrounding file's style.
- Keep public APIs and database contracts stable unless the task requires a migration.
- Add focused tests for authorization, tenant isolation, uploads, signed URLs, queues, and other security-sensitive behavior.
- Do not add comments that merely narrate obvious code.
