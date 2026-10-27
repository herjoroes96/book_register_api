# Project Context

## Purpose

This document contains dynamic information about the current project.

It must be updated as the project evolves and serves as context memory for the AI.

Unlike `docs/constitution/`, this file may change frequently.

---

# General information

## Project name

test_backend_sas — Book Registry API

---

## Description

REST API built with Laravel/PHP that lets authenticated users keep a private registry of their
own books. Users can log in, log out, and create, view, update, and delete only their own books.

The project is a technical hiring assessment focused on structure, security, validation,
database design, maintainability, scalability, and the ability to explain every decision.

---

## Current status

State:

* [ ] Initial idea
* [ ] Analysis
* [ ] Design
* [x] Development
* [ ] Testing
* [ ] Production
* [ ] Maintenance

Description of the current state:

The Laravel 12.70.1 application is scaffolded, configured, migrated, documented, and verified.
`000-setup-base`, `001-auth-minimum`, and `002-books-create-list-show` are complete.
`003-books-update-delete` is complete. The book CRUD API is implemented, documented, and verified;
`004-api-contract-hardening` is complete. The JSON error envelope, safe 500 responses, and API rate
limits are implemented and documented. `005-auth-dummy-hash-config` is complete. `006-delivery-documentation` TASK-001 through TASK-003
are complete; TASK-004 is next.

---

# Current objectives

## Primary objective

Complete `006-delivery-documentation` one task at a time, starting with TASK-004, while keeping
lint, tests, and build green.

---

## Secondary objectives

* Complete TASK-004 and TASK-005 in order, one task at a time.
* Keep tests, validation, ownership isolation, and the JSON error contract mandatory.
* Keep documentation and code in English.
* Preserve the proportionate, explainable architecture defined in the constitution.

---

# Current architecture

Summary:

* API-only modular monolith.
* HTTP layer with controllers, form requests, and API resources.
* Application/business layer with one service per business controller: `AuthService` and
  `BookService` are implemented.
* Domain/model layer with Eloquent models and ownership scoping.
* No frontend.
* No repository layer and no Actions pattern.

Reference:

```text
docs/constitution/architecture.md
```

---

# Current stack

## Frontend

Technologies:

* N/A — API-only project.

Status:

No UI is in scope. If a UI is added, `docs/constitution/ui-ux.md` applies.

---

## Backend

Technologies:

* Laravel 12.70.1
* PHP 8.2.12
* Laravel Sanctum
* PHPUnit
* Laravel Pint

Status:

Laravel 12.70.1, Sanctum 4.3.3, PHPUnit, and Pint are installed. Authentication and book CRUD are
implemented and the current test suite passes.

---

## Database

Technology:

SQLite 3.x for delivery; MySQL 8.x compatible target.

Status:

SQLite is migrated with the framework tables, `personal_access_tokens`, `books`, and the demo
user.

---

## Infrastructure

Services:

* Local development only (XAMPP/PHP 8.2 + Composer 2.5.4).
* No Docker.
* No CI/CD pipeline.
* No production environment.

Status:

Local setup is provided and documented by `000-setup-base`; SQLite is migrated and seeded with
the demo user.

---

# Recent decisions

## Decision — Requirements, engineering standards, and constitution approved

Date:

2026-10-26

Description:

The human approved the structured requirements, the engineering standards, and all constitution
documents. The project moved from analysis/design into feature specification.

Motive:

The scope, quality gates, architecture, and product boundaries are clear enough to start
slicing features.

Impact:

Feature specs can now be created. No code starts until a feature spec is approved and its plan
and tasks exist.

## Decision — Feature `000-setup-base` specification and plan approved; tasks proposed

Date:

2026-10-26

Description:

The functional specification and technical plan for the bounded setup feature were approved, and
the task list was created.

Motive:

Every later feature needs a runnable, verified project foundation.

Impact:

The next gate is task approval, followed by implementation one task at a time. The setup feature
must stay free of business logic.

## Decision — TASK-001 setup completed

Date:

2026-10-26

Description:

Laravel 12.70.1 was scaffolded and merged without touching project documentation. The local
environment, application key, SQLite file, and safe drivers were configured. The default web
route, `/up` health route, and failing feature example test were removed.

Motive:

Establish a runnable, API-only baseline that can pass the build gate before business features
start.

Impact:

