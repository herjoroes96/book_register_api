# Tech Stack.md

# Technology Stack and Technical Conventions

> **Status:** Approved by the human on 2026-10-26.

## Purpose

This document defines the technologies used by the project, their main versions, associated
tools, and related technical decisions.

Its goal is to prevent arbitrary technology changes and to keep consistency throughout the
development cycle.

Update this document whenever an important technology decision is made.

---

# Technology summary

## Application type

```
REST API backend (JSON only), with no frontend or UI.
```

---

# Main technologies

## Main language

```
PHP
```

Version:

```
8.2.12 (local environment); Composer constraint ^8.2
```

Reason for choosing it:

```
Required by the brief. The local environment already provides PHP 8.2.12 and Composer 2.5.4.
```

---

## Main framework

```
Laravel
```

Version:

```
12.x (target; compatibility with PHP 8.2 must be validated during 000-setup-base)
```

Reason for choosing it:

```
Required by the brief. It provides routing, validation, Eloquent, migrations, Sanctum,
testing, and a conventions-driven structure that is easy to explain in a live review.
```

---

## Runtime / execution environment

```
PHP 8.2 CLI, Composer 2.5.4, and a local web runtime (XAMPP/Apache or php artisan serve)
```

Version:

```
PHP 8.2.12; Composer 2.5.4
```

---

# Frontend

## Framework / library

```
N/A — API-only deliverable; no frontend in scope.
```

Version:

```
N/A
```

---

## Main libraries

| Library | Use | Reason |
| ------- | --- | ------ |
| N/A     | N/A | No frontend in scope. |

---

## Styles and interface

Technology:

```
N/A — API-only.
```

Visual system:

```
N/A. If a UI is added, docs/constitution/ui-ux.md applies.
```

---

# Backend

## Framework

```
Laravel 12.x
```

Version:

```
12.x (resolved by Composer; validate PHP 8.2 compatibility during 000-setup-base)
```

---

## Backend architecture

Defines:

* module organization;
* controllers (HTTP only);
* services (one per business controller);
* form requests and API resources;
* validations and business rules.

Reference:

```
Modular monolith organized by capability (Auth, Books) with Laravel's HTTP layer.
Controllers handle HTTP and delegate to one service per business controller; Form Requests
validate; API Resources shape JSON; services use Eloquent directly and own business rules,
ownership, and transactions. No repository layer and no Actions pattern.
```

API conventions:

* All endpoints live under `/api/v1`.
* All responses are JSON.
* Success: `{ "data": ... }`; paginated: `{ "data": [...], "meta": {...}, "links": {...} }`.
* Errors: `{ "message": "...", "errors": { ... } }`.
* Status codes: 200, 201, 401, 404, 405, 422, 429, and 500. Deletes return 200 with a JSON
  message; 204 is not used because every response must have a JSON body.
* Pagination: 15 items per page, ordered by `created_at` desc, then `id` desc.
* `value` is serialized as a string with two decimals.

---

# Database

## Main engine

```
SQLite 3.x for the delivered application; MySQL 8.x compatible target.
```

Version:

```
SQLite 3.x (bundled with PHP); MySQL 8.0+ if the project is moved to MySQL.
```

---

## ORM / access layer

```
Laravel Eloquent
```

Reason:

```
Laravel's default ORM. It provides relationships, casts, migrations, factories, and a clear
ownership boundary through $user->books(). It is sufficient for this domain, so no repository
layer is added.
```

---

## Important rules

* Every schema change is a migration. Applied migrations are never edited; add a new migration.
* No manual schema changes.
* Do not use SQLite-only features that break MySQL portability.
* `name` is a string(255); `isbn` is a string(20); `value` is `decimal(12,2)`.
* Foreign keys and indexes are declared in migrations.
* `books.user_id` is a foreign key with cascade on delete.
* Indexes: `books(user_id)` and `books(user_id, created_at)`.
* No unique index on `isbn`.
* Users and books use `created_at`/`updated_at` timestamps.
* Concurrency is last-write-wins; no optimistic locking in the current scope.
* Seeds/factories provide the demo user and test data.

---

# Storage

## Files

Technology:

```
N/A — no file uploads or object storage in scope.
```

Use:

```
N/A
```

---

## Cache

Technology:

```
N/A — no application caching in scope.
```

Use:

```
N/A. Laravel's framework cache is not used for business logic. Add caching only with a measured
need.
```

---

# Authentication and authorization

## Authentication method

```
Laravel Sanctum bearer tokens.
```

Details:

* Login uses email and password.
* Tokens expire after 7 days (configurable through environment/config).
* Logout revokes only the current token.
* Passwords are hashed with Laravel's hasher.
* Unknown-email login uses a configurable, non-secret dummy hash; keep it aligned with the hasher
  and cost. This is a partial timing mitigation, not a replacement for generic errors or throttling.
