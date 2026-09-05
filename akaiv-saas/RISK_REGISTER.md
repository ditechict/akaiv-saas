# RISK_REGISTER.md — AKAIV SaaS Risk Assessment

**Generated:** 2026-09-04  
**Method:** Evidence-based repository audit per Deep Architecture Discovery Agent protocol

---

## Risk Scoring Matrix

| Probability | Impact | Score |
|-------------|--------|-------|
| High (H) | Critical (C) | **CRITICAL** |
| High (H) | High (H) | **HIGH** |
| Medium (M) | Critical (C) | **HIGH** |
| High (H) | Medium (M) | **HIGH** |
| Medium (M) | High (H) | **HIGH** |
| Medium (M) | Medium (M) | **MEDIUM** |
| Low (L) | Critical (C) | **MEDIUM** |
| Low (L) | High (H) | **MEDIUM** |
| Low (L) | Medium (M) | **LOW** |

---

## Technical Risks

### RISK-T01: Document Processing Pipeline Incomplete
| Field | Value |
|-------|-------|
| **Risk** | `VirusScanDocumentJob` dispatches 3 jobs (`OcrDocumentJob`, `ThumbnailDocumentJob`, `IndexDocumentJob`) that **do not exist** |
| **Probability** | High |
| **Impact** | Critical — uploaded documents stall after virus scan |
| **Evidence** | `VirusScanDocumentJob.php:78-80` dispatches non-existent classes; `glob app/Jobs/*` returns only `VirusScanDocumentJob.php` |
| **Confidence** | High |
| **Mitigation** | Implement all 3 missing jobs before enabling uploads |

### RISK-T02: Filament Admin Panel Not Implemented
| Field | Value |
|-------|-------|
| **Risk** | Zero Filament resources, PanelProvider, or configuration exist |
| **Probability** | High |
| **Impact** | Critical — no admin interface for any domain entity |
| **Evidence** | `glob app/Filament/**` = 0 files; `glob config/filament*.php` = 0 files; CHECKPOINT_UIUX_20260829 §1.1 confirms 0% |
| **Confidence** | High |
| **Mitigation** | Create AdminPanelProvider + 6 Resources per CHECKPOINT_20260826 §2 P1 |

### RISK-T03: No Frontend Build Pipeline
| Field | Value |
|-------|-------|
| **Risk** | No `package.json`, `vite.config.js`, `tailwind.config.js`, or CSS/JS entrypoints |
| **Probability** | High |
| **Impact** | High — Filament requires Vite/Tailwind for asset compilation |
| **Evidence** | `glob **/package.json` = 0; `glob **/vite.config.*` = 0; `glob resources/**/*.css` = 0; CHECKPOINT_UIUX_20260829 §1.1 |
| **Confidence** | High |
| **Mitigation** | Initialize npm, install Vite/Tailwind/Filament assets, configure build |

### RISK-T04: Missing DocumentObserver
| Field | Value |
|-------|-------|
| **Risk** | `AppServiceProvider.php:28-30` conditionally registers `DocumentObserver` but class doesn't exist |
| **Probability** | Medium |
| **Impact** | High — uploads won't trigger virus scan pipeline |
| **Evidence** | `AppServiceProvider.php` line 28-30; `glob app/Observers/**` = 0 files |
| **Confidence** | High |
| **Mitigation** | Create `DocumentObserver` with `created()` dispatching `VirusScanDocumentJob` |

### RISK-T05: Missing Signed URL Controller
| Field | Value |
|-------|-------|
| **Risk** | No controller for `GET /documents/blob/{uuid}` signed route; documents on private S3/R2 disk inaccessible |
| **Probability** | High |
| **Impact** | Critical — core download feature broken |
| **Evidence** | CHECKPOINT_20260826 §2 P1 Item 6; `glob app/Http/Controllers/**` = only middleware files |
| **Confidence** | High |
| **Mitigation** | Implement `DownloadDocumentController` with signed route middleware |

### RISK-T06: Missing AuthServiceProvider Policies
| Field | Value |
|-------|-------|
| **Risk** | `DocumentPolicy` exists but not registered; no policies for Folder, Case, Share |
| **Probability** | Medium |
| **Impact** | High — authorization not enforced for key resources |
| **Evidence** | CHECKPOINT_20260826 §2 P1 Item 7; `app/Providers/AuthServiceProvider.php` not found |
| **Confidence** | High |
| **Mitigation** | Create `AuthServiceProvider` registering all policies |

