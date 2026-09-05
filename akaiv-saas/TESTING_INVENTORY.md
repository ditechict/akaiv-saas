# TESTING_INVENTORY.md — AKAIV SaaS Testing Discovery

**Generated:** 2026-09-04  
**Method:** Evidence-based repository audit per Deep Architecture Discovery Agent protocol

---

## Test Infrastructure Status

| Aspect | Status | Evidence |
|--------|--------|----------|
| **Test Directory** | **MISSING** | No `tests/` directory exists in `akaiv-saas/` |
| **PHPUnit Config** | EXISTS | `phpunit.xml` present, configured for Unit/Feature suites |
| **Pest Config** | EXISTS | `pestphp/pest` in composer.json dev deps |
| **Test Files** | **NONE** | Zero test files found |
| **Coverage Tool** | CONFIGURED | `pestphp/pest-plugin-type-coverage` in dev deps |

---

## PHPUnit Configuration Analysis

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
</testsuites>
<source>
    <include>
        <directory suffix=".php">./app</directory>
    </include>
</source>
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SCOUT_DRIVER" value="collection"/>
</php>
```

**Observations:**
- Configuration expects `tests/Unit` and `tests/Feature` directories
- Uses SQLite in-memory for testing (fast, isolated)
- Queue sync, Scout collection driver for test isolation
- Source coverage limited to `./app`

---

## Pest Configuration (Dev Dependencies)

| Package | Version | Purpose |
|---------|---------|---------|
| `pestphp/pest` | ^2.34 | Test framework |
| `pestphp/pest-plugin-laravel` | ^2.3 | Laravel helpers |
| `pestphp/pest-plugin-livewire` | ^2.1 | Livewire testing |
| `pestphp/pest-plugin-type-coverage` | ^2.8 | Type coverage analysis |

---

## Coverage Analysis

| Metric | Current | Target | Gap |
|--------|---------|--------|-----|
| **Unit Tests** | 0 | ≥80% | 100% |
| **Feature Tests** | 0 | ≥80% | 100% |
| **Integration Tests** | 0 | Critical paths | 100% |
| **E2E Tests** | 0 | Key workflows | 100% |
| **Type Coverage** | N/A | ≥90% | 100% |

---

## Missing Coverage Areas (Critical)

### 1. Multi-Tenancy Enforcement
- **Target:** `OrganizationScope::apply()`, `BelongsToOrganization` trait
- **Tests Needed:**
  - Scope applied in web context
  - Scope bypassed in console/tests
  - `withoutTenancy()` macro works
  - Auto-set organization_id on create
  - Cross-tenant query isolation

### 2. Document Authorization
- **Target:** `DocumentPolicy` methods
- **Tests Needed:**
  - SuperAdmin bypass (before hook)
  - ViewAny requires org membership
  - View/download/update/delete require correct permissions
  - Owner vs permission-based access
  - Cross-org access denied

### 3. Document Processing Pipeline
- **Target:** `VirusScanDocumentJob`, chained jobs
- **Tests Needed:**
  - Infected file → quarantined, activity logged
  - Clean file → published, jobs chained with delays
  - Missing file → quarantined
  - ClamAV exception → retry with backoff
  - OCR/Thumbnail/Index job dispatch (when implemented)

### 4. Legacy Migration Command
- **Target:** `MigrateLegacyDocumentsCommand`
- **Tests Needed:**
  - Dry-run produces correct output
  - PHP executable files skipped
  - SHA256 deduplication works
  - Legacy filename timestamp parsing
  - User matching by name/surname
  - Folder creation/association
  - CSV failure report generation

### 5. Organization/Workspace/Case/Folder Models
- **Target:** All model relationships, scopes, helpers
- **Tests Needed:**
  - Hierarchical folder paths
  - Workspace permissions
  - Case parties JSON accessor/mutator
  - Organization storage quota enforcement

### 6. Sharing System
- **Target:** `Share` model, ULID tokens, password/IP/expiry
- **Tests Needed:**
  - Token generation uniqueness
  - Password hashing/verification
  - IP allowlist enforcement
  - Expiry enforcement
  - Access count tracking

---

## Recommended Test Structure

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── DocumentTest.php
│   │   ├── OrganizationTest.php
│   │   ├── FolderTest.php
│   │   ├── CaseFileTest.php
│   │   └── ShareTest.php
│   ├── Policies/
│   │   └── DocumentPolicyTest.php
│   ├── Scopes/
│   │   └── OrganizationScopeTest.php
│   ├── Traits/
│   │   └── BelongsToOrganizationTest.php
│   └── Jobs/
│       └── VirusScanDocumentJobTest.php
├── Feature/
│   ├── Auth/
│   │   └── MultiTenancyTest.php
│   ├── Documents/
│   │   ├── UploadTest.php
│   │   ├── DownloadTest.php
│   │   ├── ShareTest.php
│   │   └── PipelineTest.php
│   ├── Migration/
│   │   └── MigrateLegacyDocumentsCommandTest.php
│   └── API/
│       └── FilamentResourcesTest.php (when implemented)
└── Pest.php (configuration)
```

---

## Testing Commands (When Implemented)

```bash
# Run all tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run type coverage
./vendor/bin/pest --type-coverage

# Run specific suite
./vendor/bin/pest --testsuite=Unit
./vendor/bin/pest --testsuite=Feature

# Parallel execution
./vendor/bin/pest --parallel
```

---

## Evidence Summary

| Finding | Evidence | Files | Confidence |
|---------|----------|-------|------------|
| No tests directory exists | `glob tests/**` returned empty | — | High |
| PHPUnit configured for Unit/Feature | `phpunit.xml` lines 10-16 | `phpunit.xml` | High |
| Pest dev dependencies declared | `composer.json` lines 34-43 | `composer.json` | High |
| SQLite in-memory for tests | `phpunit.xml` lines 27-28 | `phpunit.xml` | High |
| Zero test files | `glob tests/**/*.php` empty | — | High |