TASK-002 can start. No authentication, users, books, or domain behavior exists. The local cache
and session use files and the queue uses sync (ADR-001).

## Decision — Adopt one service per business controller

Date:

2026-10-26

Description:

The project adopted `app/Services` with one service per business controller. Controllers handle
HTTP only; services own business rules, ownership, transactions, and token operations. Form
Requests validate and API Resources shape JSON. No repository or Actions pattern is used.
`HealthController` remains an infrastructure exception.

Motive:

Make business logic testable and keep controllers thin without adding repository ceremony.

Impact:

`AuthService` and `BookService` own their business behavior; `AuthController` and `BookController`
delegate to them.

## Key constitution decisions

* Modular monolith, no microservices.
* Sanctum bearer tokens with 7-day expiration.
* SQLite delivery, MySQL-portable schema.
* Ownership enforced through scoped queries with 404 for foreign resources.
* Versioned API at `/api/v1`.
* No public registration, no roles, no frontend.
* No Docker or CI/CD in the current scope.
* One service per business controller; no repository and no Actions pattern.
* Tests and lint are part of the Definition of Done.

References:

```text
docs/constitution/architecture.md
docs/constitution/tech-stack.md
```

---

# Current problems

## Deadline and repository visibility are undefined

Description:

The brief does not specify a delivery deadline or whether the Git repository should be public,
private, or shared by link.

Impact:

These do not block design or implementation. They may affect optional hardening and delivery
logistics.

Status:

* Pending

Proposed solution:

Proceed with documented assumptions and confirm before delivery. See Q1 and Q2 in
`docs/project/requirements.md`.

---

# Work in progress

## Current feature

Name:

006-delivery-documentation

Status:

in progress — TASK-001 through TASK-003 completed; TASK-004 next

Reference:

```text
docs/features/006-delivery-documentation/tasks.md
```

---

## Next tasks

Order:

1. TASK-004 — verify the published delivery from a clean clone.
2. TASK-005 — run the final gate and close the feature.

---

# Temporary constraints

* Implement one task at a time from the approved list; do not skip ahead.
* Do not start `006-delivery-documentation` work until its spec, plan, and tasks are approved.
* No UI, Docker, or CI/CD work in the current scope.
* All documentation and code are written in English; working discussions remain in Spanish.
* Do not edit protected files listed in `docs/constitution/constraints.md`.

---

# External dependencies

Services:

None.

---

# Important data

* The application is an API-only Laravel project.
* The delivery database is SQLite; the schema must remain MySQL-portable.
* There is no public registration; a documented demo user is seeded for the assessment.
* Token expiration is 7 days; logout revokes the current token.
* `isbn` is a string, digits only, max 20, not unique.
* `value` is a non-negative `decimal(12,2)` serialized as a string.
* The API is versioned at `/api/v1`.
* All `/api/*` responses are JSON, including errors when the client omits `Accept: application/json`.
* The demo seeder runs only when `APP_ENV` is `local` or `testing`.
* Local cache/session use the file driver and the queue uses the sync driver (ADR-001).
* Health check: public `GET /api/v1/health` returns `200 {"status":"ok"}` or a safe
  `503 {"status":"unavailable"}`. `HealthCheckTest` covers the public 200 JSON contract.
* SQLite is migrated with the framework tables and `personal_access_tokens`; `DatabaseSeeder`
  creates the demo user.
* Sanctum v4.3.3 is installed; token expiration is 10080 minutes (7 days).
* Demo user seeded (`demo@example.com`); credentials are test-only and documented in the README.
* Login: public `POST /api/v1/login` returns a Sanctum token and public user; generic 401, JSON 422,
  and 429 throttling are implemented.
* Profile: `GET /api/v1/me` is protected by `auth:sanctum` and returns the public user identity.
* Logout: `POST /api/v1/logout` revokes the current token only; other active tokens remain valid.
* The auth suite covers expiration, login throttling, no public registration, and secret safety.
* `001-auth-minimum` is complete and `AuthController` now delegates to `AuthService`.
* ADR-008: business logic lives in one service per business controller; controllers are HTTP-only,
  with no repository or Actions pattern. DB-backed service tests live in `tests/Feature/Services`.
* `books` has its schema, model, factory, user relation, `BookService`, and protected CRUD
  endpoints. Partial updates and permanent deletion are implemented, tested, and documented.
