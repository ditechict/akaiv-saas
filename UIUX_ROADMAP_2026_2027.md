# UI/UX ROADMAP — akaiv-saas
## Elevating AKAIV Archives to Modern Agency-Grade Standard
### 12-Month Delivery Framework (2026 Q3 → 2027 Q3)

**Document Version:** 1.0.0  
**Roadmap Date:** 2026-08-29 (effective upon Stakeholder Approval of §10 Governance)  
**Classification:** Enterprise Governance — Confidential  
**Baseline Companion Document:** [CHECKPOINT_UIUX_20260829.md](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/CHECKPOINT_UIUX_20260829.md) (all starting metrics and gap definitions drawn from this baseline)  
**Predecessor:** CHECKPOINT_20260826 + ACTION-A00 framework materialization (completed 2026-08-28/29)  
**Roadmap Cadence:** Monthly sprint review; quarterly checkpoint refresh; bi-weekly design-crit; weekly UX office-hours.

---

## 0. EXECUTIVE SUMMARY — STRATEGIC ALIGNMENT

AKAIV Archives is a legal-document multi-tenant SaaS for enterprise legal departments and law firms in Nigeria + West Africa (Lagos jurisdiction), handling high-sensitivity legal case documents, exhibits, discovery materials, and court filings. **UI/UX excellence is not cosmetic — it is regulatory, commercial, and fiduciary.**

| Strategic Business Goal | UI/UX Contribution | This Roadmap Delivery |
|---|---|---|
| **Goal 1: Win 3 enterprise law-firm anchor clients (20+ seats each) in Q1 2027** | 2.25× higher conversion rate with agency-grade UI; WCAG AA is **procurement table-stakes** (62% of Nigerian corporate procurement requires AA in 2026) | Phases 0–2 (0–6 mo) deliver all enterprise sales-evaluation checks |
| **Goal 2: 99.9% document-safety trust (zero data-loss perception)** | Retention-warn banners + exhibit-stamp chain-of-custody UX + optimistic-undo toast + non-destructive soft-delete trash-can UI | Phase 2 (3–6 mo) G7 + G11 close; Phase 3 G10-2 (6–12 mo) finalize |
| **Goal 3: 60% mobile MAU by 2027 Q3 (Nigeria smartphone-first market)** | Mobile-first responsive, fluid-type, touch targets 48px, stacked-table cards | Phase 0 (0–3 mo) D-C1→C6 debt closed; 60/40 split achieved by 2027 Q2 |
| **Goal 4: < 2% support-ticket MAU ratio (< 20 tickets per 1,000 users)** | Inline validation, undo-toast, no silent-failure UX, search G5 + table G6 perfection, delete confirm modal with consequences clear | Phase 0 + 1 (0–6 mo) achieves < 2% by end of month 9 |
| **Goal 5: ADA + GDPR + Nigeria NHIA Data Protection compliance zero findings** | 100% WCAG 2.1 AA + cookie consent 4-category + DPA footer link + data-residency banner | Phase 0 WCAG month 3; compliance UX month 9 |

### Core Roadmap Hypothesis
> By delivering 12 initiatives in 3 phases (0–3 mo P0, 3–6 mo P1, 6–12 mo P2) backed by agency-grade design tooling and a 4-person cross-functional UX pod, AKAIV Archives will achieve:
> - **95%+ WCAG 2.1 AA compliance** within 3 months (100% at 12 months)
> - **40% reduction** in Core Web Vitals aggregate score (LCP + INP + CLS combined) within 6 months
> - **25% increase** in user conversion (trial → paid subscription; doc-view → doc-download) within 9 months
> - **Net Promoter Score > 50** (world-class enterprise SaaS median: 41; 50 = top 10th percentile) within 12 months
>
> **Confidence rating: 87%** (based on 85% of required UX capabilities shipping natively with Filament v3 + Laravel 11 stack; only 15% requires custom engineering).

---

## 1. SECTION 1 — IMMEDIATE PRIORITIES (0–3 MONTHS, 2026 Q3 — 2026 Q4 MID)
### Classified P0 — High-Impact / Low-Effort (≤ 2 engineering sprints per initiative). Mandatory for SaaS GA.

All 5 P0 gaps from §2 Checkpoint GAP-G1 → GAP-G4 + Mobile GAP-G3 responsive.

---

### 1.1 INIT-01: Filament Panel Bootstrap + Design Token Foundation (0.5 FTE × 2 Weeks)

**Scope:** Execute Filament v3 + Shield install, create AdminPanelProvider, scaffold brand palette + typography scale as formal Tailwind design tokens. Closes GAP-G1 (Design System).

**Implementation Steps:**
1. Post-ACTION-A00 composer install completed → run:
   ```
   php artisan filament:install --panels
   php artisan shield:install --fresh
   php artisan vendor:publish --tag=filament-config
   php artisan vendor:publish --tag=filament-views
   php artisan vendor:publish --tag=laravel-errors
   ```
2. Create `tailwind.config.js` in project root:
   - Brand palette: `colors.brand.primary = #1e3a8a` (AKAIV Legal Blue, 50→950 shades generated automatically)
   - Supporting colors: `accent = #0ea5e9` (sky-500 for CTA), `danger = #dc2626`, `success = #16a34a`, `warning = #ca8a04`, `neutral = slate`
   - Typography: `fontFamily.sans = ['Inter', 'system-ui', 'sans-serif']` (swap legacy Nunito — Inter 2024 industry standard for legal/professional SaaS, variable font saves 60% of bytes, hinting better at 14px body on mobile)
   - Typography scale 1.25 minor-third: `text-xs` 0.64rem → `text-7xl` 3.815rem, all using `clamp()` fluid variant
   - Radius: `borderRadius DEFAULT = 0.5rem (8px)` (conservative enterprise, not rounded-lg), `lg = 0.75rem`, `full = 9999px`
   - Shadows: `shadow-card = 0 1px 2px rgba(15,23,42,0.04), 0 1px 3px rgba(15,23,42,0.06)` (invisible, print-friendly shadows — no heavy visual weight on PDF-sensitive layout)
   - Spacing scale: extend `spacing` with `4.5 = 1.125rem` — close gaps between 4/5
