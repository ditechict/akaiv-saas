# CODEX EXECUTION BRIEF — Bring Up AKAIV SaaS and Verify It

You are an autonomous coding agent operating **inside a GitHub Codespace**. Execute this brief top-to-bottom without asking for confirmation unless a **STOP CONDITION** is hit. Report results in the exact format at the end.

---

## 0. CONTEXT

| Item | Value |
|------|-------|
| Repository | `https://github.com/ditechict/akaiv-saas` |
| Branch | `main` |
| App directory | `akaiv-saas/` |
| Working directory | `/workspaces/akaiv-saas` (if repo root is `/workspaces/akaiv-saas`, adjust: app is the current dir) |
| Stack | Laravel 11, PHP 8.3, Filament 3, PostgreSQL 16, Redis 7 |
| Goal | Start the app on plain HTTP port 8000 and verify it is reachable in a browser |
| Admin credentials | `admin@myarchivesonline.com` / `password` |

> **Note on directory layout:** the app may live at the repo root or in a subfolder. Determine it in Step 1 and use the resolved path everywhere.

---

## 1. LOCATE THE APP ROOT

Run:
```bash
pwd
ls -la
if [ -f artisan ]; then APP_ROOT="$(pwd)"; else APP_ROOT="$(pwd)/akaiv-saas"; fi
echo "APP_ROOT=$APP_ROOT"
test -f "$APP_ROOT/artisan" && echo "artisan found OK" || echo "artisan NOT found"
```

- **Expected:** `artisan found OK`
- If not found, search: `find /workspaces -maxdepth 3 -name artisan 2>/dev/null` and set `APP_ROOT` to its parent.
- **STOP CONDITION:** If `artisan` cannot be found anywhere → stop and report.

All subsequent commands run from `APP_ROOT`:
```bash
cd "$APP_ROOT"
```

---

## 2. SYNC LATEST CODE

```bash
git fetch origin
git checkout main
git pull origin main
git log --oneline -3
```

- **Expected:** branch `main`, up to date, latest commit present.
- If `git pull` reports conflicts → **STOP CONDITION**, report the conflict files.

---

## 3. VERIFY DOCKER AVAILABILITY

```bash
docker version
```

**Decision:**
- If both `Client:` and `Server:` print → continue.
- If `permission denied ... /var/run/docker.sock` → run the fix and re-check:
  ```bash
  sudo chown root:docker /var/run/docker.sock
  sudo chmod 660 /var/run/docker.sock
  docker version
  ```
- If `docker: command not found` → the devcontainer is not active. **STOP CONDITION**: report that the Codespace must be rebuilt with the devcontainer (`.devcontainer/devcontainer.json`).

---

## 4. RUN THE BOOTSTRAP SCRIPT

```bash
ls -la scripts/run-browser-test.sh
bash scripts/run-browser-test.sh
```

The script performs, in order:
1. Docker socket permission fix
2. `composer install` inside a `composer:2.7` container (with `--ignore-platform-reqs`)
3. `.env` preparation for HTTP browser testing
4. `php artisan key:generate --force`
5. `docker compose up -d pgsql redis serve`
6. PostgreSQL readiness wait
7. `php artisan migrate --force` and `php artisan shield:install --fresh`
8. Creation of admin user `admin@myarchivesonline.com` + `Platform SuperAdmin` role

- **Expected final lines:**
  ```
  ======================================================
   AKAIV SaaS is running.
   ...
  ======================================================
  ```
- This can take **5–10 minutes** on first run. Do not interrupt.
- **On failure:** capture the exact failing step output. Apply Step 5 fixes if the error matches, otherwise **STOP CONDITION** and report.

---

## 5. ERROR BRANCHES (only if Step 4 failed)

| Error signature | Corrective action |
|-----------------|-------------------|
| `docker.sock: permission denied` | `sudo chown root:docker /var/run/docker.sock && sudo chmod 660 /var/run/docker.sock` then re-run Step 4 |
| `composer install` platform errors | The script already passes `--ignore-platform-reqs`; if it still fails, run `docker run --rm -v "$(pwd):/app" -w /app composer:2.7 composer install --no-interaction --prefer-dist --ignore-platform-reqs` and inspect output |
| `pg_isready` never succeeds | `docker compose logs pgsql --tail=100`; if the volume is corrupt, `docker compose down -v && docker compose up -d pgsql redis serve` then re-run migrations |
| `migrate` foreign-key error | `docker exec akaiv-serve php artisan migrate:fresh --force` (dev only), then re-run Shield + admin creation |
| `Class "..." not found` | `docker exec akaiv-serve composer dump-autoload` then retry the failing command |
| Port 8000 already in use | `docker compose down` then re-run Step 4 |

After any corrective action, re-run the failed portion of Step 4 before continuing.

---

## 6. VERIFY CONTAINERS

```bash
docker compose ps
```

