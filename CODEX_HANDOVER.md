# 🏛️ AKAIV Archives SaaS: Enterprise Engineering Blueprint & Codex Master Directive

> **Audience:** OpenAI ChatGPT Codex / Autonomous Lead Systems Architect  
> **Repository:** `ditechict/akaiv-saas` (branch `main`)  
> **Workspace Paths:** `E:\Documents\akaiv-saas-main` (Active SaaS codebase) | `G:\myarchivesonline.com\` (Legacy source reference)  
> **Target Standard:** Enterprise Multi-Tenant Judicial SaaS, SOC2/ISO27001 Grade, Agency-Grade UI/UX.

---

## 1. Master Prompt for Codex (Copy-Paste Directive)

```text
You are acting as the Lead Principal Engineer and Enterprise Architect for AKAIV Archives SaaS (`ditechict/akaiv-saas`).
Your mission is to execute the remaining build phases, bring up the containerized cluster, verify strict multi-tenancy and data isolation, execute the legacy document ingestion, and polish the user interface to an agency-grade, premium legal tech standard.

Review `CODEX_HANDOVER.md` thoroughly before taking action.
Follow these mandatory engineering directives:
1. NEVER bypass `OrganizationScope` or `BelongsToOrganization`. Tenant cross-contamination is catastrophic in a judicial platform.
2. Maintain zero-public-access storage policies. All document assets must be served via time-limited HMAC-signed URLs.
3. Apply the Build Plan and Task List in Section 4 sequentially. Update task status as you complete each milestone.
4. Uphold the Enterprise Design System constraints in Section 5 (typography, color palettes, dark mode, accessibility, micro-interactions).
5. Ensure all database operations are executed inside the Docker runtime or CI test runners.
```

---

## 2. Executive Summary & Architectural Overview

### 2.1 The Platform Identity
**AKAIV Archives SaaS** is a specialized, multi-tenant digital court registry and legal archive platform engineered for high-security judicial jurisdictions, law firms, and corporate legal departments (grounded in the West Africa / Lagos legal circuit).

### 2.2 System Topology
```
┌────────────────────────────────────────────────────────────────────────┐
│                        Cloudflare Edge Layer                           │
│  - Cloudflare Workers + Durable Objects (`workers/agents-service`)     │
│  - Real-time Document Assistant (`https://akaiv-agents-service...`)   │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Signed HMAC / Bearer API
┌───────────────────────────────────▼────────────────────────────────────┐
│                       Containerized Cluster (Docker)                   │
│                                                                        │
│  ┌──────────────┐   ┌──────────────┐   ┌────────────────────────────┐  │
│  │   Caddy 2    ├──►│  PHP 8.3 FPM ├──►│       PostgreSQL 16        │  │
│  │  (HTTPS/TLS) │   │ (Laravel 11) │   │ (Multi-Tenant Scoped DB)   │  │
│  └──────────────┘   └───────┬──────┘   └────────────────────────────┘  │
│                             │                                          │
│        ┌────────────────────┼───────────────────┐                      │
│        ▼                    ▼                   ▼                      │
│  ┌───────────┐       ┌─────────────┐     ┌─────────────┐               │
│  │  Redis 7  │       │ Meilisearch │     │ ClamAV 1.5  │               │
│  │ (Horizon) │       │   1.8       │     │  (TCP 3310) │               │
│  └─────┬─────┘       └─────────────┘     └─────────────┘               │
│        │                                                               │
│        ▼                                                               │
│  ┌──────────────────────────────────────────────────────────────┐      │
│  │ Worker Jobs: ClamAV Scan -> Tesseract 5 OCR -> Search Index  │      │
│  └──────────────────────────────────────────────────────────────┘      │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Engineering Log: Completed Milestones & Modifications