* Book route identifiers are numeric; malformed identifiers return 404.
* The final `books` index is `(user_id, created_at)`; the redundant `user_id` index was removed.
* Update validation runs before the ownership lookup: invalid payloads return `422` even for
  foreign/missing books; valid payloads return `404`.
* API errors use one JSON envelope: `401`, `404`, `405`, `422`, `429`, and safe `500`.
* Rate limits: login 5/minute by email + IP; all non-login `/api/*` traffic 60/minute by IP or
  authenticated user, including unauthenticated protected and unknown routes.
* Throttled responses include `Retry-After` and `X-RateLimit-*`; rejected requests do not mutate data.
* Unknown-email login uses `auth.dummy_password_hash`, a configurable non-secret hash with a safe
  default, optional `AUTH_DUMMY_PASSWORD_HASH` override, and fail-fast validation of algorithm,
  cost, and options against the configured hasher.
* `003-books-update-delete` is complete; the local database was left clean (`books=0`, `tokens=0`,
  `users=1`) after the live create → update → delete flow.
* Definition of Done commands:
  * `./vendor/bin/pint --test`
  * `php artisan test`
  * `php artisan optimize`

---

# Change history

## Date

2026-10-26

Change:

Requirements, engineering standards, and constitution approved. Context updated to English and
to the current design state.

Motive:

Record the approval gate and keep dynamic project memory aligned with the approved documents.

## Date

2026-10-26

Change:

Created the `000-setup-base` feature specification.

Motive:

Start the first thin slice: a runnable and verifiable project foundation.

## Date

2026-10-26

Change:

Approved the `000-setup-base` specification and technical plan, and created the task list.

Motive:

Move the first thin slice to the implementation gate.

## Date

2026-10-26

Change:

Completed TASK-001: scaffolded Laravel 12.70.1, configured the local environment and SQLite,
removed the default web/`/up` routes and failing example test, and verified the DoD.

Motive:

Establish the runnable API-only foundation for the remaining setup tasks.

## Date

2026-10-26

Change:

Completed TASK-002: removed the welcome view, CSS/JS assets, package.json, vite.config.js, and
npm/Vite scripts from composer.json; kept resources/views with .gitkeep.

Motive:

Keep the repository API-only with no unused frontend tooling.

## Date

2026-10-26

Change:

Completed TASK-003: registered `GET /api/v1/health` and added the invokable HealthController with
a database connection check and a safe 503 response.

Motive:

Provide the first public, versioned API endpoint for the setup verification.

## Date

2026-10-26

Change:

Completed TASK-004: added `HealthCheckTest` with `RefreshDatabase`, exact JSON and content-type
assertions; removed the unit example.

Motive:

Protect the public health contract with an automated test.

## Date

2026-10-26

Change:

Completed TASK-005: applied the framework migrations, verified the health endpoint and an empty
users table, and removed the default Test User from `DatabaseSeeder`.

Motive:

Verify the local database and keep the setup free of demo data.

## Date

2026-10-26

Change:

Completed TASK-006 and closed `000-setup-base`: project README, setup and troubleshooting docs,
environment cleanup, composer setup with SQLite creation, final DoD, and feature documentation.

Motive:

Deliver the first complete, reviewable slice and leave the project ready for authentication.

## Date

2026-10-26

Change:

Created the `001-auth-minimum` functional specification.

Motive:

Define the authentication walking skeleton before creating its technical plan and code.

## Date

2026-10-26

Change:

Approved the `001-auth-minimum` specification and created its technical plan.

Motive:

Move the authentication walking skeleton to the plan gate.

## Date

2026-10-26

Change:

Created the `001-auth-minimum` task list with seven ordered tasks.

Motive:

Prepare the authentication walking skeleton for implementation.

## Date

2026-10-26

Change:

Completed TASK-001: installed Sanctum v4.3.3, published config/migration, added `HasApiTokens`,
configured 7-day expiration, and migrated the token table.

Motive:

Establish token storage before implementing login, profile, and logout.

## Date

2026-10-26

Change:

Completed TASK-002: added the idempotent demo-user seeder, wired it into `DatabaseSeeder`, and
updated `composer setup` to seed after migrating.

Motive:

Provide the documented demo account required by the login flow.

## Date

2026-10-26

Change:

Completed TASK-003: added the login endpoint, validation, Sanctum token issuance, public user
resource, and the named 5/minute login limiter, with tests.

Motive:

Provide the first working authentication flow.

## Date

2026-10-26

Change:

Completed TASK-004: added the authenticated `GET /api/v1/me` profile check with tests for valid,
missing, and malformed tokens.

Motive:

Expose the authenticated identity required by later book ownership.

## Date

2026-10-26

Change:

Completed TASK-005: added logout with current-token revocation and tests for logout, two-token
isolation, and unauthenticated logout.

Motive:

Complete the minimum session lifecycle for API clients.

## Date

2026-10-26

Change:

Completed TASK-006: added token-expiration, throttling, no-registration, and secret-safety tests.

Motive:

Protect the authentication contract's security edges before documentation and closure.

## Date

2026-10-26

Change:

Completed TASK-007 and closed `001-auth-minimum`: README auth documentation, live HTTP flow,
final DoD, and feature documentation updates.

Motive:

Deliver the first authentication slice and leave the project ready for book management.

## Date

2026-10-26

Change:

Created the `002-books-create-list-show` functional specification.

Motive:

Define the first book slice before creating its plan and code.

## Date

2026-10-26

Change:

Approved the `002-books-create-list-show` specification and created its technical plan.

Motive:

Move the first book slice to the plan gate.

## Date

2026-10-26

Change:

Created the `002-books-create-list-show` task list with six ordered tasks.

Motive:

Prepare the first book slice for implementation.

## Date

2026-10-26

Change:

Completed `002-books-create-list-show` TASK-001: added the books schema, model, factory, user
relation, and indexes.

Motive:

Establish the book data model before service and HTTP behavior.

## Date

2026-10-26

Change:

Completed `002-books-create-list-show` TASK-002: added `BookService` with owner-scoped create,
pagination, and lookup, plus service tests.

Motive:

Centralize book rules and ownership before adding HTTP endpoints.

## Date

2026-10-26

Change:

Completed `002-books-create-list-show` TASK-003: added book creation, validation, resource, and
protected route with HTTP tests.

Motive:

Deliver the first book-management endpoint.

## Date

2026-10-26

Change:

Completed `002-books-create-list-show` TASK-004: added the protected paginated book list with
ownership and ordering tests.

Motive:

Let users consult their own collection.

## Date

2026-10-26

Change:

Completed `002-books-create-list-show` TASK-005: added protected book detail with generic 404
for foreign or missing books.

Motive:

Complete the create/list/detail book slice.

## Date

2026-10-26

Change:

Completed TASK-006 and closed `002-books-create-list-show`: README book documentation, live HTTP
flow, final DoD, and feature documentation updates.

Motive:

Deliver the first complete book slice and leave the project ready for update/delete.

## Date

2026-10-26

Change:

Created the `003-books-update-delete` functional specification.

Motive:

Define partial updates and permanent deletion before planning the final book slice.

## Date

2026-10-26

Change:

Approved the `003-books-update-delete` specification and created its technical plan.

Motive:

Move the final book slice to the plan gate.

## Date

2026-10-26

Change:

Created the `003-books-update-delete` task list with five ordered tasks.

Motive:

Prepare partial update and hard delete for implementation.

## Date

2026-10-26

Change:

Completed `003-books-update-delete` TASK-001: added owner-scoped `BookService::updateForUser`
and service tests.

Motive:

Establish partial-update business logic before exposing the PATCH endpoint.

## Date

2026-10-26

Change:

Completed `003-books-update-delete` TASK-002: added owner-scoped `BookService::deleteForUser`
and service tests.

Motive:

Establish deletion business logic before exposing the DELETE endpoint.

## Date

2026-10-26

Change:

Adopted one service per business controller as the official architecture (ADR-008 in
`architecture.md`); ADR-004 is superseded. Context, conventions, constraints, tech stack, README,
and analysis were aligned. AUTH-001 is complete: `AuthController` delegates to `AuthService`.

Motive:

Consistent, testable business logic and thin controllers before building the book feature.

## Date

2026-10-26

Change:

Completed `003-books-update-delete` TASK-003: added `UpdateBookRequest`, `BookController@update`,
the protected `PATCH /api/v1/books/{book}` route, and eight HTTP tests.

Motive:

Expose partial book updates with validation, ownership isolation, generic 404 handling, and secret
safety before implementing deletion.

## Date

2026-10-26

Change:

Completed `003-books-update-delete` TASK-004: added `BookController@destroy`, the protected
`DELETE /api/v1/books/{book}` route, and four HTTP tests.

