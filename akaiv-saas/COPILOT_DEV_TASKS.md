# COPILOT DEVELOPMENT TASKS — AKAIV SaaS Phase 2

**Issued:** 2026-09-04  
**Issuer:** Master Project Orchestrator (OpenCode)  
**Executor:** GitHub Copilot Agent (Codespace)  
**Branch:** `main`

---

## 🗺️ ARCHITECTURAL BRIDGE STATE

| Domain | Status |
|--------|--------|
| **Legacy Base** | `myarchivesonline.com/` — Laravel 6, incident-remediated, pending credential rotation |
| **Cloud Extension** | `akaiv-saas/` — Laravel 11, Filament 3, Docker Compose (8 services), Codespace |
| **Completed** | DocumentResource, AdminPanelProvider (Shield), 4 pipeline jobs, DocumentObserver, PreviewDocumentController, DocumentAgentController, Vite/Tailwind, 2 tests |
| **In Progress** | Phase 2 — Remaining resources, policies, tests, download/share flows |
| **Not Started** | Legacy migration dry-run, Worker deployment, CI/CD |

### Verified Implementation Inventory

| Component | Status | Evidence |
|-----------|--------|----------|
| DocumentResource | ✅ DONE | `app/Filament/Resources/DocumentResource.php` (120 lines) |
| AdminPanelProvider | ✅ DONE | `app/Providers/Filament/AdminPanelProvider.php` (Shield plugin registered) |
| VirusScanDocumentJob | ✅ DONE | `app/Jobs/VirusScanDocumentJob.php` |
| OcrDocumentJob | ✅ DONE | `app/Jobs/OcrDocumentJob.php` (pdftoppm + Tesseract) |
| ThumbnailDocumentJob | ✅ DONE | `app/Jobs/ThumbnailDocumentJob.php` |
| IndexDocumentJob | ✅ DONE | `app/Jobs/IndexDocumentJob.php` (Scout) |
| DocumentObserver | ✅ DONE | `app/Observers/DocumentObserver.php` |
| PreviewDocumentController | ✅ DONE | `app/Http/Controllers/PreviewDocumentController.php` |
| DocumentAgentController | ✅ DONE | `app/Http/Controllers/DocumentAgentController.php` |
| Vite/Tailwind | ✅ DONE | `vite.config.js`, `tailwind.config.js`, `package.json` |
| Brand assets | ✅ DONE | `public/images/akaiv-logo.svg`, `akaiv-mark.svg` |
| Cloudflare Worker | ✅ DONE | `workers/agents-service/src/index.ts` |
| Tests | ⚠️ PARTIAL | `DocumentPolicyTest`, `PreviewDocumentTest` only |

### Confirmed Gaps (Evidence-Based)

| Gap ID | Missing Component | Evidence | Priority |
|--------|-------------------|----------|----------|
| G-01 | OrganizationResource | `glob app/Filament/Resources/*` = only DocumentResource | CRITICAL |
| G-02 | UserResource | Same glob | CRITICAL |
| G-03 | FolderResource | Same glob | CRITICAL |
| G-04 | CaseResource | Same glob | CRITICAL |
| G-05 | TagResource | Same glob | CRITICAL |
| G-06 | FolderPolicy, CasePolicy, SharePolicy, OrganizationPolicy | `glob app/Policies/*` = DocumentPolicy, RolePolicy only | HIGH |
| G-07 | AuthServiceProvider (policy registration) | `glob app/Providers/*` = AppServiceProvider only | HIGH |
| G-08 | DownloadDocumentController (signed download) | Only Preview controller exists | HIGH |
| G-09 | Share guest controller (password/IP/expiry) | `routes/web.php` has no share route | HIGH |
| G-10 | Tenant isolation tests | `glob tests/**` = 2 tests only | HIGH |
| G-11 | Pipeline job tests | No job tests | HIGH |
| G-12 | Legacy migration command tests | No command tests | MEDIUM |
| G-13 | Worker endpoint tests | No Worker tests | MEDIUM |
| G-14 | CI/CD pipeline | `glob .github/workflows/**` = 0 | MEDIUM |

---

## 📤 COPILOT_MISSION_INJECTION

Copy and paste the block below directly into the cloud Copilot Agent task list:

```markdown
[COGNITIVE ARCHITECT MANDATE]
- Objective: Complete AKAIV SaaS Phase 2 — remaining Filament resources, authorization policies, download/share flows, and test coverage.
- Constraints: 
  - Preserve tenant isolation (OrganizationScope + BelongsToOrganization)
  - Follow existing DocumentResource pattern for all new resources
  - No permanent storage URLs — signed/temporary only
  - All policies must enforce organization boundaries
- Structural Blueprint:
  - app/Filament/Resources/{Organization,User,Folder,Case,Tag}Resource.php + Pages/
  - app/Policies/{Folder,Case,Share,Organization}Policy.php
  - app/Providers/AuthServiceProvider.php
  - app/Http/Controllers/DownloadDocumentController.php
  - app/Http/Controllers/ShareController.php
  - tests/Unit/, tests/Feature/ (tenant isolation, pipeline, migration)
- Execution Workflow: (see task list below, execute in order)
- Success Criteria: (see per-task verification below)
```

