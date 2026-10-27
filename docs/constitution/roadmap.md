# Roadmap.md

# Project Roadmap

> **Status:** Approved by the human on 2026-10-26.

## Purpose

This document defines the planned evolution of the product.

It shows:

* completed features;
* current features;
* upcoming features;
* future ideas.

It must be kept up to date during development.

---

# Current project status

## Phase

Possible values:

* Definition.
* Design.
* Initial development.
* Production.
* Scaling.
* Maintenance.

Value:

```text
Initial development
```

The Laravel 12.70.1 application is scaffolded, configured, and migrated. `000-setup-base`,
`001-auth-minimum`, `002-books-create-list-show`, and `003-books-update-delete` are complete.

---

# Current objective

Complete `006-delivery-documentation` TASK-004 and TASK-005 one task at a time.

---

# Completed features

List of completed features.

## 000 · setup-base

Status:

```text
Completed
```

Description:

```text
Laravel 12 foundation with SQLite, versioned health check, health test, and setup documentation.
No business logic.
```

Date:

```text
2026-10-26
```

Reference:

```text
docs/features/000-setup-base/
```

## 001 · auth-minimum

Status:

```text
Completed
```

Description:

```text
Demo login, authenticated profile, logout, Sanctum tokens, 7-day expiration, login throttling,
tests, and documentation.
```

Date:

```text
2026-10-26
```

Reference:

```text
docs/features/001-auth-minimum/
```

## 002 · books-create-list-show

Status:

```text
Completed
```

Description:

```text
Owner-scoped book creation, paginated listing, detail lookup, validation, ownership rules, and
HTTP/service tests using BookService.
```

Date:

```text
2026-10-26
```

Reference:

```text
docs/features/002-books-create-list-show/
```

## 003 · books-update-delete

Status:

```text
Completed
```

Description:

```text
Owner-scoped partial update and permanent deletion, validation, ownership isolation, HTTP/service
tests, README examples, and a verified end-to-end flow.
```

Date:

```text
2026-10-26
```

Reference:

```text
docs/features/003-books-update-delete/
```

## 004 · api-contract-hardening

Status:

```text
Completed
```

Description:

```text
Uniform JSON error envelope, safe 500 responses, login/public/user rate limits, rate-limit headers,
token-expiration coverage, and cross-cutting contract tests.
```

Date:

```text
2026-10-26
```

Reference:

```text
docs/features/004-api-contract-hardening/
```

## 005 · auth-dummy-hash-config

Status:

```text
Completed
```

Description:

```text
Configurable unknown-email dummy password hash with a safe default, environment override,
fail-fast validation, configuration-cache verification, tests, and documentation.
```

Date:

```text
2026-10-26
```

Reference:

```text
docs/features/005-auth-dummy-hash-config/
```

Format for future entries:

## NNN · Feature name

Status:

```text
Completed
```

Description:

```text
What problem it solved.
```

Date:

```text
YYYY-MM-DD
```

Reference:

```text
docs/features/NNN-feature-name/
```

---

# Features in development

List of currently active features.

Rule:

Ideally, only one main feature should be in development at a time.

`006-delivery-documentation` is in progress: TASK-001 through TASK-003 are complete and TASK-004
and TASK-005 remain.

Other future entries:

## NNN · Feature name

Status:

```text
In progress
```

Objective:

```text
What is being built.
```

Progress:

```text
Describe completed work and remaining work.
```

Reference:

```text
docs/features/NNN-feature-name/
```

---

# Upcoming features

Planned features, ordered by priority.

## 006 · delivery-documentation

Objective:

```text
README with setup, demo credentials, assumptions, key decisions, scalability notes, .env.example,
and a clean-install verification.
```

Priority:

* High.

Dependencies:

```text
000 · setup-base; 001 · auth-minimum; 002 · books-create-list-show;
003 · books-update-delete; 004 · api-contract-hardening; 005 · auth-dummy-hash-config.
```

---

# Idea backlog

Ideas that could be added in the future.

These ideas:

* are not approved;
* must not be implemented automatically;
* require prior analysis and a feature specification.

## Public registration

Description:

