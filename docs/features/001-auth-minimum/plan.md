# Feature Implementation Plan

# 001 · Auth Minimum

## General information

Related feature:

```text
docs/features/001-auth-minimum/spec.md
```

Status:

```text
completed
```

---

# Technical objective

Add the minimum authentication layer on top of `000-setup-base`:

* install Sanctum and its token table;
* seed one documented demo user;
* expose login, authenticated profile, and logout;
* enforce token expiration and login throttling;
* cover the behavior with feature tests;
* document the demo credentials and auth flow.

No public registration, roles, book data, UI, Docker, or CI/CD is added.

---

# Architecture analysis

## Current architecture

Laravel 12.70.1 runs as an API-only modular monolith on SQLite. It exposes
`GET /api/v1/health`, uses a public health controller, and has a feature test for that response.
The framework users table exists, `DatabaseSeeder` is empty, and there is no authentication
package or protected behavior.

## Architectural impact

* **Backend:** auth routes, controller, form request, resource, rate limiter, Sanctum integration.
* **Database:** new Sanctum `personal_access_tokens` table; no change to the users schema.
* **Seeding:** one demo user through an idempotent seeder.
* **Tests:** feature coverage for login, profile, logout, expiration, throttling, and secrets.
* **Documentation:** README credentials and auth examples; environment token lifetime.
* **Frontend / infrastructure / integrations:** none.

## Architectural decisions

| ID | Decision | Motive | Alternatives | Consequence |
| -- | -------- | ------ | ------------ | ----------- |
| D-001 | Install Sanctum with Composer and publish its config/migration; do not run `install:api`. | The API routes and bootstrap already exist; `install:api` can create or touch files unnecessarily. | Use `php artisan install:api`; implement custom token auth. | Package, config, and migration are added without changing the existing health route. |
| D-002 | Seed one demo user with an idempotent seeder and document the credentials. | The reviewer needs a known account; the feature forbids public registration. | Manual user creation; registration endpoint; factory only in tests. | `demo@example.com` / `Password123!` is test-only data; re-seeding is safe. |
| D-003 | Validate credentials with `Hash::check` and return a generic 401. | Avoids session side effects from `Auth::attempt` and prevents user enumeration. | `Auth::attempt`; separate messages for unknown email and wrong password. | Wrong password and unknown email produce the same safe response. |
| D-004 | Use `/api/v1/login`, `/api/v1/me`, and `/api/v1/logout`; protect the last two with `auth:sanctum`. | Keeps the versioned API surface small and obvious. | `/api/v1/auth/*`; unversioned routes. | Three endpoints, one public login and two token-protected routes. |
| D-005 | Define a named `login` rate limiter: 5 attempts/minute by lowercased email + IP. | The spec requires login throttling; general API limits remain in 004. | No login throttle; in-controller limiter; global API limiter now. | The sixth attempt in the window returns 429; the rest of the API is untouched. |
| D-006 | Configure Sanctum token expiration to 7 days and revoke only the current token on logout. | Matches the accepted security decisions and normal API session behavior. | Never-expiring tokens; revoke all user tokens on logout. | Tokens expire; logging out one session leaves other sessions valid. |
| D-007 | Keep basic JSON auth errors now; the shared error envelope is finalized in 004. | Keeps this slice focused on behavior rather than contract hardening. | Build the full envelope now. | 401/422/429 responses are JSON; 004 owns the final shared shape. |

---

# Implementation strategy

1. Install Sanctum, publish its config and migration, add `HasApiTokens` to `User`, configure the
   7-day expiration, and run the migration.
2. Add the idempotent demo-user seeder and invoke it from `DatabaseSeeder`; update the setup path
   so the demo user is created.
3. Add the login request, controller action, named limiter, resource, and public login route.
4. Add the authenticated profile route and controller action.
5. Add logout with current-token revocation.
6. Add feature tests for the full contract, including expiration and throttling.
7. Update the README, run the quality gate, and verify the documented curl flow.

Use `composer require laravel/sanctum`, then publish the config/migration tags. Do not use the
API installer because `routes/api.php` and `bootstrap/app.php` are already configured.

---

# Affected components