---

## TASK LIST (Execute In Order)

### PHASE 2A — Filament Resources (CRITICAL)

#### TASK-201: OrganizationResource
**Files:**
- `app/Filament/Resources/OrganizationResource.php`
- `app/Filament/Resources/OrganizationResource/Pages/{ListOrganizations,CreateOrganization,EditOrganization}.php`

**Requirements:**
- Form: name, slug, registration_number, court_type (select), jurisdiction_state, contact_email, contact_phone, address, plan (select: free/pro/enterprise), storage_quota_bytes
- Table: name, slug, court_type, plan, storage_usage (computed %), users count, created_at
- Filters: plan, court_type, is_suspended
- Actions: Edit, Suspend/Reactivate
- RelationManager: `users` (organization_user pivot with role display)

**Success Criteria:**
- `php artisan route:list | grep organizations` shows index/create/edit
- Resource list page returns HTTP 200
- Create form persists Organization with auto-slug

---

#### TASK-202: UserResource
**Files:**
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/UserResource/Pages/{ListUsers,CreateUser,EditUser}.php`

**Requirements:**
- Form: name, email, password (Reveal on edit only), timezone, language
- Table: name, email, organizations (badge list), roles, created_at
- RelationManager: `organizations` (pivot with role)
- Guard: Only Platform SuperAdmin can create/delete users

**Success Criteria:**
- User CRUD works; password hashed on create
- Organization memberships display correctly
- Non-SuperAdmin cannot access create/delete

---

#### TASK-203: FolderResource
**Files:**
- `app/Filament/Resources/FolderResource.php`
- `app/Filament/Resources/FolderResource/Pages/{ListFolders,CreateFolder,EditFolder}.php`

**Requirements:**
- Form: name, parent_folder_id (self-referential BelongsToSelect, org-scoped), workspace_id, case_id, is_system
- Table: name, parent (breadcrumb path), workspace, case, depth, documents count
- Validate unique(org_id, workspace_id, parent_folder_id, name) constraint
- Auto-compute depth + path_cache on save

**Success Criteria:**
- Hierarchical folder creation works
- Parent dropdown shows only same-org folders
- Duplicate name in same parent returns validation error

---

#### TASK-204: CaseResource (CaseFile model)
**Files:**
- `app/Filament/Resources/CaseResource.php`
- `app/Filament/Resources/CaseResource/Pages/{ListCases,CreateCase,EditCase}.php`
- `app/Filament/Resources/CaseResource/RelationManagers/DocumentsRelationManager.php`

**Requirements:**
- Form: case_number, suit_number, title, parties (repeater: role + name), court_name, bench_judge_name, jurisdiction, date_filed, date_judgment, status, notes
- Table: case_number, title, court_name, status, date_filed, documents count
- RelationManager: Documents tab

**Success Criteria:**
- Case CRUD works
- Parties JSON accessor/mutator round-trips correctly
- Documents RelationManager lists case documents

---

#### TASK-205: TagResource
**Files:**
- `app/Filament/Resources/TagResource.php`
- `app/Filament/Resources/TagResource/Pages/{ListTags,CreateTag,EditTag}.php`

**Requirements:**
- Form: name, slug (auto), color (color picker), is_system
- Table: name, slug, color, documents count, created_at
- Org-scoped unique(slug)

**Success Criteria:**
- Tag CRUD works; slug auto-generated
- Color picker persists hex value

---

### PHASE 2B — Authorization Policies (HIGH)

#### TASK-206: Create Missing Policies
**Files:**
- `app/Policies/FolderPolicy.php`
- `app/Policies/CasePolicy.php`
- `app/Policies/SharePolicy.php`
- `app/Policies/OrganizationPolicy.php`

**Requirements (mirror DocumentPolicy pattern):**
- `before()` hook: Platform SuperAdmin → true
- `assertOrg()` helper: session('active_organization_id') === model.organization_id
- view/viewAny/create/update/delete methods
- Folder: inherit permission from parent if needed
- Share: enforce document ownership + org boundary
- Organization: only owner or SuperAdmin

**Success Criteria:**
- Cross-tenant access returns 403
- SuperAdmin bypasses all checks
- Unit tests pass

---

#### TASK-207: AuthServiceProvider
**Files:**
- `app/Providers/AuthServiceProvider.php`

**Requirements:**
- Register all policies: Document, Folder, Case, Share, Organization, Role
- Register in `bootstrap/providers.php` or `config/app.php`

**Success Criteria:**
- `php artisan about` shows AuthServiceProvider loaded
- Policy auto-discovery verified

---

### PHASE 2C — Download & Share Flows (HIGH)

#### TASK-208: DownloadDocumentController
**Files:**
- `app/Http/Controllers/DownloadDocumentController.php`
- Route: `GET /documents/{document}/download` (signed + auth)

**Requirements:**
- Authorize via `$this->authorize('download', $document)`
- Stream from private S3/R2 disk
- Increment download_count
- Log activity
- Return proper Content-Type + Content-Disposition

**Success Criteria:**
- Signed URL required (unsigned → 403)
- Cross-tenant download → 403
- download_count increments
- Correct MIME type served

---

#### TASK-209: ShareController (Guest Access)
**Files:**
- `app/Http/Controllers/ShareController.php`
- Route: `GET /share/{token}` (public, ULID token)

**Requirements:**
- Resolve Share by ULID token
- Check expiry, IP allowlist, max_accesses
- Password gate (if password_hash set) → form POST
- Increment access_count
- Inline preview if can_preview, download if can_download
- Log access

**Success Criteria:**
- Expired share → 410 Gone
- Wrong password → 403 with re-prompt
- IP not allowed → 403
- Max accesses exceeded → 410
- Valid access renders preview/increments count

---

### PHASE 2D — Test Coverage (HIGH)

#### TASK-210: Tenant Isolation Tests
**Files:** `tests/Feature/TenantIsolationTest.php`

**Test Cases:**
- User in Org A cannot query Org B documents
- `withoutTenancy()` macro returns all
- `organization_id` auto-set on create
- Console context bypasses scope
- Cross-tenant policy check → 403

**Success Criteria:** `./vendor/bin/pest --filter=TenantIsolation` passes

---

#### TASK-211: Pipeline Job Tests
**Files:** `tests/Unit/VirusScanDocumentJobTest.php`, `tests/Unit/OcrDocumentJobTest.php`, `tests/Unit/ThumbnailDocumentJobTest.php`

**Test Cases:**
- Infected file → status=quarantined, virus_found=true
- Clean file → status=published, 3 jobs dispatched
- Missing file → quarantined
- ClamAV exception → released with backoff
- OCR skips if ocr_completed
- Thumbnail generates for PDF only

**Success Criteria:** `./vendor/bin/pest --filter=Job` passes

---

#### TASK-212: Migration Command Tests
**Files:** `tests/Feature/MigrateLegacyDocumentsCommandTest.php`

**Test Cases:**
- `--dry-run` produces no DB writes
- PHP executable files skipped
- SHA256 dedup detection
- Timestamp parsing from filename
- CSV failure report generated

**Success Criteria:** `./vendor/bin/pest --filter=MigrateLegacy` passes

---

### PHASE 2E — Worker Integration (MEDIUM)

#### TASK-213: Worker Tests
**Files:** `workers/agents-service/test/index.test.ts`

**Test Cases:**
- Missing auth token → 401
- Invalid token → 401
- Valid request → 200 with analysis
- Durable Object state persists per document
- CORS headers correct

**Success Criteria:** `npm test` in `workers/agents-service/` passes

---

#### TASK-214: Laravel-Worker Bridge Tests
**Files:** `tests/Feature/DocumentAgentControllerTest.php`

**Test Cases:**
- Unauthorized user → 403
- Worker not configured → 500 with clear message
- Worker 5xx → 502 returned
- Successful analysis → 200 with JSON
- Throttle limit enforced (30/min)

**Success Criteria:** `./vendor/bin/pest --filter=DocumentAgent` passes

---

### PHASE 2F — CI/CD (MEDIUM)

#### TASK-215: GitHub Actions Pipeline
**Files:** `.github/workflows/ci.yml`

**Requirements:**
- Trigger: push + PR to main
- Jobs: lint (Pint), test (Pest), type-coverage, Worker build
- Services: PostgreSQL 16, Redis 7
- Cache: composer + npm

**Success Criteria:** Workflow passes on push; badge green

---

## EXECUTION ORDER & DEPENDENCIES

```
TASK-206 (Policies) ──┐
TASK-207 (AuthProvider)┘
        │
        ▼