* All book routes require `auth:sanctum`.

---

## Permission management

Define:

```
Per-user ownership; no roles or permissions matrix.
```

Details:

* Every book query starts from `$request->user()->books()`.
* A foreign book returns 404 to avoid resource enumeration.
* No global scopes and no hidden authorization magic.
* Policies may be introduced only if route model binding is added and must preserve the 404
  behavior.

---

# External integrations

List of external services used:

None. The current scope has no external integrations.

No integration template is needed until an integration is approved. When one is added, document
the provider, purpose, communication protocol, failure handling, and retry policy in this
section.

---

# Testing

## Testing framework

```
PHPUnit (as shipped with Laravel 12.x)
```

---

## Types of tests used

* Unit tests for extracted validation rules and value rules.
* Integration/feature tests for HTTP flows: authentication, CRUD, ownership, pagination, and
  error contract.
* No end-to-end browser tests (API-only).
* No performance tests in the current scope.

---

## Rules

* Every business rule and fixed bug gets a test when applicable.
* Ownership isolation must be tested for list, view, update, and delete.
* Use `RefreshDatabase` and factories.
* Tests use an in-memory SQLite database.
* Tests are part of the Definition of Done.
* Do not write tests only to increase coverage; protect behavior.

---

# Code quality

## Linter

```
Laravel Pint
```

---

## Formatter

```
Laravel Pint
```

---

## Main rules

* Code must pass Pint before a task is considered done.
* Application PHP files use explicit parameter, return, and property types where practical, and
  `declare(strict_types=1);`.
* No empty `catch` blocks.
* No dead code or unused imports.
* Larastan is optional if time allows; it is not a required gate.
* `composer validate --strict` is a useful optional check, not part of the required DoD.

---

# Infrastructure

## Hosting

```
Local development only. No production hosting is in scope.
```

---

## Containers

Use Docker:

```
No
```

Configuration:

```
Local PHP/Composer environment (XAMPP on Windows) with SQLite.
```

---

## CI/CD

Tool:

```
None in the current scope. A simple pipeline may be added later if time allows.
```

Process:

```
No pipeline is required. The Definition of Done is run locally:
lint, test, build.
```

---

# Environments

## Development

Description:

```
Local machine, PHP 8.2.12, Composer 2.5.4, SQLite file database, .env from .env.example,
php artisan serve (or XAMPP/Apache).
```

---

## Testing

Description:

```
SQLite in-memory database configured in phpunit.xml, RefreshDatabase, factories and seeders.
```

---

## Production

Description:

```
Not in scope. If a production environment is added, use MySQL 8.x, HTTPS, APP_DEBUG=false,
a real APP_KEY, and run migrations with backup and approval.
```

---

# Project commands

> These are the canonical Laravel commands for the target application. They become mandatory
> once `000-setup-base` installs the project and must be verified in that feature. They are the
> Definition of Done for every feature thereafter.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

---

## Development

```bash
php artisan serve
```

---

## Tests

```bash
php artisan test
```

---

## Lint

```bash
./vendor/bin/pint --test
```

On Windows without a POSIX shell, use `vendor\bin\pint --test`.

---

## Build

```bash
php artisan optimize
```

`php artisan optimize` caches configuration, events, routes, and views. It is the production-like
build check for this API. After running it locally, use `php artisan optimize:clear` to continue
development.

---

## Deploy

```bash
# N/A: local-only delivery.
# If a production environment is added:
# php artisan migrate --force
```

---

# Project structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   ├── Requests/
│   │   ├── Auth/
│   │   └── Books/
│   └── Resources/
├── Models/
├── Policies/            (only if introduced)
├── Services/            (one per business controller)
└── Providers/

routes/api.php
database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
│   └── Services/        (DB-backed service tests)
└── Unit/               (pure rules and value logic)

