# 004 · API Contract Hardening — Tasks

Reference: `spec.md` · `plan.md`

Feature status: Completed

> Converted from the approved plan into small, ordered, verifiable steps.
> **One task format:** `TASK-NNN`. Implement one task at a time (prompt 06).
> At the end of each task, pass the Definition of Done (lint/test/build).
> Commit only when the human explicitly asks; if asked, use `TASK-NNN: <what changed>`.

---

## Implementation objective

All existing API operations expose one JSON-only contract with a shared error envelope, a general
60/minute rate limit, preserved login throttling, standard limiter headers, verified token
expiration, and safe 500 responses. Successful response payloads and all business rules remain
unchanged. No endpoints, migrations, packages, or UI are added.

---

## Phase order

1. Error contract → 2. Safe failure → 3. Limiter definitions → 4. Route enforcement →
   5. Regression coverage → 6. Documentation and final gate

Never start a task before its dependencies are complete.

---

## Tasks

### TASK-001 · Centralize known API error responses

- **Status:** [x] Completed
- **Description:** Add one exception-render callback for `/api/*` that maps validation failures to
  `422` with a generic `message` plus `errors`, authentication failures to `401`, framework
  not-found to `404`, and unsupported methods to `405`. Preserve the existing book controller 404
  messages and all success responses. Do not add the 500 fallback yet.
- **Files / location:** `bootstrap/app.php`, `tests/Feature/ApiContractTest.php`.
- **Dependencies:** None.
- **Validation:** Tests assert status, `Content-Type: application/json`, non-empty `message`,
  `errors` only for `422`, no `data` field, and no internal exception text for `401/404/405/422`.
  Existing tests remain green; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added the centralized API exception renderer for validation,
  authentication, framework 404, and 405 responses. Added four cross-cutting contract tests using
  requests without an `Accept` header. Full suite passed (70 tests, 373 assertions); Pint and
  optimize passed.
- **Note:** The safe 500 fallback and rate limiting remain pending.

### TASK-002 · Add the safe 500 fallback

- **Status:** [x] Completed
- **Description:** Extend the API exception renderer with a fallback for unexpected failures:
  report the exception server-side and return `500` with the generic `Server error.` envelope. Do
  not expose the exception message, stack trace, SQL, paths, or request body.
- **Files / location:** `bootstrap/app.php`, `tests/Feature/ApiContractTest.php`.
- **Dependencies:** TASK-001.
- **Validation:** A feature test renders a `RuntimeException` containing a sentinel secret through
  the API exception handler, asserts `500`, asserts the generic message and JSON content type, and
  asserts the sentinel is absent. The HTTP kernel still reports exceptions before rendering.
  Pint, tests, and optimize pass.
- **Completion:** 2026-10-26 — added the safe 500 fallback for non-HTTP unexpected exceptions;
  HTTP exceptions keep their existing handling. Added the renderer-level test with a sentinel
  `RuntimeException`. Full suite passed (71 tests, 376 assertions); Pint and optimize passed.
- **Note:** `BookService` is `final`, so the test exercises the exception handler directly instead
  of mocking the service. The HTTP kernel still reports exceptions before rendering.

### TASK-003 · Define API and login rate-limit responses

- **Status:** [x] Completed
- **Description:** Add the general `api` limiter at 60 requests/minute, keyed by authenticated user
  ID when available and public IP otherwise. Keep the login limiter at 5 attempts/minute by
  normalized email + IP. Give both limiters a shared JSON `429` response that preserves
  `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After`.
- **Files / location:** `app/Providers/AppServiceProvider.php`, `tests/Feature/AuthTest.php`,
  `tests/Feature/ApiContractTest.php`.
- **Dependencies:** TASK-001.
- **Validation:** Six login attempts return a `429` JSON envelope with the expected headers, no
  token is issued, and the existing 401/429 behavior remains intact. Pint, tests, and optimize pass.
- **Completion:** 2026-10-26 — added the `api` limiter definition at 60/minute keyed by user or IP,
  kept the login limiter at 5/minute by email + IP, and added the shared JSON 429 response with
  rate-limit headers. Extended the login throttle test to assert envelope, headers, and no token.
  Full suite passed (71 tests, 385 assertions); Pint and optimize passed.
- **Note:** The `api` limiter is defined but not attached to routes until TASK-004.

### TASK-004 · Apply the general API limit to routes

- **Status:** [x] Completed
- **Description:** Apply `throttle:api` to the public health route and to the protected API group,
  ensuring `auth:sanctum` runs before `throttle:api` so authenticated requests are limited by user
  ID. Keep login on the login limiter only; do not double-limit it. Throttled requests must perform
  no business mutation.
