# 005 · Auth Dummy Hash Configuration — Tasks

Reference: `spec.md` · `plan.md`

Feature status: Completed

> Converted from the approved plan into small, ordered, verifiable steps.
> **One task format:** `TASK-NNN`. Implement one task at a time (prompt 06).
> At the end of each task, pass the Definition of Done (lint/test/build).
> Commit only when the human explicitly asks; if asked, use `TASK-NNN: <what changed>`.

---

## Implementation objective

Move the unknown-email dummy password hash from `AuthService` into authentication configuration with
a safe bcrypt default, optional environment override, fail-fast validation, direct tests, and clear
documentation. Login behavior, token issuance, rate limits, and API responses remain unchanged.

No endpoint, migration, package, UI, or business-rule change is introduced.

---

## Phase order

1. Configuration → 2. Service → 3. Documentation → 4. Verification and records →
   5. Final gate and closure

Never start a task before its dependencies are complete.

---

## Tasks

### TASK-001 · Add the dummy-hash configuration and validation

- **Status:** [x] Completed
- **Description:** Add `auth.dummy_password_hash` with a valid bcrypt cost-12 default and optional
  `AUTH_DUMMY_PASSWORD_HASH` override. Validate that the configured value is a recognized password
  hash before configuration is returned; fail fast with an error that names the setting and never
  includes the hash value. Add comments explaining the non-secret purpose and hasher/cost alignment.
- **Files / location:** `config/auth.php`.
- **Dependencies:** None.
- **Validation:** The default is non-empty and recognized as a valid hash; an invalid override makes
  `php artisan config:cache`/configuration loading fail with a clear configuration error; the error
  does not contain the supplied value; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `auth.dummy_password_hash` with a bcrypt cost-12 default,
  optional `AUTH_DUMMY_PASSWORD_HASH` override, and fail-fast validation. The default reports
  `bcrypt`; an invalid override makes `config:cache` fail with a clear error that does not include
  the value. Full suite passed (75 tests, 706 assertions); Pint and optimize passed.

### TASK-002 · Refactor AuthService to use configuration

- **Status:** [x] Completed
- **Description:** Remove `DUMMY_PASSWORD_HASH` from `AuthService`, read
  `config('auth.dummy_password_hash')`, and add a comment explaining why unknown emails perform one
  password hash check. Add service tests for the default hash, configured-value wiring, and the
  unchanged unknown-email failure.
- **Files / location:** `app/Services/AuthService.php`,
  `tests/Feature/Services/AuthServiceTest.php`.
- **Dependencies:** TASK-001.
- **Validation:** An unknown email still returns `null` and issues no token; a test proves
  `Hash::check` receives the configured hash; valid credential login remains unchanged; Pint,
  tests, and optimize pass.
- **Completion:** 2026-10-26 — removed the service constant and read the configured hash. The new
  tests exposed and fixed a short-circuit bug where `! $user || ! Hash::check(...)` skipped the
  dummy check for unknown emails entirely. Added default-validity, unknown-email, and config-wiring
  tests. Full suite passed (78 tests, 713 assertions); Pint and optimize passed.

### TASK-003 · Document the configuration

- **Status:** [x] Completed
- **Description:** Document the non-secret dummy hash, default, optional override, single-quote
  guidance for bcrypt `$` characters, generation command, hasher/cost alignment, configuration-cache
  requirement, and partial-timing-mitigation limit.
- **Files / location:** `.env.example`, `README.md`, `docs/constitution/tech-stack.md`.
- **Dependencies:** TASK-001, TASK-002.
- **Validation:** `.env.example` documents the optional variable without exposing a secret; README
  and tech-stack explain generation/alignment/limits; documentation matches the actual default and
  behavior.
- **Completion:** 2026-10-26 — documented the optional environment override, bcrypt generation,
  single-quote handling, config-cache rebuild, fail-fast behavior, and partial-mitigation limit in
  `.env.example`, README, and tech-stack.

### TASK-004 · Verify override and cache behavior, update records

- **Status:** [x] Completed
- **Description:** Verify a valid override with configuration caching, verify that an invalid
  override fails configuration loading, clear the cache afterward, and update the `001` remediation
  record, project context, learnings, and known issues as needed.
