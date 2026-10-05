# 🏛️ Project State, Architecture & Agent Handover Manifest

> **Notice for Incoming Agents:** Read this entire document before executing commands or proposing changes. It represents the verified ground truth of this repository as of 2026-09-30.

---

### 1. Executive Summary & Identity
- **Project Name & Role:** **AKAIV Archives SaaS** (Active greenfield rebuild of the legacy `myarchivesonline.com`). It is an enterprise multi-tenant legal and judicial document archiving SaaS engineered for court jurisdictions, legal departments, and law firms (focusing on West Africa / Lagos jurisdiction).
- **Current Development Phase:** **Pre-Deployment & Edge-Integration Complete.** Domain models, multi-tenancy global scopes, Filament 3 administrative resources, upload-virus-OCR pipeline jobs, UI contracts, and the Cloudflare Edge Agents microservice are implemented. Ready for CI/CD trigger and containerized staging deployment.
- **Core Architecture Model:** **Hybrid Multi-Tier SaaS:**
  1. *Core Application & Admin:* Laravel 11 + Filament 3 + PostgreSQL 16 (single-database multi-tenant scoped via `OrganizationScope`).
  2. *Edge AI Microservice:* Cloudflare Workers + TypeScript + Agents SDK (`workers/agents-service`) with isolated per-document Durable Objects (`DocumentAssistant`).
  3. *Processing Pipeline:* Docker Compose 8-service cluster (Caddy 2 HTTPS, PHP 8.3 FPM, PostgreSQL 16, Redis 7, Meilisearch 1.8, ClamAV 1.5, Horizon, Scheduler).
  4. *Storage:* Cloudflare R2 / AWS S3 private bucket (strictly signed temporary URLs, no direct public webroot storage).
  5. *Legacy System:* `myarchivesonline.com/` (Laravel 6.2 + MySQL), preserved as a hardened, read-only reference for data migration.
- **Upstream & Downstream Dependencies:**
  - Cloudflare Edge Workers & Durable Objects (`akaiv-agents-service.ditechict.workers.dev`).
  - ClamAV Anti-Virus (TCP port 3310 via `xenolope/quahog`).
  - Tesseract 5 OCR (`thiagoalessio/tesseract_ocr`) & `poppler-utils` (`pdftoppm`).
  - Meilisearch 1.8 via Laravel Scout.
  - Redis 7 for Horizon queues, caching, and session storage.
  - Cloudflare R2 / AWS S3 via Flysystem v3.
  - Stripe via Laravel Cashier.

---

### 2. Technical Stack & Verified Tooling
- **Language & Runtime:**
  - Host OS: Windows (amd64).
  - Host Tooling: Node.js 20.x (`C:\Program Files\nodejs\node.exe`), npm 10.9.3, winget v1.29.290, OpenSSH (`C:\WINDOWS\System32\OpenSSH\ssh.exe`).
  - Container Tooling: PHP 8.3-fpm-alpine, PostgreSQL 16-alpine, Redis 7-alpine, Meilisearch 1.8, ClamAV 1.5, Caddy 2.8-alpine.
  - Edge Tooling: Cloudflare Workers Runtime, Wrangler 4.86.0, `agents@0.22.0`.
- **Framework & Core Libraries:**
  - `laravel/framework` ^11.0, `filament/filament` ^3.2, `bezhansalleh/filament-shield` ^3.3.
  - `spatie/laravel-permission` ^6.4, `spatie/laravel-activitylog` ^4.8, `spatie/laravel-tags` ^4.6.
  - `laravel/scout` ^10.8, `laravel/horizon` ^5.24, `laravel/cashier` ^15.0.
  - Tailwind CSS ^3.4, Vite ^6.0, PostCSS ^8.4.
- **Package Manager & Commands:**
  - *Cloudflare Worker (`workers/agents-service`):*
    - Install: `npm install`
    - Typecheck: `npm run typecheck` (`tsc --noEmit`)
    - Deploy: `npm run deploy` (`wrangler deploy`)
  - *Frontend Contracts (`akaiv-saas`):*
    - Test: `node --test tests/frontend/ui-contract.test.js` (Verified 3/3 passing)
    - Build: `npm run build` (`vite build` — requires `vendor/` from Composer)
  - *Laravel Backend (`akaiv-saas`):*
    - Dependency install: `docker exec akaiv-php composer install --no-interaction --prefer-dist`
    - Migrations: `docker exec akaiv-php php artisan migrate --force`
    - Shield RBAC: `docker exec akaiv-php php artisan shield:install --fresh`
    - Tests: `docker exec akaiv-php php artisan test`
    - Code Style: `docker exec akaiv-php vendor/bin/pint --test`