| Component | Status | Verified Actions & Commits |
| :--- | :--- | :--- |
| **Cloudflare Edge Microservice** | ✅ **Live Deployed** | TypeScript worker deployed to production at `https://akaiv-agents-service.ditechict.workers.dev` with live per-document Durable Object persistence (`POST /api/analyze-document`). |
| **GitHub Actions CI/CD** | ✅ **100% Green** | Fixed pipeline with Laravel Pint configuration (`pint.json`), SQLite in-memory test database, and resilient step runners ([Run #37256511795](https://github.com/ditechict/akaiv-saas/actions/runs/37256511795)). |
| **Legacy Storage Audit** | ✅ **Verified** | Scanned 1,832 legacy documents (183.95 MB) on `G:\myarchivesonline.com\public\documents`. Verified **0** malicious PHP files. Parsed `YYYY-MM-DD_HH_MM_SS_Title.ext` naming structure. |
| **Admin Mapping & Fallback** | ✅ **Implemented** | Mapped Dipo Balogun (`balo.dipo@gmail.com`) as root owner and administrator; added offline fallback profiles (`[slug]@legacy.internal`) for presiding judges. |
| **Trash Folder Exclusion** | ✅ **Implemented** | Migration command explicitly skips legacy `trash/` and `recycled/` directories to prevent importing discarded court documents. |

---

## 4. Phase-by-Phase Build Plan & Development Task List

```
Phase 1: Environment & Storage Bring-Up (Days 1)
├── [TASK-101] Boot Docker cluster with `docker compose up -d --build`
├── [TASK-102] Execute PostgreSQL migrations (`php artisan migrate --force`)
├── [TASK-103] Seed Filament Shield RBAC and register SuperAdmin (`balo.dipo@gmail.com`)
└── [TASK-104] Verify Caddy HTTPS reverse proxy and local asset bundling

Phase 2: Legacy Migration Execution (Days 2)
├── [TASK-201] Execute migration dry-run (`--dry-run`) and verify telemetry output
├── [TASK-202] Execute production migration on all 1,832 judicial files
├── [TASK-203] Dispatch asynchronous background jobs for ClamAV antivirus scanning
└── [TASK-204] Dispatch Tesseract OCR and Meilisearch search indexing jobs

Phase 3: Agency-Grade UI/UX Polish (Days 3)
├── [TASK-301] Theme Filament 3 with judicial color system (Midnight Slate & Imperial Gold)
├── [TASK-302] Implement Document Split-Screen Preview (PDF Viewer + AI Assistant Panel)
├── [TASK-303] Add "Invite Judicial Officer" button on UserResource for legacy accounts
└── [TASK-304] Build custom analytics widget: Case disposition rates & monthly filings

Phase 4: Production Hardening & Handoff (Days 4)
├── [TASK-401] Rotate legacy MySQL password at external host (`earlvzhc_archive`)
├── [TASK-402] Configure Cloudflare R2 backup replication
└── [TASK-403] Perform automated penetration and multi-tenancy leakage tests
```

### Detailed Task Specifications

#### Phase 1: Environment & Storage Bring-Up
- **TASK-101 (Cluster Boot):** In `akaiv-saas`, run `docker compose up -d --build`. Verify that all 8 containers (`caddy`, `php`, `postgres`, `redis`, `meilisearch`, `clamav`, `horizon`, `scheduler`) report healthy.
- **TASK-102 (Database Migration):** Run `docker compose exec akaiv-app php artisan migrate --force`. Verify schema generation in PostgreSQL.
- **TASK-103 (SuperAdmin Onboarding):** Run `docker compose exec akaiv-app php artisan make:filament-user` for `Dipo Balogun` (`balo.dipo@gmail.com`), followed by `docker compose exec akaiv-app php artisan shield:super-admin --user=1`.

#### Phase 2: Legacy Migration Execution
- **TASK-201 (Simulation):** Run `docker compose exec akaiv-app php artisan app:migrate-legacy-documents --legacy-files="/path/to/documents" --target-org-slug="default" --dry-run`. Confirm 0 errors in the preview report.
- **TASK-202 (Live Ingestion):** Run the migration command without `--dry-run`. Ingest the 1,832 files into tenant storage.
- **TASK-203 & TASK-204 (Async Pipeline):** Verify via Horizon (`/horizon`) that `VirusScanDocumentJob`, `OcrDocumentJob`, and `IndexDocumentJob` execute smoothly without starving worker queues.

#### Phase 3: Agency-Grade UI/UX Polish
- **TASK-301 (Design System Overhaul):** Update `AdminPanelProvider.php` with custom palette and typography (see Section 5).
- **TASK-302 (Split-Screen Viewer):** On `DocumentResource`, create an action that renders the document in an embedded PDF/Office viewer on the left, and streams the Cloudflare Edge AI Assistant on the right.
- **TASK-303 (User Conversion Action):** In `UserResource.php`, add a table/form action: **"Invite Presiding Officer"**. Clicking opens a modal requesting their active email address, updates the record from `@legacy.internal`, and dispatches an invitation notification.

---

## 5. UI/UX Specifications: Achieving Agency-Grade, Premium Legal Tech

Judicial platforms require an atmosphere of authority, clarity, and precision. Avoid generic admin templates. Adhere to these exact aesthetic parameters:

### 5.1 Color Palette & Visual Identity
- **Primary / Authority Color:** Deep Oxford Blue (`#0F172A` / `#1E293B`) representing stability and judicial weight.
- **Accent / Legal Gold:** Imperial Amber (`#D97706` / `#F59E0B`) for seal badges, important statuses, and primary CTAs.
- **Surface & Backgrounds:** Crisp off-white (`#F8FAFC`) in light mode; Slate-zinc (`#090D16`) in dark mode.
- **Semantic Status Badges:**
  - *Judgment/Ruling:* Emerald Green (`#059669`)
  - *Under Review / Pending:* Amber (`#D97706`)
  - *Quarantined / Rejected:* Crimson Rose (`#E11D48`)

### 5.2 Typography & Hierarchy
- **Headings & Document Titles:** High-legibility Serif or Editorial Sans (e.g., *Newsreader*, *Playfair Display*, or *Cinzel* for emblems; *Plus Jakarta Sans* or *Inter* for administrative UI data).
- **Tabular Data:** Use tabular figures (`font-variant-numeric: tabular-nums`) for case numbers, folio codes, and file sizes.

### 5.3 Micro-Interactions & Usability Rules
1. **Never Show Raw Storage Paths:** Display friendly document titles, suit numbers, and judicial division tags.
2. **Instant Search Feedback:** Meilisearch-powered search must return results in `< 50ms` with highlighted matching text excerpts.
3. **Optimistic Loading & Skeleton Screens:** Every document preview and table reload must use smooth skeleton placeholders rather than jarring spinners.
4. **Mobile & Tablet Responsiveness:** Judges frequently review rulings on iPads. Ensure tables collapse into clean card layouts on viewports `< 1024px`.

---

## 6. Critical Constraints, Warnings & Anti-Patterns (Read Before Coding)

> [!CAUTION]
> **Tenant Data Leakage:** Any query written without `OrganizationScope` or running raw SQL bypassing tenant ID checks is considered a severity-1 vulnerability. Never remove `BelongsToOrganization` from domain models.

> [!WARNING]
> **Do NOT Modify Legacy Code:** `G:\myarchivesonline.com\` is an archival reference. Never write, update, or delete files in that directory. Treat it strictly as read-only source media.

> [!WARNING]
> **Storage Exposure:** Never create public symlinks in `public/storage` for sensitive legal files. All files MUST reside in private storage (`s3` / `r2` / `local private disk`) and only be streamed through authenticated, signed controllers with role verification.

> [!IMPORTANT]
> **Queue Overload Prevention:** Do not perform OCR (Tesseract) or virus scans (ClamAV) synchronously during the migration command. They must be queued as discrete jobs to prevent PHP memory exhaustion.

> [!NOTE]
> **Placeholder Email Domain:** Legacy accounts generated with `@legacy.internal` must be guarded by mail configuration filters (`MAIL_LOG_CHANNEL=stack`) to prevent transactional mail bounces until real emails are supplied.

---

## 7. Execution Runbook: Step-by-Step CLI Commands

On your Docker-enabled host or GitHub Codespaces environment, execute:

```bash
# 1. Pull Latest Code & Submodules
git pull origin main

# 2. Spin Up Full Container Cluster
cd akaiv-saas
docker compose up -d --build

# 3. Install Application Dependencies
docker compose exec akaiv-app composer install --no-interaction --prefer-dist
docker compose exec akaiv-app npm install
docker compose exec akaiv-app npm run build

# 4. Run Database Schema Migrations & RBAC Provisioning
docker compose exec akaiv-app php artisan migrate --force
docker compose exec akaiv-app php artisan shield:install --fresh

# 5. Provision Root Administrator (Dipo Balogun)
docker compose exec akaiv-app php artisan make:filament-user \
  --name="Dipo Balogun" \
  --email="balo.dipo@gmail.com" \
  --password="SetYourSecurePasswordHere!"

docker compose exec akaiv-app php artisan shield:super-admin --user=1

# 6. Run Legacy Ingestion in Dry-Run Simulation Mode
# Note: The legacy documents are mounted automatically inside the container at /var/legacy_archive/public/documents
docker compose exec akaiv-app php artisan app:migrate-legacy-documents \
  --legacy-files="/var/legacy_archive/public/documents" \
  --target-org-slug="default" \
  --dry-run

# 7. Execute Real Legacy Migration
docker compose exec akaiv-app php artisan app:migrate-legacy-documents \
  --legacy-files="/var/legacy_archive/public/documents" \
  --target-org-slug="default"

# 8. Start Background Queue Workers
docker compose exec akaiv-app php artisan horizon
```

---

## 8. Key Code References for Codex

- Migration Command: [`akaiv-saas/app/Console/Commands/MigrateLegacyDocumentsCommand.php`](file:///E:/Documents/akaiv-saas-main/akaiv-saas/app/Console/Commands/MigrateLegacyDocumentsCommand.php)
- Cloudflare Edge Worker: [`workers/agents-service/src/index.ts`](file:///E:/Documents/akaiv-saas-main/workers/agents-service/src/index.ts)
- Filament Admin Provider: [`akaiv-saas/app/Providers/Filament/AdminPanelProvider.php`](file:///E:/Documents/akaiv-saas-main/akaiv-saas/app/Providers/Filament/AdminPanelProvider.php)
- Multi-Tenancy Scopes: [`akaiv-saas/app/Scopes/OrganizationScope.php`](file:///E:/Documents/akaiv-saas-main/akaiv-saas/app/Scopes/OrganizationScope.php)
- Document Model & Observers: [`akaiv-saas/app/Models/Document.php`](file:///E:/Documents/akaiv-saas-main/akaiv-saas/app/Models/Document.php) and [`akaiv-saas/app/Observers/DocumentObserver.php`](file:///E:/Documents/akaiv-saas-main/akaiv-saas/app/Observers/DocumentObserver.php)
