INCIDENT RESPONSE FORENSIC EVIDENCE LOG
Date: 2026-08-26
Executor: Automated remediation run
Project: myarchivesonline.com (AKAIV / Laravel 6 legacy app)

==============================================================
1. MALWARE / BACKDOORS IDENTIFIED AND REMEDIATED
==============================================================

BACKDOOR-01: Web Shell "upl.php" (Variant 1 - Main)
Location: public/upl.php
File Size: 1365 bytes (original)
Content Signature:
  - Static access key: 7ce08fe35639adedac1007ef9c7f4503 (passed via ?k=)
  - Commands: test, mkdir (any path via $_POST['dir'], chmod 0755),
              upload (base64_decode($_POST['data']) -> file_put_contents to $_POST['file'])
  - Known CKH: This is a widely distributed commodity PHP uploader / weevely-style shell
Remediation Action: PERMANENTLY DELETED (sha256 captured prior - see *_sha256.txt)

BACKDOOR-02: Web Shell "upl.php" (Variant 2 - Duplicate)
Location: public/public/upl.php
Note: public/public/ directory is NOT part of default Laravel 6 structure.
      Directory and shell both appear to have been created via BACKDOOR-01 mkdir + upload
      commands post-exploitation.
Remediation Action: PERMANENTLY DELETED + parent directory removed

BACKDOOR-03: Web Shell "upl.php" (Variant 3 - Duplicate)
Location: public/public_html/upl.php
Note: public/public_html/ also attacker-created (same technique as BACKDOOR-02)
Remediation Action: PERMANENTLY DELETED + parent directory removed

EXPOSURE-04: CKEditor sample handler (not backdoored, but dangerous)
Location: public/assets/plugins/ckeditor/samples/old/sample_posteddata.php
          public/assets/plugins/ckeditor/samples/old/assets/posteddata.php
Risk: Allows arbitrary POST of form content to be echoed (sample code)
Remediation Action: PERMANENTLY DELETED (along with full samples/old/ tree unused in production)

==============================================================
2. CONFIGURATION HARDENING APPLIED
==============================================================

CONFIG-01: .env file
  BEFORE:
    APP_ENV=local
    APP_DEBUG=true
    APP_URL=http://localhost
    APP_KEY=base64:wIng5abu8wU0xOgqTPSxEjfWW1ch0K6QD6KzgftiP4Q=  (COMPROMISED)
  AFTER:
    APP_ENV=production
    APP_DEBUG=false           <-- CRITICAL: Disables Ignition RCE endpoint CVE-2021-3129
    APP_URL=https://myarchivesonline.com
    APP_KEY=base64:RkREdTduSHRzZVF3d2pKNkNXeEpYZjJqNGgxN2lhbjg=  (ROTATED)
  Effect: All sessions signed with old APP_KEY invalidated (see SESSIONS-01)
  PENDING ACTION (manual): MySQL user earlvzhc_archive password must be rotated
    at hosting provider (cPanel / MySQL CLI) because old password tMfKFvPwLT7d
    was present in leaked .env file. Update .env DB_PASSWORD after rotation.

CONFIG-02: Document Directory Hardening
  Location: public/documents/.htaccess
  Rule added: Require all denied
  Effect: Even though files physically exist under webroot, Apache will now
    return HTTP 403 for direct access. Only PHP/Laravel controller with
    authentication check (DocumentController@download) will still serve files.
  PHASE-2 PLANNED: Full migration of documents to storage/app/documents (outside webroot)
    then public/documents can be removed entirely.

==============================================================
3. SESSION / AUTH INVALIDATION
==============================================================

SESSIONS-01: Session store invalidation
  APP_KEY rotation above invalidates all Laravel session cookies immediately.
  Additionally, all files in storage/framework/sessions/ should be manually
  purged (except .gitignore) to force 100% re-login.

SESSIONS-02: Pending manual audit
  Run in MySQL after deployment:
    SELECT id, email, role, created_at, active FROM users ORDER BY created_at DESC;
  Look for accounts created on/after 2023-02-21 (date of CVE-2021-3129 exploit in logs).
  Audit any user with role='Admin' that was not approved.
  Force password reset for all active users via DB UPDATE set password = '!!!'
    so next login attempt fails until they do Forgot Password flow.

==============================================================
4. HISTORICAL IOC (Indicator of Compromise) FROM LOGS
==============================================================

Confirmed in laravel-2023-02-21.log (lines 1-100):
  18:44:45 local.ERROR: file_put_contents(/bin/sh): failed to open stream: Permission denied
    Stack trace clearly shows:
      ExecuteSolutionController -> MakeViewVariableOptionalSolution::run() ->
      file_put_contents('/bin/sh', ELF binary bytes)
    This is the CVE-2021-3129 RCE "write /bin/sh to get shell" payload used in the wild.
  18:44:52 local.ERROR: file_get_contents(DOESNOTEXIST): failed to open stream
  18:44:55 local.ERROR: file_get_contents(.../storage/logs/laravel.log): no such file
    Next step in CVE-2021-3129 chain: convert laravel.log to PHAR deserialization
    gadget chain for second-stage RCE.

Result of attack: PERMISSION DENIED on /bin/sh (server user not root = saved by least-privilege)
However, upl.php backdoors and public/public + public/public_html directories ARE PRESENT,
so attacker DID gain filesystem write capability at some later point (or through a
different path). Assume server was partially compromised.

==============================================================
5. REMAINING MANUAL ACTIONS FOR SYSADMIN / DEVOPS
==============================================================

[ ] RESET MYSQL PASSWORD for user earlvzhc_archive (old password leaked)
[ ] AUDIT users table rows created >= 2023-02-21, disable unknown admins
[ ] FORCE PASSWORD RESET for all existing users (invalidate password hashes column)
[ ] ENABLE Cloudflare / WAF rule to block:
      - POST URI path containing "_ignition/"
      - URI path = "/upl.php" (even though files deleted, block at edge)
      - Any User-Agent like sqlmap, masscan, nuclei, acunetix
[ ] REVIEW server access logs (access.log) around 2023-02-21 18:40-23:59 UTC,
      identify attacker IP(s), add permanent block.
[ ] PURGE storage/framework/sessions/* files (except .gitignore)
[ ] ENABLE 2FA for all admin users on the NEW SaaS platform (not legacy)
[ ] MOVE legacy documents to storage/app/documents (non-webroot) + update controller
[ ] DESTROY old server instance at hosting provider, redeploy fresh (rebuild plan w/ Laravel 11)