- **Files / location:** `routes/api.php`, `tests/Feature/ApiContractTest.php`.
- **Dependencies:** TASK-003.
- **Validation:** The 61st public request from one IP returns 429; the 61st authenticated request
  for one user returns 429; a second user remains unaffected; rate-limit headers are present; no
  mutation occurs on rejected requests. Limiter keys are cleared or isolated between tests. Pint,
  tests, and optimize pass.
- **Completion:** 2026-10-26 — applied `throttle:api` to health and the protected group, with
  `auth:sanctum` running first. Login remains on its specific limiter. Added public-IP and
  per-user limit tests, including no mutation on a rejected create and another user unaffected.
  Full suite passed (73 tests, 526 assertions); Pint and optimize passed.

### TASK-005 · Cover contract regressions and compatibility

- **Status:** [x] Completed
- **Description:** Add the remaining cross-cutting contract tests: JSON errors without an
  `Accept` header, unknown API route 404, 405, expired-token 401 envelope, safe 500, no sensitive
  fields, and unchanged 200/201 success and pagination shapes. Reuse the envelope assertions from
  earlier tasks without duplicating business tests.
- **Files / location:** `tests/Feature/ApiContractTest.php`, `tests/Feature/AuthTest.php`.
- **Dependencies:** TASK-001 through TASK-004.
- **Validation:** The full contract matrix passes; success payloads remain compatible; no test
  relies on internal exception text; Pint, tests, and optimize pass.
- **Completion:** 2026-10-26 — added expired-token envelope assertions, explicit 429 envelope
  headers without `Accept`, and a success/pagination compatibility test. Existing 404/405/500/
  no-Accept coverage remains green. Full suite passed (74 tests, 568 assertions); Pint and
  optimize passed.

### TASK-006 · Document the contract and run the final gate

- **Status:** [x] Completed
- **Description:** Update the README with the error envelope, status codes, login/public/user rate
  limits, headers, retry behavior, and the unchanged success shapes. Update context and known
  issues if needed. Run a manual contract flow and the full quality gate.
- **Files / location:** `README.md`, `docs/project/context.md`, `docs/memory/known-issues.md`.
- **Dependencies:** TASK-001 through TASK-005.
- **Validation:** Manual checks for validation, 401, 404, 405, 429 with headers, safe 500, and a
  successful create/list/detail flow; `composer validate --strict`, `./vendor/bin/pint --test`,
  `php artisan test`, `php artisan optimize`, and `php artisan optimize:clear` pass.
- **Completion:** 2026-10-26 — documented the error envelope, status codes, rate limits, headers,
  retry behavior, and unchanged success shapes. The manual flow passed for `200/201/401/404/405/422/429`
  and cleanup; safe `500` is covered by the API contract test. Full suite passed (74 tests, 568
  assertions); composer validation, audit, Pint, optimize, and optimize:clear passed.

---

## Feature Definition of Done

Before marking the feature complete:

- [x] All TASK items are Completed.
- [x] The acceptance criteria in `spec.md` are satisfied.
- [x] `architecture.md` and `conventions.md` are respected.
- [x] The error envelope, rate-limit keys, and safe 500 behavior live in the shared HTTP boundary.
- [x] Successful response payloads and business rules are unchanged.
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

**Reference:** code review finding on public rate-limit coverage

**Files:** `app/Http/Middleware/ThrottleApiRequests.php`, `bootstrap/app.php`,
`app/Providers/AppServiceProvider.php`, `routes/api.php`, `tests/Feature/ApiContractTest.php`.

Changes:

* Moved the general API limiter from route-level middleware to a global API middleware so
  unauthenticated protected requests (`401`) and unknown routes (`404`) are also counted.
* The `api` limiter now resolves the Sanctum user before route authentication, preserving per-user
  keys for authenticated traffic while using IP for public traffic.
* Kept login excluded from the general limiter and under its own 5/minute limiter.
* Added tests for 60/minute public coverage of unauthenticated `/me` and unknown routes.
* Corrected test limiter cleanup for Laravel's hashed limiter keys.

**Validation:** `php artisan test` passed (75 tests, 706 assertions); `ApiContractTest` passed
(9 tests, 339 assertions); Pint, optimize, and optimize:clear passed. Live probes: `59 × 401`
plus the health check and `6 × 429`; `60 × 404` and `5 × 429` for unknown routes.

---

## Agent rules

1. Implement one task at a time. Mark real progress, not optimistic progress.
2. Report modified files and executed validations with actual results.
3. Do not skip tasks or expand the scope silently.
4. If the plan proves wrong, stop, explain, and propose an update. Do not patch around it.
5. Commit or push only when the human explicitly asks; use `TASK-NNN: <what changed>` when asked.