### RISK-T07: No Test Coverage
| Field | Value |
|-------|-------|
| **Risk** | Zero tests exist; no CI to enforce quality |
| **Probability** | High |
| **Impact** | High — regressions undetected, no confidence in refactors |
| **Evidence** | `glob tests/**` = 0 files; TESTING_INVENTORY.md |
| **Confidence** | High |
| **Mitigation** | Implement test suite per TESTING_INVENTORY.md recommendations |

### RISK-T08: Single Migration File (Schema Drift Risk)
| Field | Value |
|-------|-------|
| **Risk** | All 14 tables in one migration; no granular rollback, hard to review |
| **Probability** | Medium |
| **Impact** | Medium — deployment/rollback complexity |
| **Evidence** | `database/migrations/2026_01_01_000001_enterprise_domain_schema.php` = 259 lines |
| **Confidence** | High |
| **Mitigation** | Split into logical migrations (tenancy, documents, billing, etc.) |

### RISK-T09: Legacy Migration Command Untested
| Field | Value |
|-------|-------|
| **Risk** | `MigrateLegacyDocumentsCommand` never executed; no dry-run performed |
| **Probability** | High |
| **Impact** | Critical — data loss risk on production migration |
| **Evidence** | CHECKPOINT_20260826 §2 P2 Item 8; BOTTLENECK-07 in analysis archive |
| **Confidence** | High |
| **Mitigation** | Run `--dry-run` against staging copy; iterate until ≥99% success |

### RISK-T10: Hardcoded Default Passwords in Docker Compose
| Field | Value |
|-------|-------|
| **Risk** | `docker-compose.yml` has default passwords (`akaiv_ChangeMe_2026!`, `akaiv_redis_ChangeMe!`, `akaiv_master_ChangeMe!`) |
| **Probability** | Medium |
| **Impact** | High — if deployed without .env override |
| **Evidence** | `docker-compose.yml` lines 55, 76, 97 |
| **Confidence** | High |
| **Mitigation** | Require .env validation at startup; fail if defaults detected in production |

---

## Operational Risks

### RISK-O01: No CI/CD Pipeline
| Field | Value |
|-------|-------|
| **Risk** | No GitHub Actions, GitLab CI, or any automation |
| **Probability** | High |
| **Impact** | High — manual deployment, no automated testing |
| **Evidence** | `glob .github/workflows/**` = 0; `glob *ci*` = 0 |
| **Confidence** | High |
| **Mitigation** | Create CI workflow: lint → test → build → deploy staging |

### RISK-O02: No Monitoring/Observability Stack
| Field | Value |
|-------|-------|
| **Risk** | Laravel Pulse installed but no Prometheus, Grafana, Sentry, or alerting |
| **Probability** | High |
| **Impact** | Medium — production issues detected late |
| **Evidence** | `composer.json` has `laravel/pulse`; no monitoring configs found |
| **Confidence** | High |
| **Mitigation** | Configure Pulse + Sentry + basic healthcheck endpoints |

### RISK-O03: No Backup/Restore Strategy Documented
| Field | Value |
|-------|-------|
| **Risk** | PostgreSQL data in Docker volume only; no backup cron, no restore tested |
| **Probability** | Medium |
| **Impact** | Critical — data loss on container failure |
| **Evidence** | `docker-compose.yml` uses `pgsql_data` volume; no backup scripts found |
| **Confidence** | High |
| **Mitigation** | Implement pg_dump cron to S3/R2; test restore procedure |

### RISK-O04: Single-Point-of-Failure Scheduler
| Field | Value |
|-------|-------|
| **Risk** | One `scheduler` container runs `schedule:run`; no HA |
| **Probability** | Medium |
| **Impact** | Medium — scheduled jobs stop if container dies |
| **Evidence** | `docker-compose.yml` lines 160-176 |
| **Confidence** | High |
| **Mitigation** | Use Redis-based scheduler lock; consider multiple replicas |

### RISK-O05: No Staging Environment Defined
| Field | Value |
|-------|-------|
| **Risk** | Only local Docker Compose; no staging infrastructure |
| **Probability** | High |
| **Impact** | High — no pre-production validation |
| **Evidence** | No staging configs, no deployment scripts |
| **Confidence** | High |
| **Mitigation** | Define staging in CI/CD; mirror production topology |

---

## Security Risks

