# Feature Implementation Plan

# 000 · Setup Base

## General information

Related feature:

```text
docs/features/000-setup-base/spec.md
```

Status:

```text
completed
```

---

# Technical objective

Create a runnable, verifiable Laravel API foundation without adding business logic.

The implementation must install Laravel into the existing repository without destroying project
documentation, configure SQLite, expose one public JSON health endpoint, add its test, make
lint/tests/build pass, and document local setup. Authentication, users, books, seed data, UI,
Docker, and CI/CD remain out of scope.

---

# Architecture analysis

## Current architecture

The repository contains only documentation and project configuration. There is no application,
dependency manifest, test suite, or local runtime. The target is the modular monolith API-only
architecture defined in `docs/constitution/architecture.md`, using SQLite for delivery and a
MySQL-portable schema. Authentication arrives in `001-auth-minimum`.

## Architectural impact

* **Backend:** Laravel skeleton, API route registration, one invokable health controller, one
  health feature test.
* **Frontend:** none. The default web welcome route and unused Vite/JS/CSS scaffolding are
  removed.
* **Database:** SQLite connection and local file; framework default migrations are applied to
  verify connectivity. No application domain tables.
* **Infrastructure:** local PHP/Composer runtime only; no Docker, CI/CD, hosting, or production.
* **Integrations:** none.

## Architectural decisions

| ID | Decision | Motive | Alternatives | Consequence |
| -- | -------- | ------ | ------------ | ----------- |
| D-001 | Scaffold Laravel in a temporary directory and merge the skeleton into the repository, preserving `docs/`, `aipf/`, `AGENTS.md`, `.aipf.json`, and `.opencode/`. | The repository is not empty; scaffolding in place can fail or overwrite documentation. | Scaffold in the root; manual skeleton; run the app from a subdirectory. | One root-level Laravel app with all project documentation preserved. The merge must be reviewed for overwrites. |
| D-002 | Register `routes/api.php` manually and do not install Sanctum or any auth package. | Laravel's API installer also adds Sanctum, which belongs to `001-auth-minimum`. | Run `php artisan install:api` and leave Sanctum unused; put health in `routes/web.php`. | Setup owns only API routing and health; Sanctum arrives in 001. |
| D-003 | Use an invokable `HealthController`, not a route closure. | `php artisan optimize` caches routes; closures cannot be serialized. | Closure route; framework `/up` route; console command. | The route is cacheable and testable, and the build gate stays valid. |
| D-004 | Health checks the database connection: `200 {"status":"ok"}` or `503 {"status":"unavailable"}`. | The spec requires the data store to be ready; a DB-aware probe verifies it. | Static response; detailed diagnostics; separate endpoint. | The endpoint depends on DB configuration. Failure details are never exposed. |
| D-005 | Apply framework migrations, but add no auth routes, controllers, seeders, or users. The `DatabaseSeeder` remains empty. | Migrations prove the database works and give later features a clean base. | Skip migrations; delete framework migrations; seed a demo user. | Framework users/cache/jobs tables exist, but no auth behavior or user data. 001 owns the demo user. |
| D-006 | Remove the default welcome route/view and unused frontend scaffolding (`package.json`, Vite, `resources/css`, `resources/js`). | The deliverable is API-only; unused frontend tooling implies a UI and adds Node setup. | Keep the default scaffolding; keep a temporary welcome page. | Only PHP and Composer are required. The empty views directory is kept to avoid path assumptions. |
| D-007 | Expose the health check publicly at `GET /api/v1/health`. | The check must work before authentication and align with the versioned API convention. | Unversioned `/health`; framework `/up`; authenticated health. | The only API endpoint in this feature is public and versioned. |

---

# Implementation strategy

1. Scaffold Laravel 12.x in a temporary directory and merge the skeleton without touching
   project documentation.
2. Configure `.env.example`, the local `.env`, the application key, and the SQLite file.
3. Register API routing, add the health controller, and remove the default web/frontend
   scaffolding.