- **Files / location:** `docs/features/001-auth-minimum/tasks.md`, `docs/project/context.md`,
  `docs/memory/learnings.md`, `docs/memory/known-issues.md`.
- **Dependencies:** TASK-001 through TASK-003.
- **Validation:** Valid cache build preserves behavior; invalid override fails clearly; cache is
  cleared for development; records mention configuration rather than the old service constant.
- **Completion:** 2026-10-26 — a valid override survived `config:cache` and matched the configured
  value; an invalid override failed `config:cache` with the safe setting-named error. Cache was
  cleared afterward. Updated the `001` remediation record, context, and learnings.

### TASK-005 · Run the final gate and close the feature

- **Status:** [x] Completed
- **Description:** Run manual login checks, the full quality gate, and close the feature:
  implemented spec, completed plan/tasks, roadmap, context, learnings, and known issues.
- **Files / location:** `docs/features/005-auth-dummy-hash-config/spec.md`,
  `docs/features/005-auth-dummy-hash-config/plan.md`,
  `docs/features/005-auth-dummy-hash-config/tasks.md`, `docs/constitution/roadmap.md`,
  `docs/project/context.md`, `docs/memory/learnings.md`, `docs/memory/known-issues.md`.
- **Dependencies:** TASK-001 through TASK-004.
- **Validation:** Valid demo login succeeds; unknown email returns generic 401 without a token;
  `composer validate --strict`, `./vendor/bin/pint --test`, `php artisan test`,
  `php artisan optimize`, and `php artisan optimize:clear` pass; feature records are consistent.
- **Completion:** 2026-10-26 — verified valid login and generic unknown-email login with
  configuration cached, plus profile/logout/token reuse. Closed spec, plan, tasks, roadmap,
  context, and known issues. Full suite passed (78 tests, 713 assertions); composer validation,
  audit, Pint, optimize, and optimize:clear passed.

---

## Feature Definition of Done

Before marking the feature complete:

- [x] All TASK items are Completed.
- [x] The acceptance criteria in `spec.md` are satisfied.
- [x] `architecture.md` and `conventions.md` are respected.
- [x] The dummy hash is configuration, not a service constant.
- [x] Login success, errors, tokens, and rate limits are unchanged.
- [x] Lint, tests, and build pass using the commands in `tech-stack.md`.
- [x] No new endpoint, migration, package, UI, Docker, or CI/CD work was added.
- [x] No unnecessary debt remains; known issues are recorded in
  `docs/memory/known-issues.md`.

---

## Closing the feature

1. `spec.md` → status `implemented`.
2. `roadmap.md` → move the feature to *Completed features*.
3. `docs/memory/learnings.md` → record what worked, what did not, and what to improve.
4. `docs/memory/decisions.md` → add an ADR if an impactful decision was made.
5. Update `docs/project/context.md` with the new state.

---

## Post-completion review remediation (2026-10-26)

**Status:** Completed

**Reference:** code review finding on hasher/cost alignment validation

**Files:** `config/auth.php`, `tests/Feature/Services/AuthServiceTest.php`,
`tests/Feature/Configuration/AuthDummyHashConfigTest.php`, `phpunit.xml`.

Changes:

* Validation now requires the dummy hash algorithm, cost, and options to match the configured
  hasher; bcrypt cost 4 and an argon2id hash are rejected under a bcrypt cost-12 setup.
* Added automated config-cache tests for malformed, wrong-cost, and wrong-algorithm overrides.
* Strengthened the default-hash test to require bcrypt and the configured cost.
* Added a test-only cost-4 dummy hash to `phpunit.xml`, which uses `BCRYPT_ROUNDS=4`.

**Validation:** `php artisan test` passed (79 tests, 723 assertions); Pint, optimize, and
optimize:clear passed. Config-cache tests confirm each invalid case fails with a safe error.

---

## Agent rules

1. Implement one task at a time. Mark real progress, not optimistic progress.
2. Report modified files and executed validations with actual results.
3. Do not skip tasks or expand the scope silently.
4. If the plan proves wrong, stop, explain, and propose an update. Do not patch around it.
5. Commit or push only when the human explicitly asks; use `TASK-NNN: <what changed>` when asked.