| Path | Responsibility | Expected change |
| ---- | -------------- | --------------- |
| `composer.json`, `composer.lock` | Dependencies and setup script. | Add `laravel/sanctum`; seed the demo user in `setup`. |
| `config/sanctum.php` | Sanctum configuration. | Set token expiration to 7 days through env/config. |
| `database/migrations/*_create_personal_access_tokens_table.php` | Token storage schema. | New published Sanctum migration. |
| `app/Models/User.php` | User model and relationships. | Add `HasApiTokens`. |
| `database/seeders/DatabaseSeeder.php` | Seed entry point. | Call the demo-user seeder. |
| `database/seeders/DemoUserSeeder.php` | Demo account. | New idempotent seeder with documented credentials. |
| `routes/api.php` | Versioned API routes. | Add login, profile, and logout routes. |
| `app/Http/Controllers/Api/V1/AuthController.php` | Auth orchestration. | New controller with login, profile, and logout actions. |
| `app/Http/Requests/Auth/LoginRequest.php` | Login validation. | New form request for email and password. |
| `app/Http/Resources/UserResource.php` | Public user representation. | New resource exposing id, name, and email only. |
| `app/Providers/AppServiceProvider.php` | Application services. | Register the named login rate limiter. |
| `tests/Feature/AuthTest.php` | Auth contract coverage. | New feature test for the complete flow. |
| `.env.example`, `.env` | Environment configuration. | Add the 7-day token expiration value. |
| `README.md` | Usage documentation. | Add demo credentials and auth examples; remove “no auth” statements. |

---

# New components

| Component | Location | Responsibility |
| --------- | -------- | -------------- |
| `AuthController` | `app/Http/Controllers/Api/V1/AuthController.php` | Issue tokens, return the current profile, and revoke the current token. |
| `LoginRequest` | `app/Http/Requests/Auth/LoginRequest.php` | Validate email/password input. |
| `UserResource` | `app/Http/Resources/UserResource.php` | Expose only the public user identity. |
| `DemoUserSeeder` | `database/seeders/DemoUserSeeder.php` | Create or refresh the documented demo account. |
| `AuthTest` | `tests/Feature/AuthTest.php` | Verify login, profile, logout, expiration, throttling, and secret safety. |

---

# Data model

## New entities

None in the application domain. Sanctum adds infrastructure storage:

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | bigint | Token identifier. |
| `tokenable_type`, `tokenable_id` | morphs | Owner of the token. |
| `name` | string | Token name (`api`). |
| `token` | string, unique | Hashed token value. |
| `abilities` | text, nullable | Token abilities. |
| `last_used_at` | timestamp, nullable | Last successful use. |
| `expires_at` | timestamp, nullable | Optional explicit expiration. |
| `created_at`, `updated_at` | timestamps | Audit timestamps. |

## Existing changes

* The users table already provides `id`, `name`, `email`, `password`, and timestamps.
* No columns are added to users.
* The demo user is a seeded row, not a schema change.
* No book data or book tables are introduced.
* Token expiration is enforced by Sanctum configuration (7 days).

---

# API / Contracts

## Login

Method: `POST`

Route: `/api/v1/login`

Request:

```json
{
  "email": "demo@example.com",
  "password": "Password123!"
}
```

Success `200`:

```json
{
  "data": {
    "token": "<plain-text-token>",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Demo User",
      "email": "demo@example.com"
    }
  }
}
```

Errors:

* `401` generic invalid credentials.
* `422` validation errors for missing or malformed data.
* `429` when the login limiter is exceeded.

## Profile

Method: `GET`

Route: `/api/v1/me`

Authentication: `Bearer <token>`

Success `200`:

```json
{
  "data": {
    "id": 1,
    "name": "Demo User",
    "email": "demo@example.com"
  }
}
```

Errors: `401` for missing, malformed, revoked, or expired tokens.

## Logout

Method: `POST`

Route: `/api/v1/logout`

Authentication: `Bearer <token>`

Success `200`:

```json
{
  "message": "Logged out."
}
```

Errors: `401` without a valid token.

The final shared error envelope is deferred to `004-api-contract-hardening`.

---

# External integrations

None.

---

# Security

* **Authentication:** Sanctum bearer tokens; hashed passwords; no session dependency.
* **Authorization:** `auth:sanctum` on profile and logout; no roles.
* **Token lifetime:** 7 days; expired tokens are rejected.
* **Logout:** deletes only the current token.
* **Login errors:** the same generic 401 for wrong password and unknown email.
* **Validation:** email format and required password through a Form Request.
* **Throttling:** 5 login attempts/minute by lowercased email + IP.
* **Secrets:** passwords and tokens are never returned or logged; `UserResource` exposes only
  id, name, and email.
