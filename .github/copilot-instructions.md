# Copilot instructions for akaiv-saas

## Repository scope

This repository contains three related areas:

- `akaiv-saas/` is the active greenfield Laravel 11 application (PHP 8.3, Filament 3).
- `myarchivesonline.com/` is the legacy Laravel 6 application. Treat it as a compatibility-sensitive reference; do not apply active-app conventions there unless the task explicitly targets it.
- `workers/agents-service/` is a Cloudflare Workers Agents SDK service used by the active Laravel app for document analysis.

Keep changes scoped to the requested area. Do not modify `.env` files, secrets, storage data, generated artifacts, or `vendor/`/`node_modules/`.

## Build, test, and lint

Run Laravel commands from `akaiv-saas/`. The documented environment uses Docker Compose; prefer the PHP container when services are required:

```bash
docker compose up -d --build
docker exec akaiv-php composer install --no-interaction --prefer-dist
docker exec akaiv-php php artisan key:generate --force
docker exec akaiv-php php artisan migrate --force
```

Laravel validation:

```bash
docker exec akaiv-php php artisan test
docker exec akaiv-php php artisan test tests/Feature/ExampleTest.php
docker exec akaiv-php php artisan test --filter=TestName
docker exec akaiv-php vendor/bin/pint --test
```

The scaffold currently has only the test bootstrap and may not yet contain test cases. PHPUnit uses in-memory SQLite, synchronous queues, array cache/session/mail, and the Scout collection driver; infrastructure-dependent behavior needs the Docker services instead.

Useful operational checks:

```bash
docker compose ps
docker compose logs --tail=50 <service>
docker exec akaiv-php php artisan route:list
curl -k -I https://akaiv.localhost/admin/login
```

For the document-analysis worker, run commands from `workers/agents-service/`:

```bash
npm install
npm run typecheck
npm run dev
npm run deploy
```

The worker requires the `AGENT_SHARED_SECRET` Wrangler secret. The Laravel side requires matching `DOCUMENT_AGENT_URL` and `DOCUMENT_AGENT_SECRET`; never commit either secret.

## Architecture

The active application is a single-database, organization-aware document-management SaaS:

- Filament’s admin panel is registered by `App\Providers\Filament\AdminPanelProvider` at `/admin`, with Shield providing role/permission integration.
- Organization-aware models use `App\Concerns\BelongsToOrganization`, which installs `App\Scopes\OrganizationScope` and fills `organization_id` from the session’s `active_organization_id`. `User::currentOrganization()` selects or initializes that session value.
- Authorization is layered: policies (especially `DocumentPolicy`) verify the active organization and permissions, while the `Platform SuperAdmin` role is handled by the global gate/policy `before` hooks. Do not bypass either layer.
- Documents are stored on private disks (normally S3-compatible storage). Preview uses a short-lived signed route and streams only documents that are not deleted and have passed virus scanning. Do not expose permanent storage URLs.
- Creating a document is observed by `DocumentObserver`, which dispatches `VirusScanDocumentJob`. A successful scan publishes an uploading document and dispatches OCR, thumbnail, and Meilisearch indexing jobs through Redis/Horizon. Quarantine failures rather than making an unsafe file available.
- `Document` combines soft deletes, activity logging, Spatie media/tags, Laravel Scout indexing, and organization scoping. Changes to its searchable fields, lifecycle status, or storage state may need corresponding job/policy/audit updates.
- PostgreSQL is the primary database; Redis backs queues/cache/sessions; Meilisearch handles document search; ClamAV scans uploads; Tesseract and `pdftoppm` support OCR/thumbnail processing; Caddy provides local HTTPS. Horizon and the scheduler run as separate Compose services.
- `workers/agents-service/` maps each document UUID to a Durable Object. Laravel calls its authenticated `/api/analyze-document` endpoint through `DocumentAgentController`; the bridge authorizes the document before sending metadata.

The legacy application has its own Laravel 6 dependency and conventions. Do not modernize it while changing the active app.

## Codebase-specific conventions

- Preserve tenant isolation. Use organization-aware models and existing scopes/policies; do not call `withoutTenancy` or otherwise remove the global scope unless an explicit cross-organization operation is required and authorization is handled.
- Gate every document view/download/edit/delete through the document policy. Keep active-organization checks in place even when a user owns a document.
- Keep uploaded files private and compatible with the ClamAV → OCR/thumbnail → indexing pipeline. Do not dispatch or rename pipeline stages casually: the jobs are separate queue boundaries and some planned milestones may still be incomplete.
- Use UUID route binding for documents (`Document::getRouteKeyName()` returns `uuid`), and use signed, expiring URLs for previews.
- Preserve auditability: document changes use Spatie activity logging, and virus detections record the signature and an activity event.
- Filament resources belong under `akaiv-saas/app/Filament/Resources`; keep resource authorization aligned with the model policy and keep `AdminPanelProvider` registered when changing panel behavior.
- Database changes belong in migrations and must preserve the existing public/database contracts unless the task explicitly requires a migration. Shield and package migrations are also kept in `akaiv-saas/database/migrations`.
- Follow the surrounding Laravel/PHP style and use Laravel Pint for formatting checks. Avoid comments that only narrate obvious code.
- For security-sensitive changes, add focused coverage for authorization, organization isolation, private/signed storage access, queues, and upload processing when the test suite is available.

Operational setup details and the legacy migration command are documented in `akaiv-saas/README_QUICKSTART.md`.

## MCP

Playwright MCP is appropriate for browser smoke tests of the Filament admin panel. Use it against the local HTTPS site after Docker services are running, and keep authentication and any test credentials out of the repository.
