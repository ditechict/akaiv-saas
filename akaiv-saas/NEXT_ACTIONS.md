# NEXT_ACTIONS.md — AKAIV SaaS Evidence-Backed Recommendations

**Generated:** 2026-09-04  
**Method:** Evidence-based repository audit per Deep Architecture Discovery Agent protocol

---

## Priority Ranking

```
CRITICAL → Blocks production / security incident / data loss
HIGH     → Blocks major feature / significant technical debt
MEDIUM   → Improves reliability / maintainability / developer experience
LOW      → Nice to have / future-proofing
```

---

## CRITICAL Actions

### ACTION-001: Rotate Leaked Legacy Database Credentials
| Field | Value |
|-------|-------|
| **Problem** | Legacy `myarchivesonline.com/.env` contains `DB_PASSWORD=tMfKFvPwLT7d` — confirmed leaked in INCIDENT_RESPONSE_LOG_20260826 |
| **Evidence** | Analysis Archive BLOCKER-01; INCIDENT_RESPONSE_LOG_20260826 §5 Item 1; `.env` line 15 |
| **Impact** | Attacker can connect to MySQL `earlvzhc_archive` database, exfiltrate all user data/documents |
| **Effort** | Low (hosting provider action) |
| **Priority** | **CRITICAL** |
| **Suggested Action** | 1. Rotate MySQL password at hosting provider (cPanel/CLI) 2. Update legacy `.env` with new password 3. Verify connection works 4. Only then run migration command |

### ACTION-002: Implement Missing Pipeline Jobs (3 Jobs)
| Field | Value |
|-------|-------|
| **Problem** | `VirusScanDocumentJob` dispatches `OcrDocumentJob`, `ThumbnailDocumentJob`, `IndexDocumentJob` — **none exist** |
| **Evidence** | `VirusScanDocumentJob.php:78-80`; `glob app/Jobs/*` = only VirusScanDocumentJob |
| **Impact** | Documents stall after virus scan; OCR, thumbnails, search indexing never run |
| **Effort** | Medium (~3-4 hours) |
| **Priority** | **CRITICAL** |
| **Suggested Action** | Create 3 job classes: `OcrDocumentJob` (Tesseract → extracted_text), `ThumbnailDocumentJob` (LibreOffice/poppler → PNG), `IndexDocumentJob` (Scout sync) |

### ACTION-003: Create Filament Admin Panel + 6 Resources
| Field | Value |
|-------|-------|
| **Problem** | Zero Filament implementation — no PanelProvider, no Resources, no config |
| **Evidence** | CHECKPOINT_20260826 §2 P1; CHECKPOINT_UIUX_20260829 §1.1 (0% frontend); `glob app/Filament/**` = 0 |
| **Impact** | No admin interface for Organizations, Users, Folders, Documents, Cases, Tags |
| **Effort** | High (~15-20 hours) |
| **Priority** | **CRITICAL** |
| **Suggested Action** | 1. `php artisan filament:install --panels` 2. Create AdminPanelProvider with Shield 3. `php artisan make:filament-resource` for 6 models in order: Organization, User, Folder, Document, Case, Tag |

### ACTION-004: Implement Document Download (Signed URL Controller)
| Field | Value |
|-------|-------|
| **Problem** | No controller for `GET /documents/blob/{uuid}`; documents on private S3/R2 inaccessible |
| **Evidence** | CHECKPOINT_20260826 §2 P1 Item 6; `glob app/Http/Controllers/**` = only middleware |
| **Impact** | Core download feature completely broken |
| **Effort** | Medium (~3 hours) |
| **Priority** | **CRITICAL** |
| **Suggested Action** | Create `DownloadDocumentController` with signed route middleware; return `Storage::disk()->response()` |

### ACTION-005: Create DocumentObserver + Wire Pipeline
| Field | Value |
|-------|-------|
| **Problem** | `AppServiceProvider` conditionally registers `DocumentObserver` but class doesn't exist |
| **Evidence** | `AppServiceProvider.php:28-30`; `glob app/Observers/**` = 0 |
| **Impact** | Document uploads won't trigger virus scan pipeline |
| **Effort** | Low (~1 hour) |
| **Priority** | **CRITICAL** |
| **Suggested Action** | Create `DocumentObserver` with `created(Document $doc)` dispatching `VirusScanDocumentJob::dispatch($doc)` |

---

## HIGH Actions

### ACTION-006: Initialize Frontend Build Pipeline (Vite + Tailwind)
| Field | Value |
|-------|-------|
| **Problem** | No `package.json`, `vite.config.js`, `tailwind.config.js` — Filament requires these |
| **Evidence** | CHECKPOINT_UIUX_20260829 §1.1; `glob **/package.json` = 0; `glob **/vite.config.*` = 0 |
| **Impact** | Filament assets won't compile; admin panel unstyled/broken |
| **Effort** | Medium (~4 hours) |
| **Priority** | **HIGH** |
| **Suggested Action** | `npm init`, install Vite/Tailwind/Filament Vite plugin, configure `vite.config.js` with Filament preset |

