# 001 · Auth Minimum — Tasks

Reference: `spec.md` · `plan.md`

Feature status: Completed

> Converted from the approved plan into small, ordered, verifiable steps.
> **One task format:** `TASK-NNN`. Implement one task at a time (prompt 06).
> At the end of each task, pass the Definition of Done (lint/test/build).
> Commit only when the human explicitly asks; if asked, use `TASK-NNN: <what changed>`.

---

## Implementation objective

A documented demo user can log in, access an authenticated profile, and log out. Tokens expire
after 7 days, login is throttled, invalid credentials do not reveal whether an email exists, and
the full flow is covered by tests. No public registration, roles, book data, UI, Docker, or CI/CD
is added.

---

## Phase order

1. Sanctum and token storage → 2. Demo user → 3. Login → 4. Profile → 5. Logout →
   6. Security edge tests → 7. Documentation and final verification

Never start a task before its dependencies are complete.

---

## Tasks

### TASK-001 · Install Sanctum and configure token storage

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Require `laravel/sanctum`, publish its configuration and migration, add
  `HasApiTokens` to the `User` model, configure 7-day token expiration through
  `SANCTUM_TOKEN_EXPIRATION=10080`, and run the migration. Do not use `install:api`; do not add
  login or logout routes yet.
- **Files / location:** `composer.json`, `composer.lock`, `config/sanctum.php`,
  `database/migrations/*_create_personal_access_tokens_table.php`,
  `app/Models/User.php`, `.env.example`, `.env` (local only), `database/database.sqlite` (local).
- **Dependencies:** None; `000-setup-base` is complete.
- **Validation:** `composer validate --strict` passes; `php artisan migrate` creates the token
  table; `php artisan migrate:status` shows it as applied; `php artisan test` keeps the health
  test green; `./vendor/bin/pint --test`, `php artisan optimize`, and `php artisan optimize:clear`
  pass. The health route and existing behavior are unchanged.

### TASK-002 · Seed the documented demo user

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add an idempotent `DemoUserSeeder` that creates or refreshes the documented
  demo account, invoke it from `DatabaseSeeder`, update the `composer setup` script to seed after
  migrating, and run the seeder locally.
- **Files / location:** `database/seeders/DemoUserSeeder.php`,
  `database/seeders/DatabaseSeeder.php`, `composer.json`.
- **Dependencies:** TASK-001.
- **Validation:** `php artisan db:seed` succeeds; the demo email exists; `User::count()` is `1`;
  running `php artisan db:seed` again still leaves exactly one demo user; no password is printed
  or logged; `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass.
- **Note:** The demo credentials are test-only data. The README documents them in TASK-007.
- **Completion:** 2026-10-26 — added `DemoUserSeeder` with `updateOrCreate`, wired it into
  `DatabaseSeeder`, and changed `composer setup` to `migrate --seed --force`. Seeded the demo
  user, verified the password hash, confirmed re-seeding keeps exactly one user, and passed the
  DoD.

### TASK-003 · Add login with validation, token issuance, and throttling

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `LoginRequest`, `UserResource`, `AuthController@login`, the named `login`
  rate limiter (5 attempts/minute by lowercased email + IP), and the public
  `POST /api/v1/login` route with `throttle:login`. Use `Hash::check` and return the same generic
  401 for wrong password and unknown email. On success, issue a Sanctum token and return it with
  the public user identity. Add tests for success, invalid credentials, validation, and secret
  safety.
- **Files / location:** `routes/api.php`, `app/Http/Controllers/Api/V1/AuthController.php`,
  `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Resources/UserResource.php`,
  `app/Providers/AppServiceProvider.php`, `tests/Feature/AuthTest.php`.
- **Dependencies:** TASK-001, TASK-002.
- **Validation:** Login with the seeded credentials returns `200` with `token`, `token_type`, and
  the public user; wrong password and unknown email return the same `401`; missing or malformed
  input returns `422`; the response and logs contain no password; the returned token is a non-empty
  bearer credential; `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize`
  pass.
- **Completion:** 2026-10-26 — added login request/resource/controller/route and the named 5/minute
  limiter. Four auth tests passed (25 assertions); full suite passed (5 tests, 29 assertions).
  The sixth manual attempt returned 429. DoD passed.

### TASK-004 · Add the authenticated profile check

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `GET /api/v1/me` protected by `auth:sanctum` and an `AuthController@me`
  action returning only the public user identity. Add tests for a valid token, a missing token,
  and a malformed token.
- **Files / location:** `routes/api.php`, `app/Http/Controllers/Api/V1/AuthController.php`,
  `tests/Feature/AuthTest.php`.
- **Dependencies:** TASK-003.
- **Validation:** A token from login returns `200` with `id`, `name`, and `email`; a request
  without a token returns `401`; a malformed token returns `401`; no password or token secret is
  returned; `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `GET /api/v1/me` with `auth:sanctum` and a public profile
  response. Two profile tests passed; full suite passed (7 tests, 41 assertions). DoD passed.

### TASK-005 · Add logout with current-token revocation

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `POST /api/v1/logout` protected by `auth:sanctum`. Revoke only the current
  access token and return a JSON success message. Add tests for logout, reuse of the revoked
  token, two active tokens, and logout without authentication.
- **Files / location:** `routes/api.php`, `app/Http/Controllers/Api/V1/AuthController.php`,
  `tests/Feature/AuthTest.php`.