```text
Allow users to create their own accounts and obtain credentials.
```

Expected value:

```text
Makes the API self-service instead of relying on seeded users.
```

## Search and filtering

Description:

```text
Filter books by name or ISBN and add configurable ordering.
```

Expected value:

```text
Improves usability for larger personal libraries.
```

## Roles and administration

Description:

```text
Add roles, administrators, and cross-user visibility.
```

Expected value:

```text
Supports teams or managed registries.
```

## Soft deletes and audit history

Description:

```text
Recover deleted books and record who changed what.
```

Expected value:

```text
Improves recoverability and accountability.
```

## Multi-tenancy

Description:

```text
Introduce organizations or tenant boundaries above individual users.
```

Expected value:

```text
Supports organizations with isolated data.
```

## Frontend

Description:

```text
Add a web client consuming the API.
```

Expected value:

```text
Provides a visual interface; would require applying docs/constitution/ui-ux.md.
```

## OpenAPI, Docker, and CI/CD

Description:

```text
Add API documentation, containerized setup, and automated pipelines.
```

Expected value:

```text
Improves developer experience and delivery automation. Currently out of scope.
```

---

# Dependencies between features

## Feature 001

Depends on:

```text
000 · setup-base
```

Motive:

```text
Authentication needs the installed Laravel application, database, routes, and test setup.
```

## Feature 002

Depends on:

```text
001 · auth-minimum
```

Motive:

```text
Book endpoints require an authenticated user and a working token flow.
```

## Feature 003

Depends on:

```text
002 · books-create-list-show
```

Motive:

```text
Update and delete require the book model, migration, ownership boundary, and existing CRUD
conventions.
```

## Feature 004

Depends on:

```text
001, 002, 003
```

Motive:

```text
Contract hardening applies to existing auth and book flows.
```

## Feature 005

Depends on:

```text
001, 004
```

Motive:

```text
The dummy-hash configuration builds on the authentication flow and follows the API contract
hardening slice.
```

## Feature 006

Depends on:

```text
000, 001, 002, 003, 004, 005
```

Motive:

```text
Documentation must describe the final, verified behavior and commands.
```

---

# Objectives by version

## Version 1.0

Objective:

```text
First functional assessment deliverable: authentication, book CRUD, security, tests, and
documentation.
```

Includes:

* Feature 000.
* Feature 001.
* Feature 002.
* Feature 003.
* Feature 004.
* Feature 005.
* Feature 006.

## Version 2.0

Objective:

```text
Future evolution, only if the product scope expands.
```

Includes:

* Selected ideas from the backlog, each with its own approved specification.
* A possible database/infrastructure review if users or data grow.

---

# Discarded changes

## Feature

Public registration for version 1.0.

Reason for discarding:

```text
The brief only asks for login/logout. Registration remains a backlog idea and requires explicit
approval.
```

Date:

```text
2026-10-26
```

## Feature

Docker, CI/CD, and OpenAPI as required version 1.0 deliverables.

Reason for discarding:

```text
They are not requested and would add scope disproportionate to the assessment. They remain
optional enhancements.
```

Date:

```text
2026-10-26
```

---

# Rules for AI agents

Before proposing or developing a new feature:

The agent must:

1. Review this roadmap.
2. Validate whether the feature already exists.
3. Review dependencies.
4. Confirm priority.
5. Create the corresponding specification.

The agent must not:

* implement backlog items automatically;
* skip higher-priority features;
* create features not aligned with the roadmap.

---

# Roadmap updates

After completing a feature:

Update:

* status;
* description;
* references;
* dependencies.

When a feature becomes complete:

Move it from:

```text
In development
```

to:

```text
Completed
```

---

# Change history