4. Configure PHPUnit for in-memory SQLite, replace the example tests with the health test, and
   ensure Pint is available.
5. Apply framework migrations and verify the database connection and health endpoint.
6. Write the README, verify `.gitignore`/`.env.example`, and run the full quality gate.

Dependencies flow top to bottom. The database is verified only after the app boots and the
health route exists.

---

# Affected components

| Path | Responsibility | Expected change |
| ---- | -------------- | --------------- |
| `docs/`, `aipf/`, `AGENTS.md`, `.aipf.json`, `.opencode/` | Project documentation, kit, and agent configuration. | Preserved unchanged during scaffolding. |
| `bootstrap/app.php` | Application bootstrapping and route registration. | Register `routes/api.php`; remove the default web route and `/up` health route. |
| `routes/web.php` | Default web routes. | Remove the welcome route or delete the file. |
| `resources/views/welcome.blade.php` | Default welcome page. | Remove; keep the views directory. |
| `resources/css`, `resources/js`, `package.json`, `vite.config.js` | Default frontend pipeline. | Remove; no UI or Node build step. |
| `.env.example` | Local environment template. | Set project name, local URL, SQLite connection, and safe defaults; no secrets. |
| `.gitignore` | Version-control exclusions. | Verify `.env`, `vendor/`, `database/*.sqlite`, storage logs, and PHPUnit cache are ignored. |
| `phpunit.xml` | Test environment. | Use in-memory SQLite and safe test defaults. |
| `composer.json` | PHP dependencies and tools. | Ensure Laravel 12.x and Laravel Pint; no Sanctum yet. |
| `README.md` | Onboarding and setup documentation. | Replace the framework README with project-specific setup and scope. |
| `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | Framework example tests. | Remove or replace; the health test becomes the baseline. |
| `database/migrations/`, `database/seeders/DatabaseSeeder.php` | Framework schema and seed entry point. | No feature-specific changes; migrations applied; seeder remains empty. |
| `app/Models/User.php` | Framework user model. | Unchanged; no auth behavior; used later by 001. |

---

# New components

| Component | Location | Responsibility |
| --------- | -------- | -------------- |
| `HealthController` | `app/Http/Controllers/Api/V1/HealthController.php` | Return public JSON health status and check the database connection safely. |
| API routes | `routes/api.php` | Register the versioned health route and provide the file later API features extend. |
| `HealthCheckTest` | `tests/Feature/HealthCheckTest.php` | Verify the public health endpoint's status and JSON contract. |

---

# Data model

## New entities

None. This feature adds no application entities.

| Field | Type | Description |
| ----- | ---- | ----------- |
| N/A | N/A | No application data is added. |

## Existing changes

* No new columns, relationships, indexes, or application tables.
* Framework default migrations (users, cache, jobs) are applied to verify the database.
* The SQLite database file is local and ignored by version control.
* No seed data and no demo user.
* No schema changes to the framework migrations.

---

# API / Contracts

## Endpoint

Method:

```text
GET
```

Route:

```text
/api/v1/health
```

Description:

```text
Public health check. Confirms that the application is running and the database connection is
available. Requires no authentication.
```

## Request

No body, query parameters, or authentication headers.

```json
{}
```

## Response

Healthy:

```json
{
  "status": "ok"
}
```

Status code: `200`

Unhealthy database:

```json
{
  "status": "unavailable"
}
```

Status code: `503`

Both responses use `Content-Type: application/json`. No configuration, credentials, exception
details, or internal paths are exposed.

---

# External integrations

None.

---

# Security

* **Authentication:** not required for health; no auth is implemented in this feature.
* **Authorization:** not applicable; only service health is exposed.
* **Validation:** no request body to validate.
* **Sensitive data:** no secrets, credentials, connection strings, stack traces, or internal
  paths are returned or logged.
* **Configuration:** `.env` is local and ignored; `.env.example` contains placeholders only.
* **Errors:** database failures return a generic 503 and a safe log message.
* **Build safety:** the invokable controller keeps route caching compatible with
  `php artisan optimize`.
* **Scope safety:** no auth, user, book, or domain behavior is added.

Deferred to later features: rate limiting (`004-api-contract-hardening`), authentication and
token security (`001-auth-minimum`), common JSON exception envelope
(`004-api-contract-hardening`), production HTTPS and monitoring (out of scope).

---

# Performance

* One database connection per health request; negligible for a setup probe.
* No domain queries, caching, queues, or background jobs.
* `php artisan optimize` verifies configuration and route caching for a production-like build.
* No performance tuning is added because there is no workload yet.

---

# Tests required

## Unit tests

* None; there are no extracted business rules or value objects.

## Integration tests

* `GET /api/v1/health` returns `200` and exactly `{"status":"ok"}`.
* The endpoint is reachable without authentication.
* The test runs against in-memory SQLite and confirms the database connection used by health.
* The `503` path is verified manually with an invalid local connection, or with a controlled
  mock if it remains simple and stable.

## Manual tests

* Fresh checkout followed by the documented setup.
* Start the app, call health, and confirm the JSON.
* Blank a required environment value; confirm a clear failure without secret leakage.
* Point at a missing/invalid SQLite file; confirm a safe 503.
* Start on an occupied port; confirm troubleshooting guidance.
* Run Pint, tests, and build; confirm all pass.
* Start without Node/npm; confirm the app still runs.

---

# Migrations required

* **New migrations:** none.
* **Modified migrations:** none.
* **Framework migrations:** default users/cache/jobs migrations are applied to verify the
  database connection.
* **Compatibility:** SQLite locally; no SQLite-only schema feature.
* **Data:** no seed data, no demo user, no domain data.
* **Rollback:** not required; the database can be recreated from scratch.

---

# Risks

| Risk | Impact | Mitigation |
| ---- | ------ | ---------- |
| Scaffolding overwrites project documentation. | High | Scaffold in a temporary directory, copy only application files, and verify preserved paths. |
| Laravel 12 is incompatible with local PHP. | High | Validate PHP 8.2.12 and extensions first; stop and report rather than silently downgrade. |
| Route caching fails due to a closure or config issue. | Medium | Use the invokable controller, avoid closures, and run `php artisan optimize` during implementation. |
| Default example test expects the removed welcome page. | Medium | Remove/replace it in the same task that removes the web route. |
| Removing frontend files leaves a reference. | Medium | Search for `vite`, `resources/css`, `resources/js`, and `welcome`; keep the empty views directory. |
| SQLite file is missing or migrations fail. | Medium | Create `database/database.sqlite` explicitly, verify connection, run migrations, document recovery. |
| Framework users table is mistaken for auth work. | Low | Document it as framework scaffolding; add no auth routes/controllers/seeders; leave auth to 001. |
| Health check leaks database details. | Medium | Return a generic 503 and log only a safe, generic message. |
| Windows command differences. | Low | Document Git Bash and PowerShell forms; use the PHP one-liner for the SQLite file. |
| `php artisan optimize` caches local config. | Low | Run `php artisan optimize:clear` after the build check and document it. |

---

# Technical dependencies

* PHP 8.2.12 with the SQLite PDO extension.
* Composer 2.5.4.
* Laravel 12.x.
* Laravel Pint (development dependency).
* PHPUnit as shipped with Laravel 12.x.
* SQLite 3.x.
* No Sanctum, Node.js, npm, Docker, or CI/CD in this feature.

---

# Implementation order

1. Scaffold and merge Laravel without touching project documentation.
2. Configure `.env.example`, local `.env`, application key, and SQLite file.
3. Register API routing, add the health controller, and remove the default web/health route.
4. Remove the welcome view and unused frontend scaffolding; verify no references remain.
5. Configure PHPUnit, replace example tests with the health test, and ensure Pint is available.
6. Apply framework migrations and verify the database and health endpoint.
7. Write the README, verify `.gitignore`/`.env.example`, run Pint/tests/build, and complete the
   manual checks; clear build caches afterward.

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