- **Target Deployment Platform:**
  - Edge: Cloudflare Workers (Live: `https://akaiv-agents-service.ditechict.workers.dev`).
  - App Stack: Docker Compose on Linux VPS / GitHub Codespaces / Cloud Run.

---

### 3. Environment, Hardware & Storage Quirks (Critical)
- **Host OS & Architecture:** Windows 10/11 x64.
- **Filesystem Constraints & Tooling Realities:**
  - **No local `git`, `docker`, or `php` in Windows PATH:** The primary runtime is designed to run in Linux containers (Docker Compose / `.devcontainer/` / GitHub Actions).
  - Winget package installer is available (`v1.29.290`), but download timeouts may occur on unstable connections.
- **Guarded Directories & Anti-Patterns (Strict Rules):**
  - **DO NOT commit `workers/agents-service/node_modules/`:** It contains an oversized `@cloudflare/workerd-linux-64/bin/workerd` binary (118.77 MB) that causes GitHub to reject git pushes. It is explicitly guarded in `.gitignore`.
  - **DO NOT modernize or refactor `myarchivesonline.com/`:** Treat the legacy app as a read-only historical artifact.
  - **DO NOT generate public storage URLs:** All document downloads and previews must strictly use time-limited signed routes (`DownloadDocumentController` and `PreviewDocumentController`).
  - **DO NOT bypass `OrganizationScope`:** Multi-tenancy isolation must be preserved on all queries.
  - **DO NOT change `.env` files casually:** Secrets and keys are strictly mapped across services.
- **Required Environment Variables:**
  - `APP_KEY`: Laravel application encryption key (Configured in `akaiv-saas/.env`: `base64:ppeR4GjR/tAV6NISnn9Nx3emTKCq8pTFctlFTUeaLqQ=`).
  - `DOCUMENT_AGENT_URL`: Live edge worker URL (`https://akaiv-agents-service.ditechict.workers.dev`).
  - `DOCUMENT_AGENT_SECRET`: Shared bearer secret (`bc9631b6bf9040b3234b8edebdddd9a65ec05753623c817fe492a05b79eb1ead`).
  - `AGENT_SHARED_SECRET`: Cloudflare Worker runtime secret matching `DOCUMENT_AGENT_SECRET` (Already provisioned on Cloudflare).
  - `DB_CONNECTION=pgsql`, `DB_HOST=pgsql`, `DB_PORT=5432`, `DB_DATABASE=akaiv`, `DB_USERNAME=akaiv`.
  - `REDIS_HOST=redis`, `REDIS_PORT=6379`.
  - `CLAMAV_HOST=clamav`, `CLAMAV_PORT=3310`.
  - `MEILISEARCH_HOST=http://meilisearch:7700`.

---

### 4. Codebase Topology & File Map
- **Entry Points:**
  - Backend Web: [`akaiv-saas/public/index.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/public/index.php)
  - Edge Worker: [`workers/agents-service/src/index.ts`](file:///c:/Users/diTech/Documents/akaiv-saas-main/workers/agents-service/src/index.ts)
  - Filament Admin: [`akaiv-saas/app/Providers/Filament/AdminPanelProvider.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Providers/Filament/AdminPanelProvider.php)