- **Dependencies:** TASK-004.
- **Validation:** Logout returns `200`; the same token gets `401` on the profile; a second active
  token still works; logout without a token returns `401`; no token or password is logged;
  `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `POST /api/v1/logout` with current-token revocation and three
  tests (logout, two-token isolation, unauthenticated logout). Full suite passed (10 tests, 53
  assertions). DoD passed.

### TASK-006 · Cover expiration, throttling, and security edges

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add the remaining security regression tests: token expiration after 7 days,
  the sixth login attempt returning 429, absence of a public registration route, and absence of
  password material in responses. If a test reveals a defect, fix it within this task without
  expanding scope.
- **Files / location:** `tests/Feature/AuthTest.php`; `config/sanctum.php` only if the expiration
  unit is wrong.
- **Dependencies:** TASK-003, TASK-004, TASK-005.
- **Validation:** The expired-token test uses time travel and returns `401`; the sixth attempt in
  one minute returns `429`; an account-creation request returns `404`; secrets are absent;
  `php artisan test`, `./vendor/bin/pint --test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added expiration (time travel), sixth-attempt throttling, missing
  registration, and auth-response secret tests. Full suite passed (14 tests, 68 assertions).
  DoD passed.

### TASK-007 · Document authentication and run the final quality gate

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Update the README with the demo credentials, login/profile/logout examples,
  seeding instructions, token lifetime, throttling, and auth troubleshooting. Remove the “no
  authentication” statements. Run the full quality gate and the manual curl flow, then clear
  build caches.
- **Files / location:** `README.md`, `.env.example`.
- **Dependencies:** TASK-001 through TASK-006.
- **Validation:** A fresh setup seeds the demo user; login with the documented credentials works;
  the profile and logout flow works; `401`, `422`, and `429` behavior is documented; the health
  check still returns `200`; `./vendor/bin/pint --test`, `php artisan test`, `php artisan optimize`,
  and `php artisan optimize:clear` pass.
- **Completion:** 2026-10-26 — README updated with demo credentials and auth examples; live HTTP
  flow login → profile → logout → 401 verified; full DoD passed.

---

## Feature Definition of Done

Before marking the feature complete:

- [x] All TASK items are Completed.
- [x] The acceptance criteria in `spec.md` are satisfied.
- [x] `architecture.md` and `conventions.md` are respected.
- [x] Lint, tests, and build pass using the commands in `tech-stack.md`.
- [x] Demo credentials are documented and test-only.
- [x] No public registration, roles, book data, UI, Docker, or CI/CD work was added.
- [x] No unnecessary debt remains; any known issue is recorded in
  `docs/memory/known-issues.md`.

---

## Closing the feature

1. `spec.md` → status `implemented`.
2. `roadmap.md` → move the feature to *Completed features*.
3. `docs/memory/learnings.md` → record what worked, what did not, and what to improve.
4. `docs/memory/decisions.md` → add an ADR if an impactful decision was made.
5. Update `docs/project/context.md` with the new state.

---

## Post-completion retrofit (maintenance, outside the original seven tasks)

**Status:** Completed (2026-10-26)

**Reference:** `AUTH-001` in `docs/memory/known-issues.md`

**Files:** `app/Services/AuthService.php`, `app/Http/Controllers/Api/V1/AuthController.php`,
`tests/Feature/Services/AuthServiceTest.php`; existing `tests/Feature/AuthTest.php` remains.

ADR-008 adopted one service per business controller after this feature closed. `AuthController`
was refactored to delegate login, profile, and logout to `AuthService` without changing the API.
`tests/Feature/Services/AuthServiceTest.php` covers the service; the existing HTTP tests remain
green. AUTH-001 is resolved.

---

## Post-completion review remediation (2026-10-26)

**Status:** Completed

**Reference:** code review findings for `001-auth-minimum`

**Files:** `app/Http/Middleware/ForceJsonResponse.php`, `bootstrap/app.php`,
`app/Services/AuthService.php`, `app/Models/User.php`, `database/seeders/DemoUserSeeder.php`,
`tests/Feature/AuthTest.php`, `README.md`.

Changes:

* Forced JSON responses for all `/api/*` requests, including authentication, validation, and
  throttling errors when the client omits `Accept: application/json`.
* Restricted `DemoUserSeeder` to `local` and `testing`; it is skipped in other environments.
* Added a dummy password hash check for unknown emails to reduce the timing side channel. The value
  was later moved to `auth.dummy_password_hash` by `005-auth-dummy-hash-config`.
* Added `declare(strict_types=1);` to the `User` model.
* Added regression tests for non-Accept JSON errors, throttling JSON, the seeder guard, and log
  secrecy.

**Validation:** `php artisan test` passed (61 tests, 312 assertions); `AuthTest` passed (17 tests,
94 assertions); Pint passed; live flow verified `401`/`422` JSON without `Accept`, plus
login → profile → logout → `401` token reuse. Local database remained `books=0`, `tokens=0`,
`users=1`.

---

## Agent rules

1. Implement one task at a time. Mark real progress, not optimistic progress.
2. Report modified files and executed validations with actual results.
3. Do not skip tasks or expand the scope silently.
4. If the plan proves wrong, stop, explain, and propose an update. Do not patch around it.
5. Commit or push only when the human explicitly asks; use `TASK-NNN: <what changed>` when asked.