- **Expected:** `akaiv-pgsql`, `akaiv-redis`, `akaiv-serve` all in state `Up` / `running`.
- **STOP CONDITION:** if any are `Exit`/`Restarting` → capture `docker compose logs <service> --tail=100` and report.

---

## 7. VERIFY HTTP RESPONSE

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/admin/login
```

- **Expected:** `200` (a `302` redirect to login is also acceptable).
- If `000` or `Connection refused`:
  ```bash
  docker logs akaiv-serve --tail=100
  ```
  then apply Step 5 branches.

Also verify the redirect from root:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/
```
- **Expected:** `302` (redirects to `/admin`).

---

## 8. VERIFY KEY ROUTES REGISTERED

```bash
docker exec akaiv-serve php artisan route:list | grep -E 'admin|documents|share' | head -40
```

- **Expected:** Filament `/admin/*` routes plus `documents.preview`, `documents.download`, `shares.show`, `shares.unlock`, `documents.analyze`.
- If routes are missing → `docker exec akaiv-serve php artisan route:clear && docker exec akaiv-serve php artisan optimize:clear`, then re-check.

---

## 9. VERIFY ADMIN USER EXISTS

```bash
docker exec akaiv-serve php artisan tinker --execute="
\$u = App\Models\User::where('email','admin@myarchivesonline.com')->first();
echo \$u ? 'USER_OK '.(\$u->hasRole('Platform SuperAdmin') ? 'SUPERADMIN' : 'NO_ROLE') : 'USER_MISSING';
"
```

- **Expected:** `USER_OK SUPERADMIN`
- If `USER_MISSING` or `NO_ROLE`, run:
  ```bash
  docker exec akaiv-serve php artisan make:filament-user --name="Admin" --email="admin@myarchivesonline.com" --password="password"
  docker exec akaiv-serve php artisan tinker --execute="
  \$u = App\Models\User::where('email','admin@myarchivesonline.com')->first();
  \$role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Platform SuperAdmin', 'guard_name' => 'web']);
  \$u->assignRole(\$role);
  echo 'ROLE_ASSIGNED';
  "
  ```

---

## 10. EXPOSE PORT 8000 PUBLICLY (for browser access)

Attempt via GitHub CLI if available:
```bash
if command -v gh >/dev/null 2>&1; then
  CODESPACE_NAME="${CODESPACE_NAME:-$(gh codespace list --json name -q '.[0].name' 2>/dev/null)}"
  gh codespace ports visibility 8000:public -c "$CODESPACE_NAME" 2>/dev/null && echo "PORT_PUBLIC_OK" || echo "PORT_VISIBILITY_MANUAL"
else
  echo "GH_CLI_MISSING"
fi
```

Print the forwarding URL:
```bash
if [ -n "${CODESPACE_NAME:-}" ]; then
  echo "BROWSER_URL=https://${CODESPACE_NAME}-8000.app.github.dev/admin/login"
fi
```

- If the CLI path fails, **do not stop** — report that port visibility must be set manually:
  **PORTS tab → right-click port 8000 → Port Visibility → Public**.

---

## 11. FINAL SMOKE TEST (authenticated page reachability)

```bash
curl -s -o /dev/null -w "login=%{http_code}\n" http://localhost:8000/admin/login
curl -s -o /dev/null -w "root=%{http_code}\n" http://localhost:8000/
docker exec akaiv-serve php artisan about | head -20
```

- **Expected:** `login=200`, `root=302`, and `about` prints environment details (Laravel version, cache/queue/session drivers).

---

## 12. REPORT (required format)

Output exactly this block, filled in:

```
=== AKAIV SaaS CODESPACE BRING-UP REPORT ===
Repo revision:        <git rev-parse --short HEAD>
App root:             <$APP_ROOT>
Docker:               OK | FAILED (<reason>)
Composer install:     OK | SKIPPED (vendor present) | FAILED (<reason>)
.env configured:      OK | FAILED
APP_KEY generated:    OK | FAILED
Containers:           pgsql=<state> redis=<state> serve=<state>
Migrations:           OK | FAILED (<reason>)
Shield install:       OK | FAILED (<reason>)
Admin user:           USER_OK SUPERADMIN | <problem>
Route check:          OK | FAILED (<missing routes>)
HTTP /admin/login:    <status code>
HTTP /:               <status code>
Port 8000 visibility: PUBLIC | MANUAL_REQUIRED
Browser URL:          <https://...-8000.app.github.dev/admin/login>
Login:                admin@myarchivesonline.com / password
Blockers:             <none | description>
Next action:          <what a human must do next, if anything>
=== END REPORT ===
```

---

## 13. HARD RULES

1. Run every command from `$APP_ROOT`.
2. Never expose or print secret values from `.env`.
3. Do not run `migrate:fresh` or `down -v` unless a migration error requires it and no real data exists (dev only).
4. Do not modify application code to make the app boot — report the defect instead.
5. Stop and report on any **STOP CONDITION** rather than improvising destructive commands.
6. Keep all output from failing commands for the report.
