# Akaiv audit remediation execution plan

Baseline PR #2 at 84bb953363a6840a3de99144939a38738b8fea96. Work proceeds on its existing branch.

1. Enforce authenticated membership on tenant model creates, updates and document policies. Test foreign organization assignments, membership revocation, policy checks and signed HTTP access. Preserve trusted console jobs and explicit platform administrator behavior.
2. Replace processing delays with a completion-ordered job chain. Restrict search eligibility to published, clean, scanned documents with completed required OCR. Replace placeholder index assertions and exercise the service integration tests.
3. Repair active-app legacy import compatibility, dry-run write behavior, folder lookup and retry duplication. Validate the existing import tests and targeted regression tests against isolated PostgreSQL.
4. Carry forward CI enforcement from PR #1, keep runtime tests independently executable, run changed-file style checks and the complete backend suite. Preserve logs and JUnit evidence. Review final diff and update PR description.
5. Record release dependencies. Live shared-secret and application-key rotation require provider access and an encrypted-data compatibility assessment. Cloud storage, durable Redis worker execution, live search indexing and deployment verification require dedicated staging checks. Keep these outcomes explicitly pending until exercised.

Acceptance criteria

- Tenant regression tests and existing signed-access behavior pass.
- Processing order is asserted and unapproved records are excluded from indexing.
- Import dry-run does not create users, folders, documents or stored files. Repeated imports do not create duplicate documents.
- Backend tests complete with failures visible to CI.
- Evidence identifies exact tested commit and remaining external dependencies.

Rollback

Revert the remediation commits through a reviewed PR. No schema migration is planned. Deployment and credential changes require their own rollback assessment.

## Implementation status

- Step 1 implemented with model save/delete membership enforcement, validated document policy checks, stale-session clearing and signed preview/download regressions.
- Step 2 implemented with an ordered processing chain, search eligibility gates, observable engine-removal assertions, all-page PDF extraction and native text extraction.
- Step 3 implemented with schema-compatible user creation, write-free dry runs, tenant-specific folder lookup, per-organization transactional retry protection, rollback cleanup and preserved timestamps.
- Step 4 implemented with enforced CI failures, independent backend verification, generated two-page PDF fixture and a Pint gate covering changed PHP files. Runtime logs and final validation are recorded in PR #2.
- Step 5 remains pending provider access and dedicated staging integration. Credential values are redacted on this branch, but live rotation and Git-history remediation are pending.

Outstanding release checks

- The existing Worker suite fails during collection because the installed Workers test runtime cannot load cloudflare-internal:email.
- Repository-wide Pint reports inherited style debt outside the remediation scope. The changed-file gate is independent.
- Real Meilisearch indexing, durable Redis worker execution, malware-positive detection and cloud storage remain outside the completed runtime coverage.
- Trusted console jobs and explicit platform administrator access retain their existing behavior. Scoped authenticated model writes are covered. Bulk SQL writes require caller-level authorization.