### ACTION-007: Register All Policies in AuthServiceProvider
| Field | Value |
|-------|-------|
| **Problem** | `DocumentPolicy` exists but not registered; Folder, Case, Share policies missing entirely |
| **Evidence** | CHECKPOINT_20260826 §2 P1 Item 7; `app/Providers/AuthServiceProvider.php` not found |
| **Impact** | Authorization not enforced for key resources |
| **Effort** | Low (~2 hours) |
| **Priority** | **HIGH** |
| **Suggested Action** | Create `AuthServiceProvider` registering DocumentPolicy, FolderPolicy, CasePolicy, SharePolicy |

### ACTION-008: Run Legacy Migration Dry-Run
| Field | Value |
|-------|-------|
| **Problem** | `MigrateLegacyDocumentsCommand` never executed; no validation of data integrity |
| **Evidence** | CHECKPOINT_20260826 §2 P2 Item 8; BOTTLENECK-07; Analysis Archive GAP-07 |
| **Impact** | Risk of data loss/corruption on production migration |
| **Effort** | Medium (~4 hours + iteration) |
| **Priority** | **HIGH** |
| **Suggested Action** | 1. Provision staging copy of legacy DB/files 2. Run `--dry-run` 3. Analyze CSV failures 4. Fix command edge cases 5. Re-run until ≥99% SHA256 match |

### ACTION-009: Remove Unused `stancl/tenancy` Dependency
| Field | Value |
|-------|-------|
| **Problem** | Package installed but only single-DB scope pattern used; adds unused complexity |
| **Evidence** | Only `OrganizationScope` + `BelongsToOrganization` trait implemented; DEPENDENCY_AUDIT.md |
| **Impact** | Unnecessary attack surface; version upgrade burden; confusion |
| **Effort** | Low (~30 min) |
| **Priority** | **HIGH** |
| **Suggested Action** | `composer remove stancl/tenancy`; verify scope + trait still work |

### ACTION-010: Audit Legacy Users Table for Backdoor Accounts
| Field | Value |
|-------|-------|
| **Problem** | Users created ≥2023-02-21 (CVE-2021-3129 exploit date) not audited |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §3 SESSIONS-02; BOTTLENECK-02 |
| **Impact** | Unknown backdoor admin accounts may exist |
| **Effort** | Low (~1 hour) |
| **Priority** | **HIGH** |
| **Suggested Action** | Run `SELECT id, email, role, created_at FROM users WHERE created_at >= '2023-02-21'`; disable unknown admins |

### ACTION-011: Force Legacy Password Invalidation
| Field | Value |
|-------|-------|
| **Problem** | Legacy user passwords not force-reset after APP_KEY rotation |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §3 SESSIONS-02 step 3; BOTTLENECK-03 |
| **Impact** | Stolen credentials remain valid |
| **Effort** | Low (~30 min) |
| **Priority** | **HIGH** |
| **Suggested Action** | `UPDATE users SET password = '!!!' WHERE password != '!!!'` (force reset on next login) |

### ACTION-012: Deploy WAF Rules for Legacy
| Field | Value |
|-------|-------|
| **Problem** | Cloudflare rules for `_ignition/`, `/upl.php`, scanner UAs not deployed |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §5 Item 4; GAP-01 |
| **Impact** | Exploit re-attempts hit origin server |
| **Effort** | Low (~1 hour) |
| **Priority** | **HIGH** |
| **Suggested Action** | Deploy Cloudflare WAF rules: block `_ignition/`, `/upl.php`, known scanner User-Agents |

---

## MEDIUM Actions

### ACTION-013: Implement Test Suite
| Field | Value |
|-------|-------|
| **Problem** | Zero tests exist; no CI enforcement |
| **Evidence** | TESTING_INVENTORY.md; `glob tests/**` = 0 |
| **Impact** | Regressions undetected; no confidence in changes |
| **Effort** | High (~20-30 hours) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Implement test structure per TESTING_INVENTORY.md; start with multi-tenancy, policy, pipeline tests |

### ACTION-014: Create CI/CD Pipeline (GitHub Actions)
| Field | Value |
|-------|-------|
| **Problem** | No automated testing, linting, building, or deployment |
| **Evidence** | `glob .github/workflows/**` = 0; `glob *ci*` = 0 |
| **Impact** | Manual processes; no quality gates |
| **Effort** | Medium (~4 hours) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Create `.github/workflows/ci.yml`: lint (Pint) → test (Pest) → type coverage → build Docker |