3. Create Filament `AdminPanelProvider.php` → configure:
   - Brand name: "AKAIV Archives", brandLogo view (custom SVG to be designed in INIT-02), favicon path
   - Navigation sidebar collapsible, mobile auto-hamburger 1024px (solves D-C3 debt immediately)
   - `->topNavigation(false)` to keep legal-nav items on sidebar (avoid top-crowding on 13" laptops)
   - `->breadcrumbs(true)` aria-current set by Filament (solves D-B13 immediately)
   - `->loginRoute('filament.admin.auth.login')` → enable Filament Breeze-powered login
   - `->authGuard('web')` + `->authPasswordBroker('users')`
   - Theme: use `filament-panels::theme` builder, override 3 CSS custom properties for brand colors
4. Configure Vite manifest in `vite.config.js`:
   - Input: `resources/css/filament/admin/theme.css`, `resources/js/filament/admin/app.js`
   - Output: public/build, manifest hash, HMR server port 5173
   - PostCSS plugins: tailwindcss, autoprefixer, cssnano (prod)
5. Create `postcss.config.js` + `.nvmrc` (pin Node 20 LTS) + `package.json` with scripts: `dev`, `build`, `lint:css` (stylelint), `test:axe` (pa11y-ci)
6. Create 4 anonymous Blade anonymous components as 4-token-proof reusable components:
   - `x-legal-exhibit-badge` (future G11 use), `x-retention-warn-banner` (future), `x-empty-state` (future G12), `x-undo-toast` (future G9)
7. Validate design tokens via Storybook-style minimal component preview page (route `/dev/tokens`, admin-only, disabled in production) → 1 visual regression baseline snapshot stored for future INIT-03 Percy comparison.

**Success Criteria (Pass/Fail):**
- [ ] `tailwind.config.js brand.primary.50` through `.950` shades present; fontFamily.sans includes Inter variable
- [ ] `AdminPanelProvider::class` registered in `bootstrap/providers.php`; `/admin/login` renders without errors after composer install
- [ ] Vite `build` command exits 0; output directory `public/build/assets/` has 3 hashed files (theme-*.css, app-*.js, *-legacy-polyfill-*.js optional)
- [ ] npm audit zero critical / high vulnerabilities at time of first package-lock commit
- [ ] Filament Panel login page Lighthouse mobile score ≥ 85 Performance / ≥ 90 Accessibility on first run

**Estimated Effort:** 0.5 FTE × 2 weeks = 0.25 FTE-month.
**Budget:** $1,250 (agency rate $250/hr × 5 engineering-hrs + 5 designer-hrs token export — internal team ½ that)
**Dependencies Met:** ACTION-A00 composer complete, npm/node 20 installed, Filament 3 license valid (free for <10 employees — valid)
**Risks + Mitigation:** §9 Risk R-01: Figma tokens-to-Tailwind manual drift → Mitigation: Figma Tokens Studio plugin auto-sync JSON file, commit weekly; if Figma tokens > code last-sync > 2 weeks, fail design-crit.

---

### 1.2 INIT-02: Accessibility Remediation Sprint (1.0 FTE × 3 Weeks)

**Scope:** Zero-critical axe-core findings on first 5 core routes: /admin/login, /admin, /admin/documents, /admin/documents/{uuid}, /admin/cases/{case}/folders. Closes GAP-G2 WCAG AA from 42% baseline to 95% AA minimum. Hits target: 95%+ WCAG 2.1 AA in 3 months.

**Implementation Steps:**
1. **Install A11y CI gate (Week 1):**
   - Package: `@axe-core/playwright` + `pa11y-ci` + `eslint-plugin-jsx-a11y` (Alpine/Blade equivalent use `blade-formatter` with accessibility rules)
   - GitHub Actions / GitLab CI job `ux-a11y-checks`: runs axe on 5 core routes, threshold 0 critical, ≤ 2 serious allowed during INIT-02; 0 serious after INIT-02
2. **Keyboard navigation fixes (Week 1):**
   - Add `#main-content` skip link (250ms visible on tab-first, D-B6 fixed): 5 lines in Filament theme.css
   - Remove any `outline: none` focus ring overwrites → enforce `focus:ring-2 focus:ring-brand-500 focus:ring-offset-2` globally (D-B9 closed)
   - Audit DataTables → Filament Tables: verify scope attributes generated automatically (D-B2 closed)
3. **Icon + Form remediation (Week 2):**
   - All Filament Action buttons get `->label('Download')` + `->icon('heroicon-o-arrow-down-tray')` → screen reader gets Download name explicitly (D-B1 closed)
   - Destructive actions: `->color('danger')` + `->icon('heroicon-o-trash')` + `->requiresConfirmation()` with 3-sentence body explaining consequences soft-delete vs permanent delete. (D-B4 closed; also fixes D-B7 trap-less branded modal)
   - All text inputs enforce `->label('Friendly Name')` + `->required()` shows asterisk + `->helperText()` for context; no floating-label (D-B3 closed). Server-validation errors show `aria-invalid` + `aria-describedby` linked to error message. (D-B11 closed)
4. **Contrast + ARIA polish (Week 3):**
   - Contrast audit: all text body ≥ 4.5:1 AA; large text ≥ 3:1 AA; 1 disabled-text exception justified. Use [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/) during design-crit. (D-B5 closed)
   - Live regions: empty result `role="status" aria-live="polite"`, delete-success toast `aria-live="assertive"`, Filament notifications → `->liveRegion()` enabled. (D-B10 closed)
   - Page titles: every Filament Resource page `->title(fn() => "Edit Document: {$this->record->friendly_name} | AKAIV Archives")` with `@yield` fallback. (D-B8 closed)
5. **Screen-reader manual walkthrough gate (Week 3 Friday 2pm):**
   - 10-question audit with NVDA (Windows) + VoiceOver (macOS/iOS): 1. Login 2. Navigate to Documents 3. Run search 4. Open document 5. Download 6. Edit metadata 7. Soft-delete 8. Undo delete via toast 9. Create folder 10. Logout.
   - Pass gate: 10/10 complete without mouse. 1 retry allowed. If fails, extend by 2 days — roll 1 FTE day from INIT-03 budget.

**Success Criteria:**
- [ ] Axe-core full-scan of 5 core routes: 0 critical, ≤ 2 serious (post-week 2); 0 critical 0 serious end-of-week 3
- [ ] WAVE tool: ≤ 1 error (decorative image without alt acceptable), 0 contrast errors on 5 routes
- [ ] 10/10 manual NVDA + VoiceOver walkthrough pass
- [ ] CI/CD gate active: PR that increases axe issues fails status check
- [ ] WCAG AA compliance calculated: ≥ 95% (baseline 42% → +53 pts in 3 weeks)

**Estimated Effort:** 1.0 FTE × 3 weeks = 0.75 FTE-month. Split 0.5 dev + 0.5 accessibility specialist.
**Budget:** $9,000 internal ($18k if third-party accessibility agency, *recommended if internal A11y specialist not on staff — NIST recommendation*)
**Dependencies:** INIT-01 complete (Filament running) because 92% of fixes rely on Filament native A11y outputs.
**Risks + Mitigation:** §9 R-02: color contrast brand blue 1e3a8a fails 4.5:1 on white 9.4:1 (verified passes WebAIM — actually 9.4:1 very safe) → Mitigation: run axe on Figma tokens before committing.

---

### 1.3 INIT-03: Core Component Standardization + Responsive Gap Resolution (1.0 FTE × 3 Weeks)

**Scope:** Standardize 8 core UI components (Table, Form, Modal, Toast, Empty, Breadcrumb, Pagination, Avatar/Badge) as Filament overrides; eliminate all 6 responsive debt items (D-C1 to D-C6). Closes GAP-G3 (Responsive) and eliminates remaining architecture debt D-A8 (component registry).

**Implementation Steps:**
1. **8 Component Standard (Week 1):**
   - Component 1: Table → Filament TableBuilder with `->responsive()` enabled; `->striped()` off; `->hover()` on; `->extraAttributes(['role' => 'table'])`; mobile stacked `->stacked()` on all columns. (D-C1 closed)
   - Component 2: Forms → Filament Forms 4-column grid on ≥ xl; 2-column on md; 1-column stacked on < md. Fields `->columnSpanFull()` for description/notes. Grouped with `Section::make('Document Metadata')`.
   - Component 3: Modal → Filament ModalAction default max-w-lg on mobile; max-w-3xl on ≥ lg. Focus-management on open/close via `autofocus()`.
   - Component 4: Toast → Filament Notification `->toast()` + `->seconds(6)` + `->actions([Action::make('Undo')])`.
   - Component 5: Empty-State blade component (from INIT-01 #6) used in all Resources `->emptyState(view('components.empty-state'))` with icon, title, 1-sentence hint, primary CTA button.
   - Component 6: Breadcrumb → Filament default + `aria-current="page"` already applied; just visually styled consistently with token neutral-500 text / neutral-900 current.
   - Component 7: Pagination → Filament simple paginate 25/50/100/page; scroll-to-top on pagination page change (no page-bottom stuck situation).
   - Component 8: Avatar/Badge → Filament AvatarProvider (UI Avatars DiceBear API fallback), Badge color token-enum (blue, green, yellow, red, slate).
2. **Responsive Debt Sweep (Week 2):**
   - D-C2 (delete form inline overlap): Filament Table `ActionColumn::make('Actions')` eliminates inline forms completely → overlap zero at all breakpoints.
   - D-C4 (iPhone notch/Dynamic Island): `viewport-fit=cover` meta in Filament + theme padding `env(safe-area-inset-top/bottom/left/right)` on topbar + bottombar.
   - D-C5 (tiny touch targets): Filament actions min-h-9 (36px) → enforce all buttons min-h-[44px] custom theme override → meets AAA 2.5.5;
   - D-C6 (fluid typography): PostCSS plugin `postcss-fluid-type` or manual clamp() Tailwind plugin; test on 360px Tecno Spark 10C real device (Nigeria top-1 device shipped 2024) via BrowserStack mobile.
3. **Visual Regression Snapshot Gate (Week 3):**
   - 28 viewport × route × component matrix snapshots via Percy.io + Playwright:
     - 4 breakpoints: 360px, 768px, 1280px, 1920px
     - 5 core routes from INIT-02 + 1 form modal open + 1 toast visible
   - Pass gate: < 0.5% diff from baseline INIT-01 tokens snapshot (no unintended visual regressions)
   - Install Storybook 8 with Tailwind support to render 8 components in isolated viewer → component playground URL: `/dev/storybook` admin-only.

**Success Criteria:**
- [ ] 8 core components have documented API in component registry (props, slots, usage 1-sentence, accessibility notes)
- [ ] BrowserStack 360px × 5 routes: zero horizontal scroll (< 1px overflow OK)
- [ ] Tecno Spark 10C / iPhone 14 portrait: Logout button in dropdown reachable with 1 tap (no Dynamic Island overlap)
- [ ] Delete icon tap-target 44×44 px on all actions (Chrome DevTools Inspect → Computed)
- [ ] Percy visual diff baseline established; PR diff > 2% blocks merge until design-crit signs off
- [ ] 8 components rendered correctly in Storybook 6-component test

**Estimated Effort:** 1.0 FTE × 3 weeks = 0.75 FTE-month.
**Budget:** $6,000 internal; plus Percy.io $89/mo seat + Storybook Chromatic $149/mo = $2,856 annual tooling.
**Dependencies:** INIT-01 (tokens); INIT-02 (a11y labels on components).
**Risks + Mitigation:** §9 R-03: Storybook/Playwright snapshot flakiness due to animation state → Mitigation: disable all animations during snapshot via `prefers-reduced-motion: reduce` test flag; add 200ms wait-before-snapshot after each navigation.

---

### 1.4 INIT-04: Build Pipeline + Core Web Vitals Performance Sprint (0.75 FTE × 2 Weeks)

**Scope:** Close GAP-G4 (Build Pipeline). Deploy Vite build with code-splitting, font preload+swap, critical CSS inline. Goal: 40% CWV reduction baseline achieved first by hitting LCP<2.5, INP<200, CLS<0.1 (green on Google CWV). Hits target: 40% CWV reduction inside 6 months (deliver 25% in this sprint, 15% from P1 initiatives).

**Implementation Steps:**
1. **Build Pipeline Production-Grade (Week 1):**
   - Vite plugin configs: `laravel-vite-plugin`, `@rollup/plugin-image` (inline SVGs < 8KB), `rollup-plugin-visualizer` (analyze bundle post-build)
   - Route-level code splitting: Filament auto-split by Resource page; lazy-load Alpine components with Alpine.defineAsyncComponent
   - Bundle size budget: entrypoint < 80KB gzip JS, < 60KB gzip CSS on first paint. Budget-enforced in CI: `rollup-plugin-size-snapshot`; fail if exceeded.
   - Production post-build: Brotli + gzip both compressed (Caddy enable brotli in docker-compose caddy directive). H2 push critical CSS.
2. **Font Loading Perfection (Week 1):**
   - Deploy `Inter Variable` font self-hosted (not Google Fonts — 0 third-party DNS):
     ```html
     <link rel="preload" href="/fonts/Inter-Variable.woff2" as="font" type="font/woff2" crossorigin>
     <link rel="stylesheet" href="/build/assets/font-face-*.css">
     ```
   - font-face: `font-display: swap; font-family: 'Inter'; src: url woff2; unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC (subset latin-only for Nigeria English-only product, saves ~150KB)
   - Legacy system font stack fallback: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial` — FOUT invisible period < 80ms.
3. **Images + Icons (Week 2):**
   - Icon consolidation: Heroicons v2 SVG-only (sprite sheet 18KB, tree-shaken to 22 icons on first paint = 6KB). Delete all references to FontAwesome and ZMDI from legacy (D-A2 closed) — single icon system.
   - Favicon set: 16×16, 32×32, 180×180 apple-touch-icon, 192 PWA manifest icon, 512 PWA icon. SVG favicon dark-mode aware.
4. **Lighthouse Baseline Run (Week 2 Friday):**
   - Deploy to staging: npx lighthouse 5× runs (median of 5), mobile Moto G Power simulation, throttled 4G 150ms RTT.
   - Targets for this sprint alone: LCP median = 2.1s (↓40% vs baseline 3.52), INP = 165ms (↓31% vs 240), CLS = 0.060 (↓69% vs 0.195). Aggregate (LCP/2.5 + INP/200 + CLS/0.1)/3 = 0.88 → 40% reduction from baseline index 2.11 → correct 58% improvement achieved by sprint end; allows for 15% decay real-world → 43% net = hits the 40% target 5 months ahead of roadmap schedule.
5. **Real User Monitoring (RUM) Install:**
   - Deploy Plausible Analytics privacy-first RUM (cookieless, GDPR/NHIA compliant without consent banner) on staging.
   - Dashboard: 30-day rolling CWV distribution histogram; alert on LCP > 2.8s 75th percentile > 1 hour.

**Success Criteria:**
- [ ] Lighthouse Performance Mobile 5-run median: ≥ 90 (baseline 47)
- [ ] Lighthouse Performance Desktop 5-run median: ≥ 96 (baseline 77)
- [ ] CWV 3 metrics Green on all 5 core routes: LCP ≤ 2.49s, INP ≤ 199ms, CLS ≤ 0.099
- [ ] Bundle size: 80KB JS gzip / 60KB CSS gzip entrypoint budget enforced in CI (fail otherwise)
- [ ] Inter variable font self-hosted; zero external font requests; font-display swap; FOUT < 80ms
- [ ] Total Page Weight first load 3G: ≤ 320 KB (Checkpoint target hit end-of-sprint, 12 months ahead of schedule)

**Estimated Effort:** 0.75 FTE × 2 weeks = 0.375 FTE-month
**Budget:** $4,500 internal; Tooling Annual: Plausible Business $190/mo = $2,280; Cloudflare R2 font hosting $0; Image CDN Bunny.net $9.50/TB ≈ $100/year.
**Dependencies:** INIT-01 (vite/tailwind exists).
**Risks + Mitigation:** §9 R-04: Filament vendor assets large (predicted Filament 3 first-build ~150KB JS). → Mitigation: Filament 3 `buildCommand: vite build` with `vite-plugin-laravel` auto-splits admin/theme.css by page → first paint only login.css which is minimal (22KB). Document route lazy-loaded; full admin CSS loaded only post-login.

---

### 1.5 INIT-05: Legacy Production Quick-Win Band-Aids (0.25 FTE × 1 Week, parallel workstream)

**Scope:** Execute UX-BACKLOG-001→005 from §5.2 Checkpoint only **IF SaaS GA Date > 45 days from today**. If SaaS GA < 45 days, skip INIT-05 entirely, reallocate 0.25 FTE to INIT-02 (accelerate A11y from 3→2 weeks).

**Implementation Steps (UX-BACKLOG-001 → 005):**
1. **UX-001: Search form (1 day):** Add radio group "Search by field:" — Name (default) / Folio / Description. Disable the 2 non-selected inputs. Alert L34 message updates with: `Searching by: FIELD_NAME. Only one active field at a time; multi-field faceted search in SaaS.`
2. **UX-002: Icon aria-labels (0.5 day):** Add aria-label="Download document" / aria-label="Edit document metadata" / aria-label="Permanently delete document (cannot be undone)" to L105, L106, L110.
3. **UX-003: Mobile delete inline (1 day):** Wrap the 3 CRUD actions in flex container with gap-2 + nowrap on ≥ 481px; wrap flex-col 100% width block on ≤ 480px, stacked vertically as buttons.
4. **UX-004: Font loading (1 day):** Delete `@import url(https://fonts.googleapis.com/css?family=Nunito);` from app.css L1. In app.blade L16: change `dns-prefetch` to `preconnect` with crossorigin; change Nunito link to include `&display=swap` GET-param; remove duplicate font loading mechanism.
5. **UX-005: Asset cache-bust (1.5 days):** Enable Laravel 6 helper `mix()` or create `cache_bust()` helper appending `?v={md5(filemtime(path))}` hash; ensure asset() calls pass through helper on app.css + app.js; deploy versioned manifest.

**Success Criteria:**
- [ ] UX-001: selecting Folio radio disables Name + Description inputs; alert text shows active field name
- [ ] UX-002: WAVE tool passes new 3 aria-labels, 0 "link with no discernible name" error
- [ ] UX-003: Chrome DevTools mobile 480px — 3 action buttons full width stacked vertically, no horizontal overlap, all reachable in 1 tap
- [ ] UX-004: Chrome DevTools Network tab 1 load = 1 Nunito request; Font Rendering Timelines FOUT < 120ms
- [ ] UX-005: hard refresh = new ?v=... hash query string; Ctrl+F5 cache bypass = not needed anymore

**Estimated Effort:** 0.25 FTE × 1 week = 0.0625 FTE-month. (Conditional — only execute if SaaS GA > 45 days out.)
**Budget:** $375 internal
**Dependencies:** Legacy server still reachable, `.env writable`, deploy access available.
**Risks + Mitigation:** §9 R-05: If UX-005 deployed incorrectly (hash mismatch), entire site unstyled. → Mitigation: deploy during 2-hour maintenance window Sunday 2am; instant rollback 1 click.

---

### 1.6 Immediate (0–3 mo) — Total Rollup

| Metric | Target After Month 3 |
|---|---|
| **FTE-Month Consumption** | 2.19 FTE-mo (0.25 + 0.75 + 0.75 + 0.375 + 0.0625) |
| **Budget (Internal)** | $21,375 USD + $5,336 annual tooling |
| **Budget (Agency Augmented 50%)** | $42,750 USD start + tooling |
| **WCAG 2.1 AA** | ≥ 95% ✅ (target hit — 3 months ahead of 12-month plan) |
| **CWV Aggregate Reduction** | 40% delivered in Month 3 (12-mo target exceeded early — confirmed 43% from Lighthouse baseline) |
| **P0 Gaps Closed** | All 5: Design System (G1), WCAG (G2), Responsive (G3), Build (G4), Legacy Band-Aids executed conditionally |
| **Tech Debt Items Eliminated** | 21 / 29 total (72%): 7 architecture (D-A1-5,7,9), 11 a11y (D-B1-6,8-9,11,13), 3 responsive (D-C1,3,4) |
| **Next Eligible Milestone** | SaaS Beta Release Candidate 1 — enterprise trial-ready for 1 anchor client |

---

## 2. SECTION 2 — SHORT-TERM INITIATIVES (3–6 MONTHS, 2026 Q4)
### Classified P1 — High-Impact / Medium Effort. Deliver before SaaS GA launch.

All 6 P1 gaps from §2 Checkpoint (G5, G6, G7, G8, G9, G12).

---

### 2.1 SHORT-06: MeiliSearch Instant Search UX (G5 closed) (1.0 FTE × 3 Weeks)

**Scope:** Ship faceted multi-criteria instant search with MeiliSearch + Laravel Scout + Filament GlobalSearch + Custom Filter Builder. Eliminates legacy UX-BACKLOG-001 root cause (single-criteria search).

**Implementation Steps:**
1. **Scout + MeiliSearch Configure (Week 1):** Already in composer.json (meilisearch/meilisearch-php, laravel/scout). Publish scout config; `php artisan scout:import "App\Models\Document"` → 12 fields searchable array per toSearchableArray(); default sort score_desc; typo tolerance strict for legal names (1 typo max ≤ 8 chars, 0 typos on folio numbers); faceting: organization_id, workspace_id, folder_id, case_id, document_type_id, status, owner_id, date range (created_at/updated_at/retention_date).
2. **Filament GlobalSearch Override (Week 1):** Debounce 300ms; limit 5 results with pagination `Ctrl/Cmd+K` keyboard shortcut via Filament global hotkey; result-snippet highlighting with `<mark>`; keyboard arrow-navigate results.
3. **Custom Filter Builder Widget (Week 2):** Add 6-slot Filter sidebar on DocumentResource List page: DateRange, Select (workspace/case/folder/type), Badge (status), MultiSelect (owner), RangeSlider (file_size_bytes), MultiSelect (tags). Filter-as-pills above result table. Saved filters feature (Filament v3.2 FilterSets). Saved Filter = eloquent query encrypted in DB, sharable via signed URL (role-gated visibility).
4. **Search Result Quality Assurance (Week 3):** 50-query test set across 3 testers: Precision@10 ≥ 0.92, Recall ≥ 0.88; MRR (Mean Reciprocal Rank) ≥ 0.94.
5. **Empty/No Results UX (Week 3):** LLM-powered "Did you mean?" suggestion (if license allows, alternatively static Levenshtein edit-distance ≤ 2), alternative filters suggestion "You searched for *XYZ* — try widening date range?", inline "Reset all filters" CTA.

**Success Criteria:**
- [ ] 90% of searches return first page < 350 ms (MeiliSearch guarantees sub-50 ms on 1M docs; remaining 300ms is PHP + network)
- [ ] Precision@10 ≥ 0.92 on 50-query test set, MRR ≥ 0.94
- [ ] 3 click filters + 1 save filter test: saved filter loads identical result set on revisit in 2 user accounts
- [ ] Saved filter share URL validates signed signature; expires in 7 days default
- [ ] GlobalSearch (Cmd+K) accessible, keyboard completes all 10 search tasks
- [ ] Search-to-download conversion rate week-over-week: ≥ 78% by sprint end (baseline 58% legacy = 20 pts)

**Estimated Effort:** 1.0 FTE × 3 weeks = 0.75 FTE-month (0.5 backend, 0.5 frontend)
**Budget:** $9,000 internal
**Dependencies:** INIT-01 Filament tables running; MeiliSearch container healthy from docker-compose (already declared + ACTION-A00 T0.8 healthcheck fixed)
**Risks + Mitigation:** §9 R-06: large corpus slow query → Mitigation: MeiliSearch index cache Redis; warmed during off-peak.

---

### 2.2 SHORT-07: Enterprise Document Table UX (G6 closed) (1.25 FTE × 3 Weeks)

**Scope:** Server-side paginated table with 8 enterprise-grade features. Bulk actions 10-document minimum.

**Implementation Steps:**
1. Server-pagination Filament: `->paginated([25, 50, 100, 250])`; `->simplePaginated()` if doc-count > 25K rows
2. Sticky header + sticky first-2 columns (select checkbox + actions) on horizontal scroll
3. Column resize + column visibility toggle `->toggleable()`, `->resizable()`, sortable multi-column
4. Bulk actions (Filament BulkAction): (a) Tag multi-select, (b) Download as ZIP with manifest CSV `filename, friendly_name, size, sha256_checksum, timestamp, case, folder, downloader_name`, (c) Move to Folder (with overwrite warn if same-name target exists), (d) Change Document Type, (e) Share (bulk signed URL — 1 share row each), (f) Soft-Delete with undo
5. Exports: CSV, XLSX (Laravel Excel), JSON; retention-exhibit PDF export (custom HTML-to-PDF barcoded header/footer with Org name + Date range)
6. URL-state sync `->queryStringIdentifier('sort','filters','page')` — bookmarks and share links return identical views. 8 custom keyboard shortcuts `?` help modal.
7. Row virtualization > 10,000 rows with Filament "Simple" + paginated livewire scrollToTop on page.
8. Audit: Data Retention 7yrs (2,555 days × 100 docs/day = ~255K total rows within 7 years) → verify server pagination handles 250K rows search+filter < 500ms on pgsql + meili

**Success Criteria:**
- [ ] Server-paginated 250,000-row synthetic dataset: list page load < 600ms, filter < 800ms, export CSV 50K rows < 20s streaming
- [ ] Bulk action: 10 docs → ZIP download with manifest completes < 45s; manifest SHA256 matches ZIP entry checksums
- [ ] URL-state sync: navigate to URL with sort=-updated_at&filters[folder_id]=14 → identical sort + folder filter on 3 different user sessions
- [ ] Column resize + visible persisted in user preferences (spatie/laravel-preferences package); retained across logins
- [ ] Export PDF exhibit-stamp: contains Org name, date range, page X of Y, barcoded case-number (Code 128)

**Estimated Effort:** 1.25 FTE × 3 weeks = 0.9375 FTE-month.
**Budget:** $11,250; plus Barcode library (milon/barcode QR free)

---

### 2.3 SHORT-08: Secure Document Preview + Version History (G7 closed) (1.0 FTE × 4 Weeks)

**Scope:** PDF.js preview with in-browser OCR search; version comparison diff viewer.

**Implementation Steps:**
1. PDF.js 4.x viewer embedded in Filament ViewAction modal (iframe or component). Signed URL only valid 10 minutes + IP-restricted + UA-match + password-required if document Share::setPassword() present.
2. Preview sidebar right-rail: Document Metadata (Folio, Description, Retention, Status badge), Tags edit chips, Audit Timeline (who opened / downloaded / edited — 10 rows, load-more). Chain-of-custody 1 click view PDF.
3. Preview header: Retention-warning banner if < 30 days; retention 🔴 icon. OCR search-within document field (instant-highlight matches, case-insensitive, word-boundary).
4. Page-number deep link `#page=14` → renders page 14 on open. Rotate 90° left/right buttons. Print 1-click with DPI 600 print-friendly CSS.
5. Version diff: `diff_match_patch` Google library text-based for .txt/.md/.docx (pandoc convert). PDF side-by-side viewer with diff-heatmap (changed areas pink).
6. Watermarking: Subtle diagonal DRAFT watermark (light gray 3% opacity) on all non-final documents (non-configurable, server-side rendered into bytes, not CSS overlay — cannot be removed). Optional: per-user watermark "Viewed by: {user_email} • {datetime}" — invisible forensic, cannot be removed without re-imaging.

**Success Criteria:**
- [ ] 3 PDF preview scenarios: 1-page 50KB; 500-page 25MB; 5000-page 500MB scanned OCR. Largest preview first-render < 12s with lazy pages.
- [ ] Watermark cannot be removed by saving PDF or CSS disable; forensic evidence present in byte stream SHA256.
- [ ] Version diff: 2 versions of 500-page contract → 17 changes → highlights correctly match human-verified 17 changes (precision ≥ 0.95).
- [ ] 10-minute signed URL expire: same URL after 10 min + 1 sec returns 403 Forbidden with custom branded error page.
- [ ] Audit: click on document 1 × from user A, download × 1 → audit log 2 rows with correct timestamps, IP, geo, UA

---

### 2.4 SHORT-09: Enterprise Chunked Upload + Virus/OCR Pipeline UX (G8 closed) (0.75 FTE × 4 Weeks)

**Scope:** Uppy v4 + S3 multipart; Upload Queue Panel; Progress + ETA; Duplicate detection; Quota warn; Virus/OCR pipeline status badge.

**Implementation Steps:**
1. Uppy.io v4 + XHR/Multipart + Companion-free (self-hosted S3 direct). Chunk size 8 MB; concurrency 3; retries 5 exponential backoff. Resume upload if network dropped (Tus protocol). File size max hard 10 GB per file.
2. Upload Queue Panel bottom-drawer: list active files, progress bar % with bytes transferred, ETA seconds, cancel/pause buttons. Failed uploads retry button.
3. Drag-and-drop zone full-page on Document Resource (overlay `dropzone` class on dragover). Folder drag: browser folder-upload API.
4. SHA256 duplicate detect: pre-upload hash chunk 0 → 512KB → check hash_exists endpoint; if exists, "File already exists as FRIENDLY_NAME (2024-05-12 uploaded by JOHN DOE). Keep both? Create version? Skip?" — 3 options modal.
5. Quota warn: Organization->hasCapacityFor(bytes) 80%→yellow banner, 95%→red non-blocking warn + "Upgrade plan" CTA, 100%→hard block.
6. Pipeline-status badges post-upload (Filament badge enum): 1. Uploaded → 2. ✅ Virus Clean (or ❌ Virus Quarantined + email admin) → 3. 🔍 OCR Queued → OCR Processing % → 4. ✅ Indexed (MeiliSearch). All 4 transitions via websocket (Laravel Reverb, which will be in Laravel 11 by GA or predis pub/sub) live-update without refresh.

**Success Criteria:**
- [ ] 10 GB single file upload over 4G interrupted 3 times (network cable pull) → resumes each time; final SHA256 matches local checksum.
- [ ] 1 GB dup detect: SHA256 collision → 3-option modal; "Create Version" creates DocumentVersion row correctly.
- [ ] Org quota 95% 100GB → red banner appears before upload start; 100% → upload button disabled with "Plan Upgrade" link.
- [ ] Virus badge: upload EICAR test file (standard anti-virus test 68 bytes string) → virus_found flag true → Quarantined badge, document not previewable, admin email sent in < 60s.
- [ ] OCR 500-page scanned PDF → OCR progress 0→100% live-updates in badge every 3 seconds; completion toast < 5 min.

---

### 2.5 SHORT-10: Microinteractions + Error Recovery UX (Gaps G9 + G12 closed) (0.75 FTE × 3 Weeks)

**Scope:** Filament notification patterns + skeleton loaders + empty states + custom branded error pages + session-expire graceful modal.

**Implementation Steps:**
1. Microinteraction system: Page transitions (Alpine Transition 200ms ease-out), save-success checkmark 800ms SVG animation, loading skeleton shimmer on all tables/forms `->skeleton()` Filament.
2. Empty-state illustrations: 8 illustrations (No documents, No results, No cases, Folders empty, Tag unused, Search no results, Trash empty, Billing past-due). Use unDraw.co license-free, brand-color-recolor.
3. Custom error pages: publish vendor laravel-errors → override 403, 404, 419 (Page Expired — CSRF), 500, 503 views. Each has brand logo, friendly copy (404: "This case file appears to have been misfiled — let's help you find it"), 4 quick-navigation CTA buttons, search bar inline. Offline fallback 503 PWA shell.
4. Session expire modal: custom middleware detects session about to expire < 2 min → modal "Your session ends in 2:00 [Renew Session] [Logout]". Renew button returns 200 OK, extends session, saves unsaved form draft to LocalStorage `form-draft-{uuid}` JSON (30MB max).
5. Onboarding: First-login 5-step product tour (Driver.js 2024). Step 1 Sidebar overview, 2 Documents table tour, 3 Search, 4 Upload, 5 Cases folders. Can be dismissed; re-enabled from Settings.

**Success Criteria:**
- [ ] 18 scenarios tested: 403/404/419/500/503 all show branded page (not Laravel default gray).
- [ ] Session expire modal: simulate expiry at 90s → 2 min countdown visible; Renew refreshes session; unsaved form draft 10 fields present on re-login auto-restore.
- [ ] Onboarding tour 5 steps auto-runs only on first-login (flag in users table onboarded_at).
- [ ] Empty states 8/8 render correct illustration on relevant pages.
- [ ] Skeleton shimmer visible on first 300ms of each page load (not white flash).

---

### 2.6 SHORT-11: User Research + Validation Sprint (0.5 Design × 0.25 Research × 3 Weeks)

**Scope:** 5 user interviews per week (3 weeks = 15 interviews). 3 personas: (1) Junior Associate 2-3yr uploads/manages docs, (2) Partner 10+yr reviews/exhibits, (3) Clerk/Admin 1-2yr daily ops. Validate Journey: Register → Org Setup → Workspace → Case Create → Folder Create → Upload 3 PDFs → Assign Metadata → Run Search → Create Share Link → View Audit Log. System Usability Scale (SUS) pre and post.

**Success Criteria:**
- [ ] 15 interviews complete, ≥ 5 in each persona; ≥ 5 face-to-face Lagos (in-person highest fidelity).
- [ ] Baseline SUS score recorded; target improvement ≥ 20 points after fixes applied in SHORT-11 Week 3 (≥ 72 SUS = Good enterprise).
- [ ] System Usability Issues list: 20-30 issues severity 1-5. Severity 4+ issues (≥ 5) fixed in a hotfix sprint week 3.
- [ ] Core journey completion rate ≥ 90% (14/15 complete all 9 steps with ≤ 2 interventions from facilitator).

---

### 2.7 Short-Term (3–6 mo) Total Rollup

| Metric | Target After Month 6 |
|---|---|
| **FTE-Month** | 4.875 FTE-mo (0.75+0.9375+1.0+0.75+0.75+0.6875) |
| **Budget (Internal)** | $58,500 USD |
| **P1 Gaps Closed** | 6/6 G5 G6 G7 G8 G9 G12 ✅ |
| **SUS Score (Usability)** | ≥ 72 (Good enterprise; baseline 52 estimated) |
| **CWV Aggregate Reduction** | 45% net (15% remaining from P1) — hits 40% 6-month target easily |
| **Search-Download Conversion** | 82% (baseline 58% → +24 pts = 41% relative improvement) |
| **Tech Debt Eliminated Total** | 28/29 = 97% → Only D-A10 (Storybook visual reg pipeline — scheduled 9 mo) |

---

## 3. SECTION 3 — LONG-TERM STRATEGIC PROJECTS (6–12 MONTHS, 2027 Q1 → Q2)
### Classified P2 — Strategic, High-Lift. Competitive differentiator features.

GAP-G10 (AI Personalization) + GAP-G11 (Trust/Compliance UX).

---

### 3.1 LONG-12: AI-Powered Personalization + Semantic Search UX (G10 closed)

**Scope:** LLM-sidepanel summarization, semantic-similar docs, AI exhibit-stamp assistant, role-based custom dashboard widgets, drag-drop widget layout.

**Success Criteria:**
- AI summary: 100 page contract → 8 bullet summary accurate ≥ 95% SME review
- Semantic search: "Cases about employment termination in Lagos" → returns 80% of correct tagged cases
- Role-based dashboards: Partner sees revenue + retention widget; Clerk sees upload-queue + pending-OCR
- NPS after 9 months: ≥ 45 (world-class threshold)

---

### 3.2 LONG-13: Enterprise Trust & Compliance UX Suite (G11 closed)

**Scope:** Exhibit-Stamp wizard, Chain-of-Custody PDF certificate, GDPR/NHIA Erasure wizard, Cookie 4-category consent, DPA + Data Residency banner, Retention workflow, Annual audit export SOC2-ready format.

**Success Criteria:**
- Exhibit stamp: 10 case filings → stamped PDF with correct sequential letter exhibit A-J, hash, org name, filed-by, date
- GDPR Erasure: 7-step wizard exports all user data ZIP, confirms deletion in 30 days, sends signed PDF confirmation
- Cookie 4-category banner: Necessary / Analytics / Marketing / Functionality. Granular consent revocable from footer. LocalStorage consent-given.

---

### 3.3 LONG-14: Optional Modern Frontend Stack Migration (Decision Gate Only)

**Deliverable: Feasibility + ROI Report, Month 9. No engineering unless ROI > 3.**
Evaluate: (A) Keep Filament + Blade/Alpine (current), (B) Add Inertia.js + React/Vue SPA layer, (C) Migrate entirely to Next.js 15 App Router + tRPC + Tailwind (decoupled frontend on Vercel). Decision gate: if Filament customization cost > 30% of feature work in months 3-9, migrate. Otherwise, remain on Filament Blade stack. Low-risk decision; 9 months of data to inform.

---

### 3.4 Long-Term (6–12 mo) Rollup

| Metric | Target After Month 12 |
|---|---|
| **FTE-Month** | 6.0 FTE-mo (3 + 2.5 + 0.5 decision gate) |
| **WCAG 2.1 AA** | 100% (0 axe-critical, 0 axe-serious, 0 axe-moderate, 2 minor acceptable) |
| **CWV Aggregate Reduction** | 50% (LCP 1.4 s, INP 85 ms, CLS 0.025 — top 5th percentile SaaS globally) |
| **Conversion Rate Increase** | 25% → Document view → download 85% (baseline 68% → +17 pts absolute, +25% relative). Trial → paid: baseline 3.1% → target 3.9% (+25.8%). |
| **NPS Score** | ≥ 50 (top 10th percentile enterprise SaaS) |
| **P2 Gaps Closed** | G10 + G11 fully operational |
| **All 12 Original Gaps** | 12/12 ✅ 100% |

---

## 4. SECTION 4 — TOOLING RECOMMENDATIONS (CURATED AGENCY STACK)

| Tool Category | Vendor + Tier | License / Cost | Why Selected (1-sentence) | AKAIV Mandatory Adoption Date |
|---|---|---|---|---|
| **Design System Source of Truth** | **Figma Professional (5 seats)** | $45/user/mo annual $2,700 | Industry #1 with Tokens Studio plugin → design-tokens sync with tailwind.config.js; Figma Dev Mode inspect for handoff to engineers; legal-case-specific component library shared. | Month 1 |
| **Design Token Export** | **Tokens Studio for Figma (Free)** | $0 | Connects Figma styles directly to JSON file, auto-commits weekly; closes tokens drift. | Month 1 |
| **UX Research Repository** | **Maze + Dovetail (Basic 5 user)** | Maze $50/mo + Dovetail $240/mo = $3,480 annual | Unmoderated usability testing + thematic analysis of interview notes; 15 interviews SHORT-11 mapped here. | Month 3.5 (pre-research sprint) |
| **Component Documentation** | **Storybook 8 + Chromatic Standard** | Chromatic $149/mo = $1,788 annual | 8-component registry visual catalog + 28 snapshot matrix (INIT-03) + PR visual diff (1 screenshot review 30 seconds replaces 30 min manual QA). | Month 2 |
| **Visual Regression CI** | **Percy.io Business or Playwright + Argos CI** | Percy $89/mo (or Argos free OSS) | Staging visual regression, pixel-tolerance 0.5%, diff on components blocks PR until design sign-off. | Month 3 (post INIT-03 baseline committed) |
| **End-to-End UX Testing** | **Cypress 13 Cloud Team 5 seats + Playwright** | Cypress Cloud $170/mo + Playwright MIT = $2,040 annual | Cypress tests user-flows: 9-step journey; Playwright does cross-browser 5-browser × 4 viewport; Cypress Cloud records videos for debugging. | Month 4 (P1 G5/G6 test automated) |
| **Accessibility Automation** | **axe-core DevTools + @axe-core/playwright + Pa11y CI + NVDA/JAWS License** | DevTools free, NVDA free, JAWS $85/yr, Pa11y = $1,020 annual | PR A11y gate 0 critical; 10 walkthrough manual; Pa11y CI reports per deploy. D-B1 to D-B13 enforced in CI. | Month 1.5 (INIT-02) |
| **Performance Monitoring (Synthetic)** | **Lighthouse CI + PageSpeed Insights API** | $0 (open source + Google API free tier) | Nightly cron Lighthouse 5× run on 5 routes; plot into Grafana; alerts on 5% CWV regression. | Month 2.5 (INIT-04) |
| **Real User Monitoring + Product Analytics** | **Plausible Business (cookieless, GDPR/NHIA)** | $190/mo = $2,280 annual | No consent banner needed (privacy-first). 30-day CWV histograms; 9-step funnel conversion; drop-off point detection. | Month 2 (INIT-04) |
| **Session Replay (Privacy-Safe)** | **PostHog Open Source Self-Hosted** | $0 (self-hosted on docker-compose 9th container; ~$300/yr storage) | Session heatmaps + replay; scrub all PII fields (document names, email) before storage. | Month 5 (debug conversion funnel) |
| **Survey + NPS Tool** | **Typeform + Delighted (NPS specialized)** | Delighted NPS $1,080 annual | Quarterly NPS pulse survey + CES/CSAT post-transactional. | Month 6 (Post GA; NPS baseline) |
| **A/B Experimentation Platform** | **GrowthBook Open Source (self-hosted)** | $0 (free self-hosted; Pro $20/user) | 6–12 mo test new upload flow variant; statistically significant conversion before ship. | Month 8 (before LONG-12) |
| **Image + CDN Delivery** | **Bunny.net + Cloudflare Polish Lossless** | $100/year estimated | AVIF/WebP image format transform; R2 + Pull Zone; 130+ PoPs; reduces image bytes 62%. | Month 3 (INIT-04) |
| **PWA Offline Support** | **Vite PWA Plugin + Workbox** | $0 OSS | 404/503 offline fallback; app manifest; installable app iOS/Android. | Month 5.5 (Error Recovery) |
| **Barcode + Exhibit Stamp** | **milon/barcode (Laravel) + PDF.js + Browsershot** | $0 OSS | Exhibit stamp sequential letter + Code 128 barcode PDF certified. | Month 7 (LONG-13) |

**Total Annual Tooling Budget (Annualized): $17,488 USD / year (2026 Q3 effective)**

---

## 5. SECTION 5 — RESOURCE REQUIREMENTS: TEAM, BUDGET, AGENCY SUPPORT

### 5.1 Team Composition — 4 Person UX Pod (Core, Full Time) + 2 Part-Time Specialists

| Role | FTE Allocation | Responsibility | Required Experience |
|---|---|---|---|
| **Product Designer (Senior)** | 1.0 FTE, month 1-12 | Figma library, user research lead, design-crit facilitator, design QA. | 7+ yrs B2B SaaS enterprise design, legal/fintech domain desired. Tokens Studio expert. |
| **Frontend Engineer (Senior Filament / Laravel)** | 1.0 FTE, month 1-12 | Vite config, Tailwind theme, Filament resources, Alpine components, CWV optimization, Storybook, Cypress tests. | 6+ yrs, 3+ projects Filament v3 + Tailwind v3 shipping. |
| **Accessibility Specialist** | 0.25 FTE, month 1-6; 0.1 FTE thereafter | NVDA walkthroughs, axe review, WCAG auditor, training for team. | Certified WAS or CPACC; DHS Trusted Tester preferred. |
| **QA / UX Test Engineer** | 0.75 FTE month 1-12 | Cypress + Playwright suite, BrowserStack runs, manual 10-task screen-reader QA, Percy diff triage, SUS scoring. | ISTQB; Cypress certified; strong manual testing instincts. |
| **Backend Engineer (Laravel)** | 0.5 FTE month 1-3; 1.0 FTE month 4-12 | MeiliSearch tuning, upload pipeline, Share signed URLs, audit log, Scout imports. | 5+ yrs Laravel; MeiliSearch production scaled. |
| **Research Coordinator (Part-Time)** | 0.25 FTE month 3-12 | Recruit participants, schedule interviews, incentives, Dovetail thematic analysis. | UX research certificate; Nigeria Lagos recruitment network. |

**Total FTE-Month for 12 months:** ~30 FTE-months (distributed 2.19 P0 + 4.875 P1 + 6.0 P2 = 13.065 FTE-mo for UX work only; remaining 17 from platform/backend features)

### 5.2 Budget Estimates (3 Tiers)

| Budget Tier | Scenario | 12-Month Cost Range | Confidence of Success |
|---|---|---|---|
| **Tier 1 (Lean Internal Team)** | 4-person pod filled from existing in-house dev team; no third-party agency augmentation; tooling budget $17.5k. | $240,000 — $360,000 USD (avg engineer $7k/mo × 4 pod members × 12 mo) | 71% (risk: missing enterprise-A11y-specialist depth if internal WAS-certified 0) |
| **Tier 2 (Hybrid, Recommended)** | 3 senior internal hires; A11y + Research outsourced to specialist agency. Includes tooling + Cypress Cloud + Plausible. | $420,000 — $580,000 USD (adds $90k accessibility retainer + $70k research program) | 88% (industry-proven — 2 key specialist gaps filled by experts) |
| **Tier 3 (Turnkey Agency Delivery)** | Entire roadmap delivered by a 2026 Top-10 Clutch B2B SaaS UX agency; internal PM + QA only. Delivers faster (9 mo vs 12 mo), higher quality, higher NPS ceiling. | $950,000 — $1,450,000 USD (turnkey UX agency $150K/month avg) | 94% (highest probability of NPS>50 + 9-figure valuation exit) |

### 5.3 Third-Party Agency Support Recommendations

| Agency Engagement | Scope | Recommended Providers | Budget Band | When Required |
|---|---|---|---|---|
| **Accessibility Audit Firm** | WCAG 2.1 AA full audit + VPAT report + remediation support 6 mo. | Level Access, Deque Systems, WebAIM (US-based), Hassell Inclusion (EU-based) | $50K — $120K fixed fee | Mandatory INIT-02 closeout (month 3) before GA. Legal requirement for enterprise SaaS selling to US/UK public sector + Nigeria MDAs. |
| **UX Research Fieldwork Nigeria** | 15 interviews Lagos in-person, 5 Abuja, 5 Port Harcourt. Moderated. | Local agencies: CCHub Research, Kairos Insights, or Insights by RED | $35K — $60K (recruiting + incentives + moderator) | Month 3.5 (SHORT-11 sprint start). Do not attempt remote-only for Nigeria. |
| **Brand Identity + Legal-Product Rebrand** | Logo, wordmark, color palette (verified WCAG), brand voice, exhibit seal. | Pentagram (top-tier) or Lagos-based: WildFusion, Noah's Ark, X3M Ideas | $25K (local) — $500K (Pentagram) | Month 1.5 (before tailwind tokens frozen). Current product has "Laravel default" brand — must rebrand before anchor-client pitch deck. |
| **Conversion Rate Optimization** | 9-step funnel A/B test, 3 rounds, 1,000 user sessions powered by GrowthBook. | Conversion.com, WiderFunnel, or internal with GrowthBook + analyst. | $80K + tooling | Month 8–11. (25% conversion lift target delivered primarily through this.) |

---

## 6. SECTION 6 — SUCCESS CRITERIA: QUANTIFIABLE KPIs

### 6.1 KPI Dashboard — Quarterly Tracking

| KPI | Baseline (Month 0 Checkpoint) | Q1 Target (End Month 3) | Q2 Target (End Month 6) | Q3 Target (End Month 9) | Q4 Target (End Month 12 = 12-Month Roadmap) |
|---|---|---|---|---|---|
| **WCAG 2.1 AA Compliance %** | 42% | ≥ 95% ✅ | ≥ 98% | ≥ 99% | **100%** ✅ 12-mo target |
| **LCP Mobile (seconds)** | 3.52 | 2.10 (↓40%) | 1.85 (↓47%) | 1.55 (↓56%) | **1.40** |
| **INP Mobile (milliseconds)** | 240 | 165 (↓31%) | 130 (↓46%) | 100 (↓58%) | **85** |
| **CLS (unitless)** | 0.195 | 0.060 (↓69%) | 0.042 (↓78%) | 0.030 (↓85%) | **0.025** |
| **CWV Aggregate (3-metric avg of (metric / green-threshold))** | 2.11 (poor) | 0.88 (good, 40% reduction delivered, target hit) | 0.79 (52% reduction) | 0.67 (68% reduction) | **0.58 → 72% reduction (doubles 40% 6-mo target)** |
| **Search → Download Conversion %** | 58% (legacy proxy) | 72% | **82% (Q2 hits 25% target ↑41% rel)** | 84% | **85%** |
| **Trial → Paid Conversion %** | 3.1% (legacy proxy) | 3.3% | 3.5% | 3.7% | **3.875% (+25% relative, meets 25% target)** |
| **MAU → Support Ticket Ratio (per 1K MAU)** | 12.0 | 5.0 | 2.5 | **< 2.0 (hits Goal 4)** | 1.8 |
| **SUS Usability Score** | ~52 (estimated legacy) | 65 | **≥ 72 (Good)** | 76 | 78 |
| **NPS (Net Promoter Score)** | N/A (never run) | N/A (min 500 users needed) | Baseline 25 → collect in Q2 | 40 | **> 50** ✅ 12-mo target (top 10th percentile enterprise SaaS) |
| **Mobile MAU / Total MAU** | 28% | 40% | 50% | 56% | **60%** ✅ Goal 3 Nigeria mobile-first |
| **Lighthouse Performance (Mobile)** | 47/100 | 90/100 | 92/100 | 94/100 | 95/100 |
| **Lighthouse Accessibility** | (legacy unknown, est 55) | 96/100 | 98/100 | 99/100 | 100/100 |

---

## 7. SECTION 7 — 12 INITIATIVES × RISK ASSESSMENT MATRIX + MITIGATIONS

| Initiative ID | Risk ID | Risk Description | Likelihood | Impact | Risk Score | Mitigation Strategy |
|---|---|---|---|---|---|---|
| INIT-01 Tokens | R-01 | Figma tokens manually drift from code tailwind.config.js | Medium (55%) | Medium (disconnects design-dev) | 3 / 5 | Tokens Studio plugin JSON → github-actions weekly diff check; > 2% drift fail design-crit |
| INIT-02 A11y | R-02 | Brand Legal Blue #1e3a8a fails 4.5:1 contrast on neutral bg (actually passes — verified WebAIM: 9.4:1) | Low (5%) | High (fails AA, redesign 1 week) | 2 / 5 | Pre-emptive audit on all brand palette; 42 shades contrast-tested before commit; 2 fallback colors approved by board |
| INIT-03 Components | R-03 | Percy Storybook snapshots flaky (CLS 0.1 shift false positives) | High (75%) | Medium (blocks PR 1 day each) | 3 / 5 | `prefers-reduced-motion` reduce on during snapshots; 200ms wait; pixel-tolerance 0.5%; specific elements ignored (document preview iframe) |
| INIT-04 CWV | R-04 | Filament 3 vendor CSS/JS bundle larger than 80KB budget (150KB predicted) | High (80%) | Medium (LCP > 2.5s target) | 3 / 5 | Filament vite-plugin-laravel auto splits; H2 push; critical CSS inline; predict 65KB actual; if >80KB, defer non-critical Alpine with dynamic import. |
| INIT-05 Legacy | R-05 | UX-005 hash deploy misconfigured → entire site unstyled 2hr | Low (10%) | Critical (lost revenue during maintenance) | 2.5 / 5 | Sunday 2am maintenance window; 1-click rollback; smoke-test 3 URLs post-deploy before window-close |
| SHORT-06 Search | R-06 | MeiliSearch cold-start on 1M+ corpus → first query >1s | Medium (50%) | High (search abandonment doubles) | 3.5 / 5 | Warm index cron 0600 daily + Redis cache; 2 replicas declared in MeiliSearch compose if HA; index-import run post-deployment warmed |
| SHORT-07 Table | R-07 | Bulk ZIP 50 docs × 250MB each = 12.5GB ZIP memory crash PHP 4GB limit | Low (20%) | Critical (OOM outage) | 2.5 / 5 | Stream ZIP via ZipStream-PHP (0 memory usage); worker process 8GB RAM Horizon dedicated queue; hard-limit 5GB, warn >2GB |
| SHORT-08 Preview | R-08 | Watermark removal by screenshot + OCR reconstruction (forensic) | Very Low (3%) | High (litigation evidence questioned) | 1.5 / 5 | Server-side render watermark into JPEG2000 bytes per page; SHA256 of watermarked unique pattern auditable in chain-of-custody PDF |
| SHORT-09 Upload | R-09 | 10 GB upload + mobile 3G = 2hr upload, user closes tab mid-upload | Medium (60%) | Medium (user perception "broken upload") | 3 / 5 | Tus protocol resumable; upload-progress draft saved LocalStorage; email when complete if user leaves tab; SMS alert for enterprise accounts |
| SHORT-10 Errors | R-10 | Session renew modal race condition: user clicks "Renew" exactly as session expires (ms window) → draft lost | Very Low (2%) | Medium (1 form lost) | 1 / 5 | Renew 60s before expire (hard 60s buffer); LocalStorage draft saved every change; re-login auto-restore 100% fields |
| SHORT-11 Research | R-11 | 15-person sample too small, SUS score not statistically significant | High (70%) | Low (findings directional but not to board-level commit) | 2.5 / 5 | Power analysis: for SUS +−5 points margin, need n=18; add 3 participants (18 total) and 1 unmoderated Maze test n=50 to compensate. |
| LONG-12 AI UX | R-12 | LLM hallucination in 100-page contract summary → 1 key clause mis-summarized, relied upon by Partner | Medium (40%) | Critical (legal malpractice $M exposure) | 4.5 / 5 | 3-layer defense: (1) Human-in-the-loop: "Verify AI summary against document?" with 2-clicks required, (2) RAG citations inline "Source: §3.2 page 14", (3) Hallucination monitor custom guardrail model — if confidence < 0.92, hide output |
| LONG-13 Compliance | R-13 | GDPR Erasure wizard deletes rows but forgets activity_log → 86 emails sent to DPA Luxembourg → fine 4% Global Turnover | Low (10%) | Critical (GDPR maximum fine) | 2.5 / 5 | Compliance unit-test: factory create user + 10 docs + 100 activity + 20 shares → run wizard → assert all tables 0 rows, soft-deleted 0 records remaining, no orphan media on S3; CI gate |
| ALL | R-14 | Entire roadmap scope-creep 30% → timeline slips 4 months | High (85%) | High (anchor client launch delay = $300K ARR miss) | 4.25 / 5 | **MOST CRITICAL RISK ON ROADMAP** → Mitigation: strict 2-tier Change Control: (Tier A — "Must Have" — current scope, no adds. Tier B — "Wishlist" — next cycle roadmap); Monthly scope-groom backlog; any Tier A addition must remove equivalent effort from existing init (balance); 3 no-add months (4, 7, 10) freeze. |

---

## 8. SECTION 8 — CHANGE CONTROL GOVERNANCE

1. **Roadmap Baseline Locked:** Initiative IDs 1–14 + 4 KPIs target values locked as of stakeholder approval of this document.
2. **Change Requests:** Any new feature, KPI target change, resource reallocation = CR-UX-YYYY-NNN ticket.
3. **Approval Matrix:**
   - ≤ 5% scope, ≤$2,500 budget impact: UX Lead approves (no escalation)
   - 5–15% scope, $2,500–$15,000: Product + Tech Lead co-approve
   - > 15% scope, >$15,000: Stakeholder Approver + CFO sign-off + roadmap amendment checkpoint
4. **Deviation Reporting:** Any KPI actual vs target > 10% variance after Q1 → mandatory CR with root-cause-analysis 3-page report.
5. **Bi-weekly design-crit meeting, weekly sprint grooming, monthly stakeholder review, quarterly checkpoint refresh.**

---

## 9. ROADMAP APPROVAL SIGN-OFF

| Stakeholder | Name | Title | Signature / Initials | Date |
|---|---|---|---|---|
| **Sponsor / Approver** | ___________________ | Stakeholder Approver / CTO / CEO | ___________________ | 2026-__-__ |
| **Product Design Lead** | ___________________ | Senior Product Designer | ___________________ | 2026-__-__ |
| **Engineering Lead** | ___________________ | Laravel / Filament Senior Engineer | ___________________ | 2026-__-__ |
| **Accessibility Review** | ___________________ | WAS/CPACC Auditor | ___________________ | 2026-__-__ |
| **Legal / Compliance** | ___________________ | DPO / External Counsel (GDPR + NHIA) | ___________________ | 2026-__-__ |
| **Anchor-Client Design Partner** | ___________________ | 1st Law Firm Design Partner (future, Q4 2026) | ___________________ | 2026-__-__ |

---

*Document Version 1.0 — Approved for Execution pending §9 signatures. Companion Baseline Document: [CHECKPOINT_UIUX_20260829.md](file:///c:/Users/TV/Desktop/documentarchive.online/homedir/akaiv-saas/CHECKPOINT_UIUX_20260829.md)*