Motive:

Complete the book API CRUD contract with permanent owner-scoped deletion and data-safety coverage.

## Date

2026-10-26

Change:

Completed `003-books-update-delete` TASK-005 and closed the feature: README, live HTTP flow, spec
status, roadmap, tasks, learnings, known issues, and context.

Motive:

Finish the book CRUD slice with verified documentation and leave the project ready for
`004-api-contract-hardening`.

## Date

2026-10-26

Change:

Resolved the `001-auth-minimum` code-review findings: forced JSON for `/api/*`, guarded the demo
seeder to local/testing, added a dummy password hash for unknown emails, added
`strict_types` to the `User` model, and covered non-Accept JSON, the seeder guard, and log secrecy
with tests.

Motive:

Align the authentication contract with BR-04/BR-11 and close the production-readiness gaps found
during review without changing the API success behavior.

## Date

2026-10-26

Change:

Resolved the `002-books-create-list-show` code-review findings: restricted book route identifiers
to numbers, rendered unmatched `/api/*` exceptions as JSON, added boundary and ordering-tie tests,
and dropped the redundant `books_user_id_index` with a new migration.

Motive:

Prevent malformed identifiers from resolving existing books and close the remaining schema and
regression-coverage gaps without changing the successful create/list/detail contract.

## Date

2026-10-26

Change:

Resolved the `003-books-update-delete` code-review findings: marked the plan as completed, added
update boundary/owner-only/precedence tests, and documented that validation returns `422` before
the ownership lookup, while valid foreign/missing updates return `404`.

Motive:

Close the remaining documentation and regression-coverage gaps without changing the update or
delete behavior.

## Date

2026-10-26

Change:

Queued `005-auth-dummy-hash-config` after `004-api-contract-hardening` and renumbered delivery
documentation from `005` to `006` in the roadmap, requirements analysis, README, and project
context.

Motive:

Keep the dummy-hash configuration as a dedicated feature after the next slice and preserve delivery
documentation as the final feature.

## Date

2026-10-26

Change:

Created the `004-api-contract-hardening` functional specification with status `proposed`.

Motive:

Define the shared error envelope, rate limits, token-expiration verification, status-code rules,
and contract-test scope before planning.

## Date

2026-10-26

Change:

Approved the `004-api-contract-hardening` specification and created its technical plan.

Motive:

Move contract hardening to the plan gate without implementing code or redefining business
behavior.

## Date

2026-10-26

Change:

Approved the `004-api-contract-hardening` technical plan and created its six-task implementation
list.

Motive:

Move contract hardening to the implementation gate, one bounded task at a time.

## Date

2026-10-26

Change:

Completed `004-api-contract-hardening` TASK-001: centralized validation, authentication, framework
404, and 405 JSON responses; added four contract tests without an `Accept` header.

Motive:

Establish one tested error envelope before adding safe 500 handling and rate limiting.

## Date

2026-10-26

Change:

Completed `004-api-contract-hardening` TASK-002 through TASK-006 and closed the feature: safe 500
fallback, rate limits, contract regression tests, README, and final gate.

Motive:

Deliver the hardened API contract and leave authentication configuration as the next slice.

## Date

2026-10-26

Change:

Completed `005-auth-dummy-hash-config` TASK-001 through TASK-005 and closed the feature:
configurable dummy hash, service wiring, tests, documentation, and final gate.

Motive:

Deliver safe, maintainable unknown-email timing configuration.

## Date

2026-10-26

Change:

Applied the `005` review remediation: validation now enforces hasher algorithm, cost, and options;
added config-cache regression tests and a test-only cost-4 hash for the test environment.

Motive:

Close the gap where a valid but misaligned hash could silently weaken timing equalization.

---

# Update rules

Update this file when:

* the architecture changes;
* an important decision is made;
* a new constraint appears;
* the project state changes;
* a feature starts, is approved, or is completed.

Do not store here:

* permanent rules;
* developer experience;
* detailed technical documentation.

Those belong to:

```text
docs/constitution/
aipf/knowledge/
```

---

# AI usage

The reading order and decision hierarchy are defined in `AGENTS.md` (single source of truth).
This file (`docs/project/context.md`) is dynamic memory: review it to understand the current
state, recent decisions, and work in progress.

Project context takes priority over general assumptions.
