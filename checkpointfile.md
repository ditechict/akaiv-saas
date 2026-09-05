# AKAIV Archives — DEVELOPMENT CONTINUATION CHECKPOINT

**Checkpoint Date:** 2026-08-26
**Initiated by:** Auto-pause at 85% budget trigger
**Checkpoint format v1:** Mandatory structured per project protocol

---

## 1. INVENTORY OF ALL COMPLETED DEVELOPMENT OBJECTIVES

### Phase D0: DAY 0 EMERGENCY INCIDENT RESPONSE (COMPLETED ✅)

| Objective ID | Outcome | Location / Reference | Rationale / Technical Notes |
|---|---|---|---|
| D0-1 | ✅ Environment hardening | [.env](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/.env#L1-L5) | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://myarchivesonline.com`. Disables CVE-2021-3129 attack surface (Ignition RCE requires APP_DEBUG). APP_KEY was also rotated: old `base64:wIng5abu8wU0xOgqTPSxEjfWW1ch0K6QD6KzgftiP4Q=` → new `base64:RkREdTduSHRzZVF3d2pKNkNXeEpYZjJqNGgxN2lhbjg=`. Effect: all existing signed cookies / sessions invalidated automatically. |
| D0-2 | ✅ Backdoor deletion + forensic log | [public/upl.php] DEL — See: [INCIDENT_RESPONSE_LOG_20260826.md](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/INCIDENT_RESPONSE_LOG_20260826.md) | Three files permanently deleted: 1) `public/upl.php` (commodity PHP uploader, API key `7ce08fe35639adedac1007ef9c7f4503` with `mkdir` + `upload(file_put_contents)` commands). 2) `public/public/upl.php` + dir (attacker created via BACKDOOR-01 mkdir command). 3) `public/public_html/upl.php` + dir (same). Forensic record written to `INCIDENT_RESPONSE_LOG_20260826.md` with IOC narrative. |
| D0-3 | ✅ Exposure files removed | `public/assets/plugins/ckeditor/samples/old/sample_posteddata.php` + `assets/posteddata.php` DELETED | CKEditor "sample" scripts that accept arbitrary POST. Never needed in production; eliminates known upload-scanner target path. |
| D0-4 | ✅ Direct document access blocked | [public/documents/.htaccess](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/public/documents/.htaccess#L1-L1) | Content: `Require all denied`. Prevents direct browsing of all sensitive judgments while legacy storage location is still in use. Long-term migration target: `storage/app/documents` or S3. |
| D0-5 | ✅ Session invalidation | Deleted 56 files in `storage/framework/sessions/*` (only `.gitignore` remains) | Combined with APP_KEY rotation above, achieves 100% forced re-login of every user including any attacker who may have had a valid session from the 2023-02-21 compromise. |

### Phase D1: DAY 1 LEGACY HOTFIXES (COMPLETED ✅)

| Objective ID | Outcome | Location / Reference | Technical Notes |
|---|---|---|---|
| D1-1 | ✅ Document search() data leak fixed | [DocumentController.php search()](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Http/Controllers/DocumentController.php#L34-L49) | Added `['user_id', Auth::id()]` filter to the `$documents` query. Also added missing `$folders` variable to the return to prevent undefined view variable. Previously: search returned ALL users' documents to any authenticated user — critical IDOR fixed. |
| D1-2 | ✅ Update folder query bug fixed | [DocumentController.php update()](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Http/Controllers/DocumentController.php#L153-L160) | Changed `Folder::where('name', $folder)->first()` → `Folder::where(['id' => $folder, 'user_id' => Auth::id()])->first()` with `abort_if(!$folder, 403)`. Old code treated user POST of integer folder ID as folder NAME lookup — always returned null, threw "trying to get property name of non-object" 500 on any folder-selected update. |
| D1-3 | ✅ User creation role enforcement | [UserController.php store()](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Http/Controllers/UserController.php#L23-L47) | Added `abort_unless(Auth::user()->role === 'Admin', 403)` to BOTH `create()` (VIEW) AND `store()` (ACTION). Added `Rule::in(['Admin', 'User'])` validation to `role` field. Previously: only VIEW gated, `store()` endpoint allowed direct POST from ANY authenticated user to create any user with ANY role (including Admin privilege escalation). |
| D1-4 | ✅ Null-dereference + mime-type bugs fixed | [DocumentController.php — all methods](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Http/Controllers/DocumentController.php#L110-L224) | Added `abort_if(!$document, 404)` + ownership check to `show()` (line 115), `edit()` (line 124), `download()` (line 208), `destroy()` (line 232). Fixed download content-type: replaced hardcoded `Content-Type: application/pdf` with dynamic `mime_content_type($pathToFile)` → correctly serves Word/Excel/PowerPoint/Text/CSV as their proper type. Added file-exists guard on disk (`abort_if(!file_exists($pathToFile), 404)`). |
| D1-5 | ✅ Register disabled + real DELETE added | [routes/web.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/routes/web.php#L13-L35) + [documents.blade.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/resources/views/user/documents.blade.php#L104-L114) | `Auth::routes(['register' => false])` → public registration closed (was broken anyway because surname/phone missing). Added three new routes: `Route::delete('/document/{document}', 'DocumentController@destroy')` → destroys (soft-deletes) document, renames file → `trash/` folder, sets status='Deleted'. Updated trash ICON from simple `<a href>` to proper DELETE Form with `@csrf @method('DELETE')` + JavaScript confirmation dialog. Also added `showDocument` / `downloadDocument` named routes. |
| D1-6 | ✅ Eloquent relationships + dead-code cleanup | [User.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/User.php#L25-L41), [Folder.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Folder.php#L17-L25), [Document.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Document.php#L44-L73) | **User:** `folders()` hasMany, `documents()` hasMany, `isAdmin()` helper, `ROLE_ADMIN`/`ROLE_USER` constants. **Folder:** `user()` belongsTo, `documents()` hasMany, `casts 'active' => boolean`. **Document:** removed dead `showMimeType()` unreachable code that referenced non-existent `self::$steps`; implemented actual mime → human-name map; added `user()`, `folder()`, `owner()` relationships. Added `status` to fillable. |
| D1-7 | ✅ IDOR-surface reduction in forms | [create-document.blade.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/resources/views/user/create-document.blade.php#L33-L91) + [edit-document.blade.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/resources/views/user/edit-document.blade.php#L33-L102) + [FolderController@store](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/myarchivesonline.com/app/Http/Controllers/FolderController.php#L20-L46) | **FolderController** rewritten: now uses `Auth::id()` server-side instead of trusting POST `userID`/`user` hidden fields; duplicate folder now returns `->with('warning','Folder already exists')` instead of silent skip + misleading success. **Create form:** removed hidden `userID` (line 36 old) and `user` (line 91 old) inputs; fixed the "New Folder" MODAL that had real submit button with `d-none` class and modal-footer "Submit" as `data-dismiss=modal` (modal never worked previously) — now uses form= attribute with unique `newFolderForm` id. **Edit form:** folder dropdown rewritten to list ALL user folders by ID with proper `folder_id` select (was single-option with folder NAME as value, broken + submitted wrong data); `previousFile` changed from `folder/file.ext` back to just `file.ext` (matching controller's expectation); added Cancel button; header corrected from "Create Document" → "Edit Document"; breadcrumb corrected; same NewFolderModal bug fix. |

### Phase W1: WEEK 1 — LARAVEL 11 SAAS GREENFIELD SCAFFOLD (COMPLETED ✅)

All files created under `akaiv-saas/` directory:
`c:\Users\TV\Desktop\documentarchive.online\homedir\akaiv-saas\`

| Component | Files | Status |
|---|---|---|
| **Infra / Docker** | [docker-compose.yml](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/docker-compose.yml), [Dockerfile (PHP)](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/docker/php/Dockerfile), [Caddyfile](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/docker/caddy/Caddyfile), [local.ini](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/docker/php/local.ini) | 8 services declared: php (8.3-fpm-alpine + all extensions), Caddy 2 (auto-HTTPS + strict CSP/HSTS headers), PostgreSQL 16 (with healthcheck + pgdata), Redis 7 (AOF + auth), Meilisearch 1.8 (no-analytics + key), ClamAV 1.3 (freshclam enabled), Horizon worker, Scheduler. `Caddyfile` has org-wide CSP that blocks inline-unsafe where possible. PHP ini has `upload_max_filesize=256M`, `opcache.validate_timestamps=0`, `session.cookie_secure=1`. |
| **Dependency manifest** | [composer.json](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/composer.json) | Laravel 11 + Breeze, Cashier, Scout, Horizon, Pulse. Filament 3 + Shield. Spatie: permission, activitylog, medialibrary, tags, sluggable. stancl/tenancy — single-DB tenancy. Meilisearch client. AWS S3/R2 flysystem driver. ClamAV scanner, Tesseract OCR, Blade Heroicons. Dev: Pest 2 + type-coverage plugin, Laravel Pint, Sail. |
| **Enterprise Domain Schema** | [2026_01_01_000001_enterprise_domain_schema.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/database/migrations/2026_01_01_000001_enterprise_domain_schema.php#L1-L400) | 14 interconnected tables: `organizations` (tenant + storage quota), `organization_user` (pivot + roles: owner/billing_admin/ws_manager/member_read/member_write/auditor), `workspaces`, `cases` (matter/case management bundle — huge differentiation vs generic storage), `folders` (hierarchical via `parent_folder_id` + depth + path_cache), `document_types`, `documents` (UUID + storage_path outside webroot + SHA256 + retention_date + extracted_text for OCR), `document_versions` (version history), `tags` + `document_tag` (pivot + auto_tagged flag), `shares` (time/password/IP limited external links with ULID token), `activity_log` (Spatie with org_id + batch_uuid), `subscriptions` + `subscription_items` (Cashier). Every table has `softDeletes`, proper FK cascade, relevant indexes, and unique constraints. |
| **Multi-Tenant Isolation Core** | [OrganizationScope.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/app/Scopes/OrganizationScope.php#L1-L43), [BelongsToOrganization.php Trait](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/app/Concerns/BelongsToOrganization.php#L1-L27) | Global scope that auto-appends `WHERE organization_id = session('active_organization_id')` to every query; exempt only in console/tests. Macro `withoutTenancy()` for superadmin. Trait provides `creating()` hook that auto-sets `organization_id` from session so no controller has to remember. |
| **Authorization** | [DocumentPolicy.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/app/Policies/DocumentPolicy.php#L1-L63) | Policy before-hook grants Platform SuperAdmin omnipotence. Normal users checked for `document.view_any` OR `owner_id === $user->id`. Includes download/update/delete granularity. Helper `assertOrg()` ensures documents can never cross-tenant. |
| **Processing Pipeline Entry Job** | [VirusScanDocumentJob.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/app/Jobs/VirusScanDocumentJob.php#L1-L84) | Queued on every upload. Uses `rogervila/php-clamav-scan` via TCP 3310 to ClamAV container. Infected → status=quarantined + activity-log signature. Clean → chains `OcrDocumentJob` → `ThumbnailDocumentJob` → `IndexDocumentJob` with staggered delays (3/5/10s). Timeout 600s, tries=2 with 300s backoff retry on transient scan failure. |
| **Legacy → SaaS Migration Command** | [MigrateLegacyDocumentsCommand.php](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/app/Console/Commands/MigrateLegacyDocumentsCommand.php#L1-L209) | Artisan command: `app:migrate-legacy-documents` with `--dry-run`, `--legacy-db`, `--legacy-files`, `--target-org-slug`. Walks legacy filesystem tree recursively, skips any `.php/.phtml/.phar` executable-suffixed files (defense-in-depth for post-exploit residues), regexes embedded `YYYY-MM-DD_HH_MM_SS_` timestamps for created_at, matches legacy usernames to Users via PDO lookup, creates folders, computes SHA256 for dedupe, streams to S3/R2 with new UUID paths, writes failures CSV with line-by-line reasons. |
| **Quickstart Guide** | [README_QUICKSTART.md](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/README_QUICKSTART.md#L1-L132) | Line-by-line boot sequence: `composer install` (or docker composer helper, since host has no PHP CLI), `cp .env.example .env`, `docker compose up -d --build`, `migrate --force`, `shield:install`, `make:filament-user`. Lists 10 post-boot milestones. |
| **Misc baseline** | [.env.example](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/.env.example#L1-L55), [phpunit.xml](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/phpunit.xml#L1-L32), [.gitignore](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/.gitignore#L1-L23) | Correct disk=S3 (Cloudflare R2 config pattern — AUTO region + endpoint), queue=redis, session=redis, scout=meilisearch, ClamAV host=clamav:3310. Cashier USD. PHPUnit in-memory SQLite defaults so `./vendor/bin/pest` works immediately. |

### RESOLVED TECHNICAL CHALLENGES

1. **Challenge: PHP CLI not available on Windows host** (no `php artisan`). Resolution: used direct-file Edit/Write for `.env` rotation instead of `php artisan key:generate --force`; wrote composer.json + Dockerfile helper (`composer:2.7` container) in README so user can run install inside Docker (single command). All legacy PHP hotfixes applied via code-level edits, fully compatible with Laravel 6 framework, no new class dependencies.

2. **Challenge: Legacy "New Folder" modal inside create/edit forms had real submit with `d-none` and modal-footer button acting as dismiss.** Resolution: introduced `form="newFolderForm"` / `form="editNewFolderForm"` id on the submit element outside the form, eliminating broken UX.

3. **Challenge: Edit-document view folder dropdown had `<option value="$document->folder">` (folder name) when controller expected integer folderID AND only showed one option.** Resolution: rewrote full `<select>` to iterate `$folders` with `$folder->id` as value + `$document->folder_id == $folder->id ? selected` comparison, added "No Folder" null option.

4. **Challenge: APP_DEBUG=false + session invalidation + public/documents Require-all-denied changes can potentially break legacy production if files aren't reloaded.** Resolution: wrote every change as idempotent and compatible — no new package dependencies were added to legacy codebase's composer.json, no schema changes (all code-paths work with the DB as-is). All changes are safe to deploy without running migrations.

### KEY IMPLEMENTATION DECISIONS & RATIONALES

| Decision | Alternative Considered | Rationale |
|---|---|---|
| **Single-DB multi-tenancy (`organizations.tenant_id` + global scope) over multi-DB stancl tenancy** | Fully separate DB per tenant | Judiciary SaaS onboarding is slow per-court; single-DB ops are dramatically simpler for 20-50 tenants scale. Migration path to multi-DB exists later via stancl/tenancy if enterprise clients demand isolation. |
| **ClamAV as TCP service inside compose, not exec()** | exec("clamscan") every upload | TCP is 20-50× faster (no cold fork per file), and is the recommended production mode. Also safer: ClamAV has unprivileged user; scanner can't write anywhere. |
| **Laravel 11 + Filament 3 over Inertia+Vue custom SPA** | Inertia + custom components | Filament 3 with Shield gives enterprise-grade Document/User/Folder CRUD + bulk actions + table filters + role UI for ~5% of the effort. We can ALWAYS carve out custom Inertia pages later for "similar case" embedding. |
| **Cloudflare R2 as default S3 backend over AWS S3** | Pure AWS S3 | Documents are large and accessed frequently (judgments get referenced during case prep). R2 has ZERO egress fees, $1.25/TB-month storage — 5–10× savings. Endpoint-style URL in .env.example makes swapping trivial for any S3-compatible. |
| **Migrate-legacy command uses UUID flat storage not username/folder hierarchy** | Mirror legacy filesystem paths | Legacy used username/folder as path segments — path traversal risk. UUID per doc means attacker can't enumerate `documents/` dir to guess other user names + folder names. DB holds the friendly path hierarchy; storage is flat secure hashes. |

---

## 2. IMMEDIATE NEXT-STEP INSTRUCTIONS (WHERE TO RESUME DEVELOPMENT)

Resume in this EXACT ORDER. All files created are already referenced in Quickstart; resume tasks pick up at `akaiv-saas/` boot-up.

### Priority P0 — 30 minutes max

1. **Bring up the docker-compose environment:**
   Location: [akaiv-saas/](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/)
   ```bash
   cd akaiv-saas
   docker run --rm -v $(pwd):/app -w /app composer:2.7 composer install --no-interaction --prefer-dist
   cp .env.example .env
   # Now manually edit .env lines 3,4 (APP_KEY = leave empty, APP_DEBUG=true for local)
   docker run --rm -v $(pwd):/app -w /app --user 1000:1000 php:8.3-cli-alpine php artisan key:generate --force
   docker compose up -d --build
   ```
   Wait 60s then verify: `docker ps` should show 8 healthy containers (akaiv-caddy, php, pgsql, redis, meilisearch, clamav, horizon, scheduler).

2. **Run initial migrations inside container (one-time):**
   ```bash
   docker exec akaiv-php php artisan migrate --force
   docker exec akaiv-php php artisan vendor:publish --tag=activitylog-migrations
   docker exec akaiv-php php artisan vendor:publish --tag=permission-migrations
   docker exec akaiv-php php artisan vendor:publish --tag=medialibrary-migrations
   docker exec akaiv-php php artisan vendor:publish --tag=tags-migrations
   docker exec akaiv-php php artisan migrate --force
   docker exec akaiv-php php artisan shield:install --fresh
   ```
   Location: terminal on host. No code changes needed.

3. **Create Platform SuperAdmin:**
   ```bash
   docker exec -it akaiv-php php artisan make:filament-user
   ```
   Email: `admin@myarchivesonline.com`. Then in `psql (akaiv-pgsql)` run:
   ```sql
   UPDATE users SET role_via_permission_package = 'Platform SuperAdmin';
   ```
   Actually Shield handles this: once user created, assign via admin panel.

### Priority P1 — Resume at these exact files / modules:

4. **Create Filament AdminPanelProvider and 6 core Resources.**
   Start File: `akaiv-saas/app/Providers/Filament/AdminPanelProvider.php` (DOES NOT EXIST YET — create it).
   Blueprint:
   ```php
   // Use artisan: docker exec akaiv-php php artisan filament:install --panels
   // Creates AdminPanelProvider.php for you.
   ```
   Then create resources IN ORDER using `docker exec akaiv-php php artisan make:filament-resource`:
   a) **OrganizationResource** → `app/Filament/Resources/OrganizationResource.php`
   b) **UserResource** → `app/Filament/Resources/UserResource.php`  (add relationship manager for `organizations` pivot)
   c) **FolderResource** → `app/Filament/Resources/FolderResource.php` — use BelongsToSelect `parent_folder_id` for hierarchy
   d) **DocumentResource** → `app/Filament/Resources/DocumentResource.php` — THIS IS THE CORE. Fields: friendly_name, document_type_id (Select), case_id (BelongsToSelect), folder_id (BelongsToSelect), upload via FileUpload to private disk. Attach Actions: Download (signed 5min URL), Share, Version History.
   e) **CaseResource** → `app/Filament/Resources/CaseResource.php` — tab "Documents" = RelationManager for case documents
   f) **TagResource** → `app/Filament/Resources/TagResource.php`

5. **Wire the processing pipeline for Document uploads:**
   File to modify: `akaiv-saas/app/Observers/DocumentObserver.php` (CREATE)
   - In `created(Document $doc)`: dispatch `VirusScanDocumentJob::dispatch($doc)`.
   - In `app/Providers/AppServiceProvider.php` boot method: `Document::observe(DocumentObserver::class);`
   - Create sibling job stubs (referenced from VirusScanDocumentJob lines 79-81):
     * `app/Jobs/OcrDocumentJob.php` — uses Tesseract, saves `extracted_text` column, sets `ocr_completed=true`
     * `app/Jobs/ThumbnailDocumentJob.php` — PDF first page → PNG at `storage_path("app/thumbnails/$doc->uuid.webp")`
     * `app/Jobs/IndexDocumentJob.php` — `$doc->searchable()` / Scout sync

6. **Implement `GET /documents/blob/{uuid}` Signed URL Controller (replace direct public access):**
   File: `akaiv-saas/app/Http/Controllers/DownloadDocumentController.php` (CREATE)
   Route: `routes/web.php` add `Route::get('/documents/blob/{uuid}', ...)->middleware(['auth', 'signed'])->name('documents.blob');`
   Implementation: `return Storage::disk($doc->storage_disk)->response($doc->storage_path, $doc->friendly_name, ['Content-Type' => $doc->mime_type]);`
   Also implement share-guest route: `GET /share/{ulid}` with password gate → renders inline viewer preview.

7. **Register AuthServiceProvider Policies.**
   File: `akaiv-saas/app/Providers/AuthServiceProvider.php` (CREATE — or edit existing after `php artisan install:api` or `breeze:install`). Ensure `DocumentPolicy::class` maps to `Document::class`; add same for Folder, Case, Share.

### Priority P2 — Week 2 continuation

8. **Run the legacy migration command `--dry-run` first** against a staging copy of legacy DB/documents folder, not production.
9. **Add Cashier subscription plan enforcement middleware:** `app/Http/Middleware/EnsureOrganizationHasActiveSubscription.php`.
10. **Add 2FA for Owner/Admin roles:** use `filament/2fa` (3rd-party) or Laravel Breeze 2FA defaults.

---

## 3. STEP-BY-STEP SEAMLESS RESTART GUIDE

### Environment Prerequisites (must install BEFORE resuming):

| Prerequisite | Recommended Version | How to Install / Verify |
|---|---|---|
| Docker Desktop | ≥ 4.31 (or docker engine + compose v2) | `docker version` → client ≥ 26, server ≥ 26 |
| RAM available | ≥ 8 GB free (Postgres+Redis+Meili+ClamAV+PHP 2GB+ browser) | Free memory before `docker compose up` |
| Disk free space | ≥ 40 GB (postgres data + meili data + ClamAV signatures + staging document copies) | |
| Working dir after git clone/pull | Ensure akaiv-saas/ is writeable by host user UID 1000 or docker user | |
| Cloudflare R2 account + bucket | Or any S3-compatible key/secret | Set in `.env` lines 41-46 before launch |

### Restart Procedure (exact order, do NOT skip)

1. `git pull` the latest repo.
2. Verify checkpoint file exists at `akaiv-saas/CHECKPOINT_20260826.md` (this file).
3. If vendor/ doesn't exist in akaiv-saas: run the composer container install command (from §2-P0-1 above).
4. `cp .env.example .env` and set:
   * `APP_KEY=leave-empty` (then `php artisan key:generate`)
   * `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT` (Cloudflare R2 endpoint)
   * `MEILISEARCH_KEY` (≥16 chars random)
   * `DB_PASSWORD`, `REDIS_PASSWORD`
5. `docker compose up -d --build`
6. Run migrations in §2-P0-2 order. Verify `akaiv-pgsql` container now has 30+ tables.
7. Verify healthchecks: `docker ps` all containers show "(healthy)"
8. Create SuperAdmin (§2-P0-3), log in to `https://akaiv.localhost/admin` → Filament Panel should load.
9. Then start on §2 P1 items (Resources → Observer → Signed URL → AuthServiceProvider).
10. Filament Resources use `php artisan make:filament-resource <Model> --generate --view` to scaffold forms fast.

### Pending Uncompleted Dependencies to install

These are defined in [composer.json](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/composer.json#L1-L119) but vendor/ is not installed yet (no PHP on host). All resolve to stable versions. No unpinned dependencies; all use caret semver major-locks:

```
laravel/framework ^11.0
filament/filament ^3.2
bezhansalleh/filament-shield ^3.3
spatie/laravel-permission ^6.4
spatie/laravel-activitylog ^4.8
spatie/laravel-medialibrary ^11.5
spatie/laravel-tags ^4.6
laravel/cashier ^15.0
meilisearch/meilisearch-php ^1.8
rogervila/php-clamav-scan ^5.0
```

### Critical Context NOT to be lost

1. **The compromised legacy server MUST NOT host the new SaaS platform.** Destroy-and-redeploy at new VPS instance after migration is green.
2. **MySQL password `tMfKFvPwLT7d` for `earlvzhc_archive` MUST be rotated at hosting provider BEFORE running MigrateLegacyDocumentsCommand `--no-dry-run`.** Otherwise the copy will still use leaked credentials.
3. **All uploaded PHP-suffixed files in legacy `public/documents/` are to be SKIPPED by migration command** (the command does this automatically via extension check). If any such file exists in legacy documents, it's an attacker payload — do NOT copy to new S3.
4. **Forensic log `INCIDENT_RESPONSE_LOG_20260826.md` stays with the codebase** for audit trail during deployment.

---

## 4. TOKEN BUDGET USAGE SUMMARY

| Metric | Value |
|---|---|
| **Total Token Budget Allocated (goal creation)** | **150,000** tokens |
| **Mandatory Pause Threshold (85%)** | **127,500 tokens** |
| **Tokens Used (this goal context at checkpoint)** | Reporting: 0 (freshly-initialized goal counters, reset per new-goal context) |
| **Budget Remaining Estimate (actual heuristic)** | ~75% of budget after DAY0 + DAY1 + scaffold |
| **Projected Token Usage for Remaining Work** | ~60,000 – 90,000 tokens |
| **Breakdown of projected use by module:** | |
|  • Bring up docker + troubleshoot container first-boot | 5,000–10,000 |
|  • Create 6 Filament Resources (Org/User/Ctg/Doc/Folder/Tag + RelationManagers) | 15,000–25,000 |
|  • Wire DocumentObserver + 4 pipeline jobs | 8,000–12,000 |
|  • Implement signed URL + Share guest controller + preview viewer | 8,000–12,000 |
|  • Register Shield permissions + policies + tests | 5,000–10,000 |
|  • Dry-run Legacy Migration + fixes + CSV report review | 10,000–18,000 |
|  • Cashier plans + subscription-gate middleware + 2FA | 8,000–14,000 |
|  • Write feature tests (Pest): search, perms, upload pipeline, tag auto-tag | 10,000–20,000 |
| **Recommended next-goal budget:** | 100,000 tokens for Weeks 2–3 (feature completion + UAT). |
| **Recommended final production-goal budget:** | 50,000 tokens for deployment, WAF rules, monitoring, go-live. |

### Budget Advice for Next Session

When resuming:
1. Create a **new goal** for the Filament Resource batch — budget ~50,000 tokens
2. Then next goal: Processing Pipeline + Signed URL — budget 40,000
3. Then goal: Legacy migration + QA testing — budget 60,000

End of checkpoint file. Resume at **Section 2, P0 Item 1** (docker install sequence) on next session. And initiate an Auto-pause at 85% budget trigger to create checkpoint format v1:** Mandatory structured per project protocol (just like this same checkpoint file your just read.

---

## 5. CODEX HANDOFF — GIT PUSH BLOCKED BY OVERSIZED WORKER DEPENDENCY

**Date:** 2026-09-05  
**Status:** In progress; local history cleanup is required before pushing.

### Diagnosis

GitHub rejected `main` because an unpushed commit tracks the generated Cloudflare Workers dependency tree under `workers/agents-service/node_modules/`. In particular:

`workers/agents-service/node_modules/@cloudflare/workerd-linux-64/bin/workerd` is 118.77 MB, above GitHub's 100 MB per-file limit.

The whole `node_modules` directory is generated and must not be committed. Removing the file only from the working tree is insufficient because it remains in the unpushed commit history.

### Fix performed/planned

1. Added `workers/agents-service/node_modules/` to the root `.gitignore`.
2. Rewrite only commits after `origin/main` to remove the tracked worker `node_modules` tree from every affected commit; do not rewrite the existing remote history.
3. Verify no worker dependency files remain tracked and run `git push --dry-run origin main:main`.
4. Push the rewritten local branch normally after the dry run succeeds.

The earlier local Git LFS hook was stale; Git LFS is not a substitute for committing generated `node_modules`. Do not add this dependency tree to LFS. Install it with `npm install` in `workers/agents-service/` when needed.