* **Registration:** no public registration route or controller.
* **Scope:** no book data, roles, admin, or cross-user access.

Deferred to `004-api-contract-hardening`: general API rate limiting and the final shared error
envelope.

---

# Performance

* One token lookup per authenticated request; the Sanctum token column is unique and indexed.
* Login does one user lookup and one password hash check.
* No caching, queues, or background jobs are introduced.
* No performance tuning is needed for this low-volume assessment slice.

---

# Tests required

## Unit tests

* None required initially; the behavior is HTTP and token integration. Extract a unit only if a
  reusable rule appears.

## Integration tests

* Successful login returns a token and public user; the token accesses the profile.
* Wrong password and unknown email return the same generic 401.
* Missing or malformed email/password returns 422 with field errors.
* Profile without a token, with a malformed token, and with a revoked token returns 401.
* Expired token (travel 8 days) returns 401.
* Logout revokes the current token; another active token remains valid.
* The sixth login attempt in a minute returns 429.
* Responses and logs do not contain the password or stored token secret.
* No public registration route exists.

## Manual tests

* Follow the README from a clean checkout, seed the demo user, and log in with the documented
  credentials.
* Call the profile endpoint with the returned token.
* Log out and confirm the same token is rejected.
* Confirm the README does not imply that book features exist yet.

---

# Migrations required

* **New migration:** Sanctum `personal_access_tokens` table.
* **Modified migrations:** none.
* **Users table:** unchanged; already created by `000-setup-base`.
* **Seed:** idempotent demo user with documented credentials.
* **Compatibility:** the migration uses portable column types; SQLite remains the local engine.
* **Rollback:** the Sanctum migration can be rolled back; the demo user is removable by its email.

---

# Risks

| Risk | Impact | Mitigation |
| ---- | ------ | ---------- |
| Sanctum installation touches existing API routing. | High | Use Composer plus published config/migration; do not run `install:api`; review the diff. |
| Login limiter keys or middleware order behave unexpectedly. | Medium | Define one named limiter and cover the sixth-attempt case with a feature test. |
| Token expiration is configured in the wrong unit. | High | Sanctum expects minutes; use `10080` (7 days) and test with time travel. |
| Demo seeder duplicates or resets users unexpectedly. | Medium | Use `updateOrCreate` by email; document test-only credentials. |
| Wrong password and unknown email return different responses. | High | Share one generic 401 path and test both. |
| Logout revokes all tokens instead of the current one. | Medium | Delete the current token only and test two active tokens. |
| Protected routes return HTML or inconsistent errors. | Medium | Use `getJson`/`postJson` in tests; document the JSON contract; 004 hardens the envelope. |
| `composer setup` stops creating the demo user. | Medium | Update the setup script to run migrations with seed. |
| Demo credentials leak into logs or responses. | High | Never log passwords/tokens; use `UserResource`; assert secrets are absent. |
| The feature grows into roles or registration. | Medium | Keep those items explicitly out of scope and reject scope creep. |

---

# Technical dependencies

* Laravel Sanctum (first-party).
* Existing Laravel 12.70.1, PHP 8.2.12, SQLite, PHPUnit, and Pint.
* No additional third-party packages.
* No external services.
* `000-setup-base` must be complete.

---

# Implementation order

1. Install Sanctum, publish config/migration, add `HasApiTokens`, configure 7-day expiration, and
   migrate.
2. Add `DemoUserSeeder`, wire it into `DatabaseSeeder`, and update the setup script to seed it.
3. Add `LoginRequest`, `UserResource`, the named login limiter, the login action, and its route.
4. Add the authenticated profile route and action.
5. Add logout with current-token revocation.
6. Add the feature test suite for login, profile, logout, expiration, throttling, and secrets.
7. Update the README with credentials and examples; run Pint, tests, build, and cleanup.

---

# Plan validation

Before implementing, verify:

* [x] It respects `architecture.md`.
* [x] It respects `tech-stack.md`.
* [x] It respects `conventions.md`.
* [x] It considers security.
* [x] It considers tests.
* [x] It defines affected files.
* [x] It defines risks.
* [x] It has human approval (2026-10-26).

---

## Post-completion architecture note (2026-10-26)

ADR-008 adopted one service per business controller. The auth logic originally in
`AuthController` was moved to `AuthService` without changing the API or behavior. This plan remains
the historical implementation record; the retrofit is completed as `AUTH-001`, and service tests
live in `tests/Feature/Services/AuthServiceTest.php`.