docs/
├── project/
├── constitution/
├── features/
└── memory/
```

Description:

| Folder | Responsibility |
| ------ | -------------- |
| `app/Http/Controllers/Api/V1` | HTTP orchestration and response mapping for versioned endpoints. |
| `app/Http/Middleware` | HTTP concerns such as forcing JSON responses. |
| `app/Http/Requests` | Validation for login and book writes. |
| `app/Http/Resources` | JSON transformation of users and books. |
| `app/Models` | Eloquent models, relationships, casts, and invariants. |
| `app/Policies` | Optional authorization policies; not required initially. |
| `app/Services` | One business service per controller: business rules, ownership, transactions, and token operations. |
| `routes/api.php` | Versioned API routes. |
| `database/migrations` | Versioned schema changes. |
| `database/factories` | Test data generation. |
| `database/seeders` | Demo user and seed data. |
| `tests/Feature` | HTTP contract tests and DB-backed service tests under `tests/Feature/Services`. |
| `tests/Unit` | Pure rules and value logic. |
| `docs` | Requirements, constitution, features, and memory. |

---

# Technology rules

## Dependencies

Before adding a new dependency:

* justify the need;
* evaluate maintenance and security;
* review existing or first-party alternatives;
* confirm it does not change the architecture without approval.

Avoid adding libraries for simple problems.

---

## Versions

Do not update major versions without:

* reviewing compatibility;
* validating impact;
* documenting the change;
* obtaining approval.

---

## Migrations

Important changes must:

* be documented;
* be reversible where possible;
* be tested before any shared or production environment.

---

# Technology decisions

## DEC-001

### Technology chosen

```
PHP 8.2 + Laravel 12.x
```

### Alternatives considered

```
Symfony, plain PHP, Node.js/NestJS.
```

### Reason

```
Laravel is required by the brief, is already available locally, and provides the routing,
validation, ORM, migrations, token auth, and testing tools needed for the assessment.
```

### Impact

```
The project follows Laravel conventions. The framework version must remain compatible with
PHP 8.2.12 until the environment is upgraded.
```

## DEC-002

### Technology chosen

```
SQLite for delivery, with a MySQL-portable schema.
```

### Alternatives considered

```
MySQL only; PostgreSQL.
```

### Reason

```
SQLite removes setup friction for the reviewer and is explicitly allowed by the brief. A portable
schema keeps the option to move to MySQL as the application grows.
```

### Impact

```
One writer at a time in SQLite. Avoid SQLite-only features and verify MySQL compatibility for
types, foreign keys, and indexes.
```

## DEC-003

### Technology chosen

```
Laravel Sanctum bearer tokens.
```

### Alternatives considered

```
Session cookies; JWT; Laravel Passport/OAuth2.
```

### Reason

```
First-party, simple, stateless, and appropriate for an API. JWT and OAuth2 add complexity that
the project does not need.
```

### Impact

```
A personal_access_tokens table is required. Tokens expire after 7 days and logout revokes the
current token.
```

## DEC-004

### Technology chosen

```
PHPUnit + Laravel Pint.
```

### Alternatives considered

```
Pest; no style tool; static analysis only.
```

### Reason

```
PHPUnit is Laravel's default and is easy to explain. Pint enforces a consistent style with a
single command.
```

### Impact

```
Tests and Pint are part of the Definition of Done. Larastan remains optional.
```

## DEC-005

### Technology chosen

```
API versioning at /api/v1.
```

### Alternatives considered

```
Unversioned routes; header-based versioning.
```

### Reason

```
An explicit path prefix is cheap, clear, and supports future breaking changes.
```

### Impact

```
All endpoints are versioned. Breaking changes require a new version or approval.
```

## DEC-006

### Technology chosen

```
No Docker and no CI/CD pipeline in the current scope.
```

### Alternatives considered

```
Docker Compose; GitHub Actions.
```

### Reason

```
The brief requests a local Laravel application, and the assessment values proportionality.
Docker and CI would add setup and maintenance work without being required.
```

### Impact

```
The README documents a clean local setup. A pipeline can be added later as an optional
enhancement.
```

## DEC-007

### Technology chosen

```
Serialize value as a string with two decimals.
```

### Alternatives considered

```
JSON number (float); integer cents.
```

### Reason

```
Preserves precision and matches Laravel's decimal cast behavior. Avoids float rounding surprises
in a money-like field.
```

### Impact

```
The API contract returns "12.50". Tests and documentation must reflect this.
```

---

## DEC-008

### Technology chosen

```
One service per business controller under app/Services.
```

### Alternatives considered

```
Business logic in controllers; Action classes; full DDD with repositories.
```

### Reason

```
Separates HTTP from business rules, makes authentication and ownership testable, and gives later
features one consistent home for transactions. One service per controller keeps the structure
proportionate; no repository is added over Eloquent.
```

### Impact

```
Controllers become thin; services return models/paginators/arrays, never HTTP responses. Business
logic gets service-level tests in addition to HTTP feature tests. HealthController remains an
infrastructure exception. AuthController delegates to AuthService; service tests live under
tests/Feature/Services.
```

---

# Instructions for AI agents

Before implementing:

The agent must:

1. Review this document.
2. Use the defined technologies.
3. Respect existing versions.
4. Avoid replacing tools without approval.
5. Document any technology change.
6. Keep controllers thin and put business logic in the matching service.

If the current stack is not suitable for a requirement, the agent must explain:

* the problem found;
* alternatives;
* impact;
* recommendation.

Never change the main technology without analysis.