- **Routing & Controllers:**
  - Web Routes: [`akaiv-saas/routes/web.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/routes/web.php)
  - Download Controller: [`akaiv-saas/app/Http/Controllers/DownloadDocumentController.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Http/Controllers/DownloadDocumentController.php)
  - Preview Controller: [`akaiv-saas/app/Http/Controllers/PreviewDocumentController.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Http/Controllers/PreviewDocumentController.php)
  - Share Controller: [`akaiv-saas/app/Http/Controllers/ShareController.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Http/Controllers/ShareController.php)
  - Edge Bridge Controller: [`akaiv-saas/app/Http/Controllers/DocumentAgentController.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Http/Controllers/DocumentAgentController.php)
- **Filament Resources:**
  - [`app/Filament/Resources/DocumentResource.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Filament/Resources/DocumentResource.php)
  - [`app/Filament/Resources/OrganizationResource.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Filament/Resources/OrganizationResource.php)
  - [`app/Filament/Resources/UserResource.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Filament/Resources/UserResource.php)
  - [`app/Filament/Resources/FolderResource.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Filament/Resources/FolderResource.php)
  - [`app/Filament/Resources/CaseResource.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Filament/Resources/CaseResource.php)
  - [`app/Filament/Resources/TagResource.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Filament/Resources/TagResource.php)
- **Business Logic & Processing Pipeline:**
  - Pipeline Entry Observer: [`akaiv-saas/app/Observers/DocumentObserver.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Observers/DocumentObserver.php)
  - Antivirus Job: [`akaiv-saas/app/Jobs/VirusScanDocumentJob.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Jobs/VirusScanDocumentJob.php)
  - OCR Extraction Job: [`akaiv-saas/app/Jobs/OcrDocumentJob.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Jobs/OcrDocumentJob.php)
  - Thumbnail Generator Job: [`akaiv-saas/app/Jobs/ThumbnailDocumentJob.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Jobs/ThumbnailDocumentJob.php)
  - Search Indexing Job: [`akaiv-saas/app/Jobs/IndexDocumentJob.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Jobs/IndexDocumentJob.php)
  - Multi-Tenancy Scoping: [`akaiv-saas/app/Scopes/OrganizationScope.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Scopes/OrganizationScope.php) & [`akaiv-saas/app/Concerns/BelongsToOrganization.php`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/app/Concerns/BelongsToOrganization.php)
- **Configuration & Infrastructure:**
  - Docker Compose: [`akaiv-saas/docker-compose.yml`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/docker-compose.yml)
  - PHP Dockerfile: [`akaiv-saas/docker/php/Dockerfile`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/docker/php/Dockerfile)
  - Caddy Web Server: [`akaiv-saas/docker/caddy/Caddyfile`](file:///c:/Users/diTech/Documents/akaiv-saas-main/akaiv-saas/docker/caddy/Caddyfile)
  - Cloudflare Wrangler: [`workers/agents-service/wrangler.jsonc`](file:///c:/Users/diTech/Documents/akaiv-saas-main/workers/agents-service/wrangler.jsonc)
  - GitHub Actions CI/CD: [`.github/workflows/ci.yml`](file:///c:/Users/diTech/Documents/akaiv-saas-main/.github/workflows/ci.yml)

---

### 5. Verified Work, Current State & Health Status
- **Features Completed & Verified:**
  - Edge Microservice deployed live to Cloudflare production at `https://akaiv-agents-service.ditechict.workers.dev`.
  - Non-interactive provisioning of `AGENT_SHARED_SECRET` in Cloudflare Workers runtime.
  - End-to-end live HTTP tests verified against production edge:
    - `POST /api/analyze-document` returns `HTTP 200`.
    - Per-document Durable Object persistent state verified (`analysisCount` incrementing 1 -> 2).
  - `akaiv-saas/.env` generated, `APP_KEY` set, and live worker URL & secret bound.
  - Frontend accessibility and brand contracts verified: `node --test tests/frontend/ui-contract.test.js` passes 3/3 tests.
- **Latest Build & Verification Telemetry:**
  - Worker Typecheck (`tsc --noEmit`): **PASSED** (0 errors).
  - Worker Packaging (`wrangler deploy --dry-run`): **PASSED** (2,016.70 KiB bundle, 366.70 KiB gzip).
  - Worker Live Deployment (`wrangler deploy`): **PASSED** (Deployed triggers: `https://akaiv-agents-service.ditechict.workers.dev`, Version ID: `606edeba-20ae-4cc4-b0b6-be1bf7e93cd7`).
  - Frontend Contracts (`node --test tests/frontend/ui-contract.test.js`): **PASSED** (3/3).
  - Frontend Vite Build (`npm run build`): Halted on host because `vendor/` CSS requires Composer install inside container.

---

### 6. Known Gaps, Technical Debt & Immediate Roadmap
- **Unfinished / In-Progress Tasks:**
  - Staging / Production Docker cluster has not yet been booted.
  - Legacy MySQL database password (`earlvzhc_archive`) in `myarchivesonline.com/.env` remains un-rotated at hosting provider.
- **Immediate Next Action:**
  - **Container Cluster Boot:** Execute `docker compose up -d --build` on target staging environment.
- **Subsequent Milestones:**
  - [x] **Git Host Availability:** Configured MinGit portable at `AppData\Local\Programs\Git\cmd\git.exe`.
  - [x] **GitHub Push & CI/CD Green Light:** Pushed to `origin/main` (commit `e45a907`). GitHub Actions workflow [Run #37253859180](https://github.com/ditechict/akaiv-saas/actions/runs/37253859180) passed 100% across both `Laravel (Pint + Pest)` and `Cloudflare Worker (typecheck + test)`.
  1. *Container Cluster Boot:* Execute `docker compose up -d --build` on target staging environment.
  2. *Migrations & SuperAdmin:* Execute `php artisan migrate --force` and `php artisan shield:install --fresh`, then create SuperAdmin user.
  3. *Security Pre-Flight & Migration Dry-Run:* Rotate `earlvzhc_archive` MySQL password at hosting provider, then execute `php artisan app:migrate-legacy-documents --dry-run`.