| Date | Change | Reason |
| ---------- | ------------------ | ------- |
| 2026-10-26 | Initial roadmap created with features 000–005. | Constitution stage for the Book Registry API assessment. |
| 2026-10-26 | `000-setup-base` spec and plan approved; task list created. | First thin slice moved to the implementation gate. |
| 2026-10-26 | TASK-001 completed: Laravel scaffolded and local environment configured. | Runnable API-only baseline established. |
| 2026-10-26 | TASK-002 completed: frontend scaffolding removed. | API-only repository with no unused Node/Vite tooling. |
| 2026-10-26 | TASK-003 completed: health endpoint registered. | First public, versioned API endpoint available for verification. |
| 2026-10-26 | TASK-004 completed: health contract covered by a feature test. | Prevents regressions in the public health response. |
| 2026-10-26 | TASK-005 completed: migrations applied and database verified empty. | Local database ready without demo data. |
| 2026-10-26 | `000-setup-base` completed: setup, health check, tests, and documentation delivered. | First feature closed; authentication is next. |
| 2026-10-26 | `001-auth-minimum` spec created; status proposed. | Authentication walking skeleton prepared for approval. |
| 2026-10-26 | `001-auth-minimum` spec approved; technical plan created. | Authentication walking skeleton moved to the plan gate. |
| 2026-10-26 | `001-auth-minimum` plan approved; task list created. | Authentication walking skeleton moved to the implementation gate. |
| 2026-10-26 | `001-auth-minimum` TASK-001 completed: Sanctum and token storage installed. | Authentication implementation started. |
| 2026-10-26 | `001-auth-minimum` TASK-002 completed: demo user seeded. | Login can be developed against a documented account. |
| 2026-10-26 | `001-auth-minimum` TASK-003 completed: login and throttling implemented. | First authentication flow is available. |
| 2026-10-26 | `001-auth-minimum` TASK-004 completed: profile endpoint added. | Authenticated identity is exposed to clients. |
| 2026-10-26 | `001-auth-minimum` TASK-005 completed: logout implemented. | Clients can revoke the current session. |
| 2026-10-26 | `001-auth-minimum` TASK-006 completed: security edge cases covered. | Auth contract is protected by regression tests. |
| 2026-10-26 | `001-auth-minimum` completed: login, profile, logout, tests, and docs delivered. | Authentication slice closed; books are next. |
| 2026-10-26 | ADR-008 adopted one service per business controller; AUTH-001 retrofit pending. | Align existing auth code before book implementation. |
| 2026-10-26 | AUTH-001 completed: AuthController delegates to AuthService; service tests added. | Codebase now matches ADR-008. |
| 2026-10-26 | `002-books-create-list-show` spec created; status proposed. | First book slice prepared for approval. |
| 2026-10-26 | `002-books-create-list-show` spec approved; technical plan created. | First book slice moved to the plan gate. |
| 2026-10-26 | `002-books-create-list-show` plan approved; task list created. | First book slice moved to the implementation gate. |
| 2026-10-26 | `002-books-create-list-show` TASK-001 completed: books schema and model added. | Book implementation started. |
| 2026-10-26 | `002-books-create-list-show` TASK-002 completed: BookService and service tests added. | Book business logic is ready for HTTP endpoints. |
| 2026-10-26 | `002-books-create-list-show` TASK-003 completed: book creation endpoint added. | First book API capability is available. |
| 2026-10-26 | `002-books-create-list-show` TASK-004 completed: paginated list added. | Users can consult their own collection. |
| 2026-10-26 | `002-books-create-list-show` TASK-005 completed: book detail added. | Create/list/detail book slice is complete. |
| 2026-10-26 | `002-books-create-list-show` completed: create/list/detail, tests, and README delivered. | Book slice closed; update/delete is next. |
| 2026-10-26 | `003-books-update-delete` spec created; status proposed. | Final book slice prepared for approval. |
| 2026-10-26 | `003-books-update-delete` spec approved; technical plan created. | Final book slice moved to the plan gate. |
| 2026-10-26 | `003-books-update-delete` plan approved; task list created. | Final book slice moved to the implementation gate. |
| 2026-10-26 | `003-books-update-delete` TASK-001 completed: service update added. | Book update logic is ready for HTTP. |
| 2026-10-26 | `003-books-update-delete` TASK-002 completed: service delete added. | Book deletion logic is ready for HTTP. |
| 2026-10-26 | `003-books-update-delete` TASK-003 completed: partial update endpoint added. | Clients can update their own books through the API. |
| 2026-10-26 | `003-books-update-delete` TASK-004 completed: delete endpoint added. | The book CRUD contract is complete; documentation and closure remain. |
| 2026-10-26 | `003-books-update-delete` completed: README, live flow, and final gate passed. | Full book CRUD slice closed; API contract hardening is next. |
| 2026-10-26 | Added `005-auth-dummy-hash-config`; renumbered delivery documentation to `006`. | Queue the dummy-hash configuration after API contract hardening and keep delivery last. |
| 2026-10-26 | `004-api-contract-hardening` spec created; status proposed. | Contract hardening is ready for review. |
| 2026-10-26 | `004-api-contract-hardening` spec approved; technical plan proposed. | Contract hardening moved to the plan gate. |
| 2026-10-26 | `004-api-contract-hardening` plan approved; six-task list proposed. | Contract hardening moved to the implementation gate. |
| 2026-10-26 | `004-api-contract-hardening` TASK-001 completed: known error envelopes added. | Validation, authentication, 404, and 405 now share a tested JSON contract. |
| 2026-10-26 | `004-api-contract-hardening` TASK-002 completed: safe 500 fallback added. | Unexpected failures now return a safe JSON envelope. |
| 2026-10-26 | `004-api-contract-hardening` TASK-003 completed: rate-limit definitions and JSON 429 added. | General and login limits share a consistent throttling contract. |
| 2026-10-26 | `004-api-contract-hardening` TASK-004 completed: general API limit applied. | Public and authenticated traffic now enforce the 60/minute policy. |
| 2026-10-26 | `004-api-contract-hardening` TASK-005 completed: contract regressions covered. | Expired-token, 429, and success compatibility behavior are protected. |
| 2026-10-26 | `004-api-contract-hardening` completed: error envelope, rate limits, tests, and docs delivered. | API contract hardening closed; dummy-hash configuration is next. |
| 2026-10-26 | `004-api-contract-hardening` review remediation: public limiter covers `401` and unknown routes. | Closed the public rate-limit coverage gap. |
| 2026-10-26 | `005-auth-dummy-hash-config` spec created; status proposed. | Dummy-hash configuration is ready for review. |
| 2026-10-26 | `005-auth-dummy-hash-config` spec approved; technical plan proposed. | Dummy-hash configuration moved to the plan gate. |
| 2026-10-26 | `005-auth-dummy-hash-config` plan approved; five-task list proposed. | Dummy-hash configuration moved to the implementation gate. |
| 2026-10-26 | `005-auth-dummy-hash-config` TASK-001 completed: config value and validation added. | The dummy hash now has a configurable source and fail-fast validation. |
| 2026-10-26 | `005-auth-dummy-hash-config` TASK-002 completed: AuthService uses config. | The dummy check now runs for unknown emails and the short-circuit bug is fixed. |
| 2026-10-26 | `005-auth-dummy-hash-config` TASK-003 completed: configuration documented. | Operators can generate, override, and cache the value safely. |
| 2026-10-26 | `005-auth-dummy-hash-config` TASK-004 completed: override/cache verified. | The value survives caching and invalid overrides fail safely. |
| 2026-10-26 | `005-auth-dummy-hash-config` completed: configurable dummy hash delivered. | Authentication configuration hardening closed; delivery documentation is next. |
| 2026-10-26 | `005-auth-dummy-hash-config` review remediation: hasher/cost alignment enforced. | Misaligned overrides now fail safely with regression tests. |
| 2026-10-26 | `006-delivery-documentation` spec approved; technical plan proposed. | Final delivery documentation moved to the plan gate. |
| 2026-10-26 | `006-delivery-documentation` plan approved; five-task list proposed. | Final delivery moved to the implementation gate. |
| 2026-10-26 | `006-delivery-documentation` TASK-001 completed: delivery narrative added. | The README now explains the AI Engineer thesis and delivery method. |
| 2026-10-26 | `006-delivery-documentation` TASK-002 completed: technical delivery sections consolidated. | The README now includes architecture, evidence, tradeoffs, and scaling. |
| 2026-10-26 | `006-delivery-documentation` TASK-003 completed: repository published. | The delivery is available at the configured GitHub remote. |