### ACTION-015: Split Monolithic Migration
| Field | Value |
|-------|-------|
| **Problem** | All 14 tables in single 259-line migration file |
| **Evidence** | `database/migrations/2026_01_01_000001_enterprise_domain_schema.php` |
| **Impact** | Hard to review, rollback, or modify individual tables |
| **Effort** | Medium (~2 hours) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Split into: tenancy, organizations, workspaces, cases, folders, documents, tags/shares, billing, activity |

### ACTION-016: Add Database Backup/Restore
| Field | Value |
|-------|-------|
| **Problem** | PostgreSQL data only in Docker volume; no backup strategy |
| **Evidence** | `docker-compose.yml` uses `pgsql_data` volume; no backup scripts |
| **Impact** | Data loss on container failure |
| **Effort** | Medium (~3 hours) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Add `pg_dump` cron to S3/R2; document restore procedure; test quarterly |

### ACTION-017: Configure Monitoring (Pulse + Sentry)
| Field | Value |
|-------|-------|
| **Problem** | Laravel Pulse installed but no Sentry, alerting, or dashboards |
| **Evidence** | `composer.json` has `laravel/pulse`; no monitoring configs |
| **Impact** | Production issues detected late |
| **Effort** | Medium (~3 hours) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Configure Pulse dashboard; add Sentry for errors; create healthcheck endpoint |

### ACTION-018: Add 2FA for Admin Roles
| Field | Value |
|-------|-------|
| **Problem** | No 2FA for Owner/Admin roles in Filament |
| **Evidence** | CHECKPOINT_20260826 §2 P2 Item 10; GAP-04 |
| **Impact** | Lateral movement if admin credentials phished |
| **Effort** | Medium (~2 hours) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Install `filament/2fa` or configure Laravel Breeze 2FA; enforce for Owner/Admin |

### ACTION-019: Validate Environment Secrets at Startup
| Field | Value |
|-------|-------|
| **Problem** | Docker Compose has default passwords; no validation they're overridden |
| **Evidence** | `docker-compose.yml` lines 55, 76, 97 |
| **Impact** | Production deployment with defaults = security breach |
| **Effort** | Low (~1 hour) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Add entrypoint script that fails if default passwords detected in production |

### ACTION-020: Migrate Legacy Documents Off Webroot
| Field | Value |
|-------|-------|
| **Problem** | Legacy docs in `public/documents/` protected only by `.htaccess` |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §2 CONFIG-02; BOTTLENECK-01 |
| **Impact** | Apache misconfig or Nginx migration exposes all documents |
| **Effort** | High (part of migration) |
| **Priority** | **MEDIUM** |
| **Suggested Action** | Include in legacy migration command; verify S3/R2 storage before cutover |

---

## LOW Actions

### ACTION-021: Add `.devcontainer` for Codespaces (DONE)
| Field | Value |
|-------|-------|
| **Status** | ✅ **COMPLETED** — `.devcontainer/` created with Docker-in-Docker, PHP 8.3, Composer, Node 20 |
| **Evidence** | `.devcontainer/devcontainer.json`, `Dockerfile`, `post-create.sh` |

### ACTION-022: Document Architecture Decisions
| Field | Value |
|-------|-------|
| **Problem** | Key decisions (single-DB tenancy, ClamAV TCP, R2, Filament) only in checkpoints |
| **Evidence** | CHECKPOINT_20260826 §KEY DECISIONS table |
| **Impact** | Knowledge loss; onboarding friction |
| **Effort** | Low (~2 hours) |
| **Priority** | **LOW** |
| **Suggested Action** | Create `ARCHITECTURE_DECISIONS.md` with ADR format |

### ACTION-023: Add Visual Regression Testing
| Field | Value |
|-------|-------|
| **Problem** | No visual regression testing for Filament UI |
| **Evidence** | CHECKPOINT_UIUX_20260829 §3.1 D-A10 |
| **Impact** | UI regressions undetected |
| **Effort** | Medium (~4 hours) |
| **Priority** | **LOW** |
| **Suggested Action** | Add Playwright/Cypress with visual snapshots for Filament pages |

### ACTION-024: Implement Design Token System
| Field | Value |
|-------|-------|
| **Problem** | No Figma-to-code token sync; colors/spacings hardcoded |
| **Evidence** | CHECKPOINT_UIUX_20260829 §3.1 D-A7, D-A10 |
| **Impact** | Rebranding = manual grep/replace |
| **Effort** | Medium (~8 hours) |
| **Priority** | **LOW** |
| **Suggested Action** | Extract Tailwind theme config; sync with Figma tokens via plugin |

---

## Execution Order (Dependency-Aware)

```
Phase 1: Security & Foundation (Do First)
├── ACTION-001  Rotate legacy DB password
├── ACTION-010  Audit legacy users
├── ACTION-011  Force password invalidation
├── ACTION-012  Deploy WAF rules
└── ACTION-009  Remove stancl/tenancy

Phase 2: Core Implementation (Parallel-Ready)
├── ACTION-002  Implement 3 pipeline jobs
├── ACTION-005  Create DocumentObserver
├── ACTION-004  Implement DownloadController
├── ACTION-006  Initialize Vite/Tailwind
└── ACTION-003  Filament Panel + 6 Resources (depends on 006)

Phase 3: Authorization & Integration
├── ACTION-007  Register all policies
├── ACTION-003 (cont.) Complete Filament Resources
└── ACTION-008  Legacy migration dry-run

Phase 4: Quality & Operations
├── ACTION-013  Test suite
├── ACTION-014  CI/CD pipeline
├── ACTION-015  Split migration
├── ACTION-016  Backup/restore
├── ACTION-017  Monitoring
├── ACTION-018  2FA
└── ACTION-019  Secret validation

Phase 5: Polish
├── ACTION-020  Legacy doc migration
├── ACTION-022  Architecture decisions doc
├── ACTION-023  Visual regression
└── ACTION-024  Design tokens
```

---

## Evidence Traceability

| Action | Primary Evidence | Confidence |
|--------|------------------|------------|
| ACTION-001 | INCIDENT_RESPONSE_LOG_20260826 §5[1] | High |
| ACTION-002 | VirusScanDocumentJob.php:78-80 | High |
| ACTION-003 | CHECKPOINT_20260826 §2 P1 | High |
| ACTION-004 | CHECKPOINT_20260826 §2 P1 Item 6 | High |
| ACTION-005 | AppServiceProvider.php:28-30 | High |
| ACTION-006 | CHECKPOINT_UIUX_20260829 §1.1 | High |
| ACTION-007 | CHECKPOINT_20260826 §2 P1 Item 7 | High |
| ACTION-008 | CHECKPOINT_20260826 §2 P2 Item 8 | High |
| ACTION-009 | DEPENDENCY_AUDIT.md | High |
| ACTION-010 | INCIDENT_RESPONSE_LOG_20260826 §3 | High |
| ACTION-011 | INCIDENT_RESPONSE_LOG_20260826 §3 | High |
| ACTION-012 | INCIDENT_RESPONSE_LOG_20260826 §5[4] | High |
| ACTION-013 | TESTING_INVENTORY.md | High |
| ACTION-014 | No CI files found | High |
| ACTION-015 | Single migration file | High |
| ACTION-016 | No backup scripts | High |
| ACTION-017 | Pulse installed, no config | High |
| ACTION-018 | CHECKPOINT_20260826 §2 P2 Item 10 | High |
| ACTION-019 | docker-compose.yml defaults | High |
| ACTION-020 | INCIDENT_RESPONSE_LOG_20260826 §2 | High |

---

## Self-Critique (Per Protocol §18)

| Question | Answer |
|----------|--------|
| **What could make this conclusion wrong?** | If Filament v3 has breaking changes not accounted for; if legacy migration command has undiscovered bugs; if ClamAV integration fails in container network |
| **What was not tested?** | Nothing — zero tests exist; no runtime verification performed |
| **What assumptions remain?** | Docker Compose works as written; R2 credentials valid; Meilisearch/ClamAV images healthy; Filament 3 compatible with Laravel 11 |
| **What evidence is indirect?** | CHECKPOINT claims (Filament "covers 62% WCAG") — not verified; UI/UX benchmarks are estimates |
| **Which conclusions depend on another artifact being correct?** | All Phase 2 actions depend on CHECKPOINT_20260826 priority ordering being valid |
| **What attack would an adversarial reviewer attempt first?** | 1. Cross-tenant data access via OrganizationScope bypass 2. Unsigned document download 3. Legacy credential reuse 4. Missing auth on Filament resources |
| **What remains unverifiable?** | Filament WCAG compliance claim; MeiliSearch performance at scale; ClamAV throughput under load |

---

## Success Criteria for Next Checkpoint

| Criterion | Verification |
|-----------|--------------|
| Legacy DB password rotated | Hosting provider confirmation + `.env` updated |
| 3 pipeline jobs implemented | `glob app/Jobs/*.php` = 4 files |
| DocumentObserver created | `app/Observers/DocumentObserver.php` exists |
| DownloadController works | `GET /documents/blob/{uuid}` returns file (signed) |
| Filament Panel accessible | `/admin` returns 200, 6 Resources in sidebar |
| Legacy migration dry-run ≥99% | CSV report shows ≤1% failures, SHA256 match |
| Test suite runs | `./vendor/bin/pest` passes (even if minimal) |
| CI pipeline green | GitHub Actions shows passing build |