### RISK-S01: Leaked Legacy Database Credentials
| Field | Value |
|-------|-------|
| **Risk** | Legacy `myarchivesonline.com/.env` contains `DB_PASSWORD=tMfKFvPwLT7d` — confirmed leaked |
| **Probability** | High (already occurred) |
| **Impact** | Critical — attacker can access legacy MySQL |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §1; Analysis Archive BLOCKER-01; .env line 15 |
| **Confidence** | High |
| **Mitigation** | **URGENT**: Rotate at hosting provider; update legacy .env; verify before migration |

### RISK-S02: Legacy Documents in Webroot
| Field | Value |
|-------|-------|
| **Risk** | Legacy documents at `public/documents/` protected only by `.htaccess` |
| **Probability** | Medium |
| **Impact** | Critical — if Apache misconfigured or migrated to Nginx, all docs exposed |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §2 CONFIG-02; BOTTLENECK-01 in analysis archive |
| **Confidence** | High |
| **Mitigation** | Migrate to `storage/app/documents` or S3 before SaaS cutover |

### RISK-S03: No User Audit Post-Breach
| Field | Value |
|-------|-------|
| **Risk** | Legacy users table not audited for accounts created ≥2023-02-21 (CVE-2021-3129 exploit date) |
| **Probability** | Medium |
| **Impact** | High — potential backdoor admin accounts |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §3 SESSIONS-02; BOTTLENECK-02 |
| **Confidence** | High |
| **Mitigation** | Execute audit before migration; disable unknown admins |

### RISK-S04: Legacy Password Hashes Not Invalidated
| Field | Value |
|-------|-------|
| **Risk** | Legacy user passwords not force-reset after APP_KEY rotation |
| **Probability** | Medium |
| **Impact** | High — stolen credentials still valid |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §3 SESSIONS-02 step 3; BOTTLENECK-03 |
| **Confidence** | High |
| **Mitigation** | Force password reset via DB UPDATE before migration |

### RISK-S05: No WAF/Edge Protection
| Field | Value |
|-------|-------|
| **Risk** | Cloudflare rules for `_ignition/`, `/upl.php`, scanner UAs not deployed |
| **Probability** | Medium |
| **Impact** | High — exploit re-attempts hit origin |
| **Evidence** | INCIDENT_RESPONSE_LOG_20260826 §5 Item 4; GAP-01 |
| **Confidence** | High |
| **Mitigation** | Deploy WAF rules before SaaS go-live |

### RISK-S06: No 2FA for Admin Roles
| Field | Value |
|-------|-------|
| **Risk** | Filament 2FA not configured for Owner/Admin roles |
| **Probability** | High (not implemented) |
| **Impact** | High — lateral movement if admin creds phished |
| **Evidence** | CHECKPOINT_20260826 §2 P2 Item 10; GAP-04 |
| **Confidence** | High |
| **Mitigation** | Install `filament/2fa` or Laravel Breeze 2FA before production |

### RISK-S07: Caddy Auto-HTTPS with Local CA
| Field | Value |
|-------|-------|
| **Risk** | Caddy uses local CA; browser trust requires manual cert install |
| **Probability** | Medium |
| **Impact** | Low (dev) / Medium (staging) |
| **Evidence** | `docker/caddy/Caddyfile`; README_QUICKSTART.md line 74 |
| **Confidence** | High |
| **Mitigation** | Use real certs (Let's Encrypt) for staging/production |

---

## Risk Summary

| Category | Critical | High | Medium | Low | Total |
|----------|----------|------|--------|-----|-------|
| Technical | 3 | 4 | 1 | 0 | 8 |
| Operational | 0 | 2 | 3 | 0 | 5 |
| Security | 2 | 4 | 0 | 0 | 6 |
| **Total** | **5** | **10** | **4** | **0** | **19** |

---

## Immediate Action Required (Critical Risks)

| Priority | Risk ID | Action | Deadline |
|----------|---------|--------|----------|
| 1 | RISK-S01 | Rotate legacy MySQL password at hosting provider | **Before any migration** |
| 2 | RISK-T01 | Implement OcrDocumentJob, ThumbnailDocumentJob, IndexDocumentJob | Before uploads enabled |
| 3 | RISK-T02 | Create Filament AdminPanelProvider + 6 Resources | Per ACTION-A01 |
| 3 | RISK-T05 | Implement DownloadDocumentController + signed route | Per ACTION-A01 P1 |
| 4 | RISK-S02 | Migrate legacy docs to S3/storage (off webroot) | Before SaaS cutover |
| 4 | RISK-S03 | Audit legacy users table for post-2023-02-21 accounts | Before migration |
| 4 | RISK-S04 | Force password invalidation for all legacy users | Before migration |