TASK-201..205 (Resources) ──► TASK-208 (Download) ──► TASK-209 (Share)
        │
        ▼
TASK-210..212 (Laravel Tests)
        │
        ▼
TASK-213..214 (Worker Tests)
        │
        ▼
TASK-215 (CI/CD)
```

---

## VERIFICATION COMMANDS (Run After Each Task)

```bash
# Syntax check
docker exec akaiv-php php -l app/Filament/Resources/<Resource>.php

# Route registration
docker exec akaiv-php php artisan route:list | grep <resource>

# Tests
docker exec akaiv-php ./vendor/bin/pest --filter=<TestName>

# Full suite
docker exec akaiv-php ./vendor/bin/pest

# Style
docker exec akaiv-php ./vendor/bin/pint --test
```

---

## DEFINITION OF DONE (Phase 2)

| Criterion | Verification |
|-----------|--------------|
| All 6 Filament Resources exist | `ls app/Filament/Resources/*.php` = 6 files |
| All policies registered | `php artisan about` + cross-tenant 403 test |
| Download flow works | Signed URL returns file, count increments |
| Share flow works | Password/IP/expiry gates enforced |
| Test coverage ≥80% | `./vendor/bin/pest --coverage` |
| Worker deployed | `wrangler deploy` succeeds |
| CI green | GitHub Actions passing |

---

## REPORT BACK FORMAT

After each task, report:
```
TASK-<ID>: <STATUS>
Files: <paths>
Command: <verification command>
Result: <output summary>
Blockers: <none | description>
```
