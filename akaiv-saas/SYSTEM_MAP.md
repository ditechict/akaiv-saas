# SYSTEM_MAP.md — AKAIV SaaS Architecture Discovery

**Generated:** 2026-09-04  
**Method:** Evidence-based repository audit per Deep Architecture Discovery Agent protocol

---

## Repository Structure

```
akaiv-saas/
├── app/
│   ├── Concerns/           # Traits (BelongsToOrganization)
│   ├── Console/Commands/   # Artisan commands (MigrateLegacyDocumentsCommand)
│   ├── Http/Middleware/    # Auth, CSRF, TrustProxies
│   ├── Jobs/               # Queue jobs (VirusScanDocumentJob)
│   ├── Models/             # 14 Eloquent models
│   ├── Policies/           # Authorization (DocumentPolicy)
│   ├── Providers/          # AppServiceProvider
│   └── Scopes/             # Global scopes (OrganizationScope)
├── bootstrap/
├── config/                 # 18 configuration files
├── database/
│   └── migrations/         # Single enterprise schema migration
├── docker/
│   ├── caddy/              # Caddyfile
│   └── php/                # Dockerfile + local.ini
├── public/
├   └── index.php
├── resources/
│   └── views/              # Empty (.gitkeep only)
├── routes/
│   ├── web.php             # Single redirect to /admin
│   └── console.php         # Inspire command only
├── scripts/                # PowerShell helper scripts
├── storage/                # Standard Laravel storage
├── .env.example            # Environment template
├── .env                    # Local environment (not committed)
├── artisan
├── composer.json
├── docker-compose.yml      # 8-service stack
├── phpunit.xml
└── README_QUICKSTART.md
```

---

## Application Inventory

| Application | Type | Framework | Purpose |
|-------------|------|-----------|---------|
| **akaiv-saas** | Backend API + Admin Panel | Laravel 11 + Filament 3 | Multi-tenant judiciary document management SaaS |
| **myarchivesonline.com** | Legacy Production | Laravel 6 | Current production system (separate repo) |

---

## Component Inventory

### Backend Systems (Laravel 11)

| Component | File/Location | Purpose |
|-----------|---------------|---------|
| **Models (14)** | `app/Models/` | Core domain entities with relationships |
| **Global Scope** | `app/Scopes/OrganizationScope.php` | Multi-tenancy enforcement |
| **Trait** | `app/Concerns/BelongsToOrganization.php` | Auto-set organization_id on create |
| **Policy** | `app/Policies/DocumentPolicy.php` | Document-level authorization |
| **Job** | `app/Jobs/VirusScanDocumentJob.php` | ClamAV scan → chains OCR/Thumbnail/Index |
| **Command** | `app/Console/Commands/MigrateLegacyDocumentsCommand.php` | Legacy → SaaS migration |
| **Service Provider** | `app/Providers/AppServiceProvider.php` | Gate + DocumentObserver registration |
| **Middleware** | `app/Http/Middleware/` | Auth, CSRF, TrustProxies |

### Infrastructure (Docker Compose - 8 Services)

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| **php** | Custom (Dockerfile) | 9000 | PHP 8.3 FPM + extensions |
| **caddy** | caddy:2.8-alpine | 80/443 | HTTPS web server |
| **pgsql** | postgres:16-alpine | 5432 | Primary database |
| **redis** | redis:7-alpine | 6379 | Cache, queues, sessions |
| **meilisearch** | getmeili/meilisearch:v1.8 | 7700 | Full-text search |
| **clamav** | clamav/clamav:1.5 | 3310 | Anti-virus scanning |
| **horizon** | Custom | - | Queue dashboard/worker |
| **scheduler** | Custom | - | Cron replacement |

---

## Architecture Overview

### Multi-Tenancy Model
- **Strategy:** Single database, global scope (`OrganizationScope`)
- **Resolution:** `session('active_organization_id')` → falls back to first membership
- **Exemption:** Console commands and unit tests bypass scope
- **Auto-set:** `BelongsToOrganization` trait sets `organization_id` on create

### Data Flow

```
User Request
    → Caddy (HTTPS, TLS termination)
    → PHP-FPM (Laravel 11)
    → Middleware (Auth, CSRF, TrustProxies)
    → Route → Controller
    → Service/Model (with OrganizationScope applied)
    → PostgreSQL (organization-scoped queries)
    → Response
```

### Document Processing Pipeline

```
Document Upload
    → DocumentObserver::created()
    → VirusScanDocumentJob (ClamAV via TCP 3310)
    ├── Infected → status=quarantined, log activity
    └── Clean → status=published
        → OcrDocumentJob (3s delay) [NOT IMPLEMENTED]
        → ThumbnailDocumentJob (5s delay) [NOT IMPLEMENTED]
        → IndexDocumentJob (10s delay) [NOT IMPLEMENTED]
```

### Authentication & Authorization

| Layer | Implementation |
|-------|----------------|
| **Auth** | Laravel Breeze (not yet installed), Filament Shield |
| **Roles** | Spatie Laravel Permission (6 org roles + Platform SuperAdmin) |
| **Policies** | DocumentPolicy with `before()` for SuperAdmin |
| **Gate** | Global `before` in AppServiceProvider for SuperAdmin |
| **Tenancy** | OrganizationScope + session-based org resolution |

### Storage Architecture

| Disk | Driver | Purpose |
|------|--------|---------|
| **s3** (default) | S3/R2 | Documents (private, signed URLs) |
| **local** | Local | App storage |
| **public** | Local | Public assets |
| **private** | Local | Private app files |

---

## Database Relationship Summary

### Core Entities (14 tables)

```
organizations (tenant)
    ├── organization_user (pivot + roles)
    ├── workspaces
    ├── cases
    ├── folders (hierarchical)
    ├── document_types
    ├── documents (UUID, SHA256, status, OCR, virus scan)
    ├── document_versions
    ├── tags + document_tag (pivot)
    ├── shares (ULID token, password, IP, expiry)
    ├── activity_log (Spatie, org-scoped)
    ├── subscriptions + subscription_items (Cashier/Stripe)
```

### Key Relationships

- **Organization** → hasMany: Workspaces, Cases, Folders, Documents, Tags, Subscriptions
- **User** → belongsToMany: Organizations (pivot: role)
- **Document** → belongsTo: Organization, Workspace, Folder, Case, DocumentType, Owner, UploadedBy
- **Document** → hasMany: Versions, Shares, Tags, Activity
- **Folder** → self-referential (parent/children), belongsTo: Workspace, Case
- **Case** → hasMany: Folders, Documents

### Critical Indexes

- `documents`: unique(org_id, folder_id, friendly_name), index(org_id, status), index(sha256, org_id)
- `organization_user`: unique(org_id, user_id)
- `folders`: unique(org_id, workspace_id, parent_folder_id, name)
- `shares`: unique(token ULID)

---

## API Discovery

### Current Routes

| Route | Method | Handler | Status |
|-------|--------|---------|--------|
| `/` | GET | Redirect to `/admin` | Defined |
| `/admin/*` | * | Filament Panel | **NOT IMPLEMENTED** |

### Missing (Planned per Checkpoints)

| Resource | Expected Routes |
|----------|-----------------|
| OrganizationResource | CRUD + RelationManager for users |
| UserResource | CRUD + org membership |
| FolderResource | Hierarchical CRUD |
| DocumentResource | CRUD + FileUpload + Actions (Download, Share, Version) |
| CaseResource | CRUD + Documents RelationManager |
| TagResource | CRUD + Auto-tag rules |

### External APIs

| Service | Integration |
|---------|-------------|
| **ClamAV** | TCP 3310 via `rogervila/php-clamav-scan` |
| **Meilisearch** | HTTP 7700 via Laravel Scout |
| **S3/R2** | Flysystem AWS S3 v3 |
| **Stripe** | Laravel Cashier |
| **Tesseract OCR** | `thiagoprz/eloquent-tesseract-ocr` |
| **LibreOffice** | Document conversion (installed in Docker) |

---

## Frontend Systems

| Aspect | Status |
|--------|--------|
| **Admin Panel** | Filament 3 — **NOT INSTALLED** (0 files in `app/Filament/`, `config/filament*.php`) |
| **Build System** | Vite + Tailwind — **NOT CONFIGURED** (no `package.json`, `vite.config.js`, `tailwind.config.js`) |
| **CSS/JS** | None — `resources/views/` only contains `.gitkeep` |
| **Icons** | Blade Heroicons v2 (composer dep, not used) |

---

## Environment Requirements

| Requirement | Value | Source |
|-------------|-------|--------|
| **PHP** | ^8.3 | composer.json, Dockerfile |
| **Node.js** | 20 (for Vite) | Dockerfile |
| **PostgreSQL** | 16 | docker-compose.yml |
| **Redis** | 7 | docker-compose.yml |
| **Meilisearch** | v1.8 | docker-compose.yml |
| **ClamAV** | 1.5 | docker-compose.yml |
| **Caddy** | 2.8 | docker-compose.yml |
| **Composer** | 2.7 | Dockerfile |

### Required Secrets (.env)

| Variable | Purpose | Default in .env.example |
|----------|---------|------------------------|
| `APP_KEY` | Laravel encryption | Empty (generate) |
| `DB_PASSWORD` | PostgreSQL | `akaiv_ChangeMe_2026!` |
| `REDIS_PASSWORD` | Redis | `akaiv_redis_ChangeMe!` |
| `MEILISEARCH_KEY` | Meilisearch API | `akaiv_master_ChangeMe!` |
| `AWS_ACCESS_KEY_ID` | R2/S3 credentials | Empty |
| `AWS_SECRET_ACCESS_KEY` | R2/S3 credentials | Empty |
| `AWS_ENDPOINT` | R2 endpoint | `https://${ACCOUNT_ID}.r2.cloudflarestorage.com` |
| `STRIPE_KEY/SECRET` | Cashier billing | Empty |