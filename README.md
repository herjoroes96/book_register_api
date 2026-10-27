# Book Registry API

A Laravel 12 API with authenticated, owner-scoped book CRUD, a consistent JSON contract, rate
limits, and a delivery process built around explicit specifications and human review.

**Repository:** [github.com/herjoroes96/book_register_api](https://github.com/herjoroes96/book_register_api)  
**Author:** [linkedin.com/in/herjoroes](https://www.linkedin.com/in/herjoroes/)

## Why I built this

I built this project for a backend technical assessment, but I treated it as a larger question:
**can a single engineer use an AI-first, spec-driven workflow to deliver the quality and
accountability expected from a senior engineer?**

The goal was not to ask an AI for a CRUD application and accept whatever came back. The goal was to
keep the engineering judgment human:

* define the problem and the boundaries;
* choose the architecture and the security model;
* challenge generated code and reject shortcuts;
* prove behavior with automated tests and live HTTP flows;
* review the result as if it had to be maintained by someone else;
* be able to explain every important decision without AI assistance.

This repository is the evidence of that process, not just the output of it.

## The AI Engineer thesis

I believe the next engineering profile companies need is not only a full-stack developer who writes
more code, but an **AI Engineer**: someone who can turn ambiguity into specifications, design
systems, direct AI execution, verify the result, and own the outcome in front of a reviewer,
customer, or team.

AI changes the economics of implementation. It does not remove engineering responsibility. The
human still owns:

* **Problem framing:** what is being built, for whom, and why.
* **Architecture:** boundaries, data ownership, failure modes, and tradeoffs.
* **Security:** authentication, authorization, secret handling, and attack-surface decisions.
* **Quality:** tests, reviews, error contracts, and release gates.
* **Accountability:** the ability to defend the implementation line by line.

The implementation is faster because AI participates. The delivery is trustworthy because the
human remains accountable.

## AIPF and the control model

The process behind this delivery follows **AIPF**, a private framework I created to orchestrate
AI-assisted software work. It is not an attempt to invent spec-driven development; it applies
established practices such as specifications, architecture decision records, automated tests, code
review, explicit constraints, and quality gates.

The flow keeps the developer in control:

```text
requirements → specification → technical plan → small tasks → implementation → review → closure
```

Each phase has a human approval gate. No application code starts before the specification, plan, and
task list are approved. Scope, assumptions, risks, and known issues are written down instead of kept
in chat. The result is a visible trail from the original brief to the running API.

### What is public and what is private

The public repository contains:

* `AGENTS.md`, the behavior contract used during the project;
* `.opencode/`, the agent command configuration;
* `docs/constitution/`, the permanent project rules;
* `docs/features/`, the real specification → plan → task → review trail;
* `docs/memory/`, the decisions, learnings, and known issues.

The **`aipf/` kit itself is private and is not included in this repository**. References to
`aipf/` in historical documents point to that private kit; their absence is intentional, not a
missing public dependency.

For access to AIPF, or to discuss the methodology, contact me on
[LinkedIn](https://www.linkedin.com/in/herjoroes/).

## The 10% conjecture

I do not have a controlled benchmark for this project, so I will not present a productivity number
as a measured fact.

My working conjecture, based on the speed of this delivery, is that a senior-led AI workflow can
approach **10% of a conventional implementation timeline** while keeping tests, review, security,
and documentation at a professional standard.

That is a hypothesis, not a guarantee. A credible benchmark would need multiple engineers,
equivalent scopes, tracked time, independent review, and a controlled comparison. What this
repository offers instead is transparent evidence: the specifications, decisions, tests, review
findings, remediations, and a runnable system that a reviewer can inspect directly.

## Review this in 10 minutes

1. **Install and run the tests.**
   ```bash
   composer setup
   php artisan test
   php artisan serve
   ```
2. **Check the health endpoint:** `GET /api/v1/health`.
3. **Log in with the demo credentials** in the next section.
4. **Exercise the book flow:** create → list → view → update → delete.
5. **Inspect the process trail:** start with `docs/features/001-auth-minimum/`, then
   `docs/features/004-api-contract-hardening/` and `docs/features/005-auth-dummy-hash-config/`.
6. **Read the review history:** `docs/memory/known-issues.md` and `docs/memory/learnings.md` show
   what was challenged, fixed, and learned.
7. **Open the API section** below for the exact requests, error envelopes, validation rules, and
   rate limits.

The rest of this README gives the technical detail needed to reproduce and evaluate the delivery.

## Current scope

The API feature set is complete through `005-auth-dummy-hash-config`. The repository also contains
the public delivery trail: specifications, plans, tasks, reviews, ADRs, known issues, and learnings.

Implemented:

* Laravel 12 application with SQLite.
* Public, versioned health check at `GET /api/v1/health`.
* Seeded demo user with documented test credentials.
* Login at `POST /api/v1/login` with Sanctum bearer tokens.
* Authenticated profile at `GET /api/v1/me`.
* Logout at `POST /api/v1/logout` with current-token revocation.
* Seven-day token expiration and login throttling.
* Book creation, owner-scoped listing, owner-scoped detail, partial update, and hard delete.
* Validation for name, ISBN, and value on create and update.
* Empty updates rejected; omitted fields remain unchanged.
* Fixed pagination at 15 books per page.
* Uniform JSON error envelope for `401`, `404`, `405`, `422`, `429`, and `500`.
* General API rate limits: 60/minute per authenticated user or public IP, plus login at 5/minute.
* Rate-limit headers and safe server errors without stack traces.
* Configurable unknown-email dummy hash with a safe bcrypt default.
* PHPUnit coverage for health, authentication, books, and the cross-cutting API contract.
* Pint, test, and build commands.
* Local setup and troubleshooting documentation.

Not implemented yet:

* Public registration, account lifecycle, roles, or cross-user access.
* Soft deletes, restore, recycle-bin behavior, or bulk operations.
* Book search, filtering, custom sorting, or configurable page size.
* Public catalog access to books.
* UI, Docker, or CI/CD.

## Demo credentials

These are test-only credentials, not real user data:

```text
Email:    demo@example.com
Password: Password123!
```

The demo user is created by `php artisan migrate --seed` or `composer setup` only when `APP_ENV`
is `local` or `testing`. The seeder is skipped in other environments. If the local account is
missing, run:

```bash
php artisan db:seed
```

## Stack

| Area | Choice |
| ---- | ------ |
| Language | PHP 8.2.12 |
| Framework | Laravel 12.70.1 |
| Database | SQLite 3.x (schema kept MySQL-portable) |
| Authentication | Laravel Sanctum 4.3 bearer tokens |
| Tests | PHPUnit 11.5 |
| Code style | Laravel Pint |
| Frontend | None; no Node/npm required |

## Requirements

* PHP 8.2 or newer with `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl`, `dom`, `xml`,
  and `tokenizer` extensions.
* Composer 2.5 or newer.
* No MySQL server, Node.js, Docker, or CI/CD tooling is required.

## Architecture and decisions

This is an API-only modular monolith. The request flow is deliberately small and explicit:

```text
HTTP request
    │
    ▼
Force JSON + API rate limit + auth:sanctum
    │
    ▼
Form Request (write validation)
    │
    ▼
Controller (HTTP translation only)
    │
    ▼
Service (business rules, ownership, tokens)
    │
    ▼
Eloquent model / SQLite
    │
    ▼
API Resource (public JSON shape)
```

Key decisions:

* **One service per business controller.** `AuthService` owns login, profile, and logout;
  `BookService` owns creation, listing, lookup, update, deletion, and ownership. Controllers do not
  contain business rules and services never return HTTP responses. See ADR-008.
* **Ownership is enforced in the service.** Book queries start from `$user->books()`. Foreign and
  missing books both return `404`, avoiding resource enumeration.
* **Validation belongs at the HTTP boundary.** Form Requests validate writes; only validated fields
  are passed to services. Ownership fields supplied by clients are ignored.
* **JSON is the only API contract.** A shared exception renderer maps known failures to
  `401/404/405/422/429/500`; unexpected failures return a safe `500` without internal details.
* **Abuse protection is scoped.** Login is limited to 5/minute by normalized email + IP. All
  non-login API traffic is limited to 60/minute by authenticated user or public IP, including
  unauthenticated `401` and unknown-route `404` requests.
* **Tokens are Sanctum bearer tokens.** They expire after seven days and logout revokes only the
  current token.
* **SQLite is the delivery database.** The schema and queries are kept MySQL-portable. There is no
  cache, queue, or repository abstraction for this assessment.

The permanent rules live in `docs/constitution/` and the decision records in
`docs/memory/decisions.md`.

## Setup

### Manual setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan serve
```

On Windows PowerShell, use:

```powershell
Copy-Item .env.example .env
```

`migrate --seed` creates the framework tables, the Sanctum token table, and the demo user in
local/testing environments.

### One-command setup

For a fresh checkout:

```bash
composer setup
php artisan serve
```

`composer setup` copies `.env` if missing, generates an application key, creates the SQLite file,
runs migrations, and seeds the demo user.

### Verify the application

```bash
curl http://127.0.0.1:8000/api/v1/health
```

Expected healthy response:

```json
{"status":"ok"}
```

Status `200` means the application and database connection are available. Status `503` with
`{"status":"unavailable"}` means the application is running but the database check failed.

## Authentication flow

### Login

```bash
curl -sS -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"Password123!"}'
```

Expected shape:

```json
{
  "data": {
    "token": "1|example-token-value",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Demo User",
      "email": "demo@example.com"
    }
  }
}
```

### Profile

Use the returned token:

```bash
curl -sS http://127.0.0.1:8000/api/v1/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

Expected shape:

```json
{
  "data": {
    "id": 1,
    "name": "Demo User",
    "email": "demo@example.com"
  }
}
```

### Logout

```bash
curl -sS -X POST http://127.0.0.1:8000/api/v1/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

Expected response:

```json
{"message":"Logged out."}
```

After logout, the same token is rejected. Other active tokens for the same user remain valid.

### Authentication rules

* Tokens expire after 7 days (`SANCTUM_TOKEN_EXPIRATION=10080`).
* Login is throttled at 5 attempts per minute per email and IP.
* Wrong password and unknown email return the same generic `401` response.
* Missing or malformed login data returns `422`.
* Missing, invalid, revoked, or expired tokens return `401`.
* Throttled requests return `429` with `Retry-After` and rate-limit headers.
* Unknown emails perform one dummy password-hash check as a partial timing mitigation.
* There is no public registration.

## Quality gates

Run these before considering a task complete:

```bash
./vendor/bin/pint --test
php artisan test
php artisan optimize
```

`php artisan optimize` is the build-like check for this API. After running it in local
development, restore the local caches with:

```bash
php artisan optimize:clear
```

Optional checks:

```bash
composer validate --strict
php artisan test --filter=AuthTest
php artisan test --filter=ApiContractTest
php artisan test --filter=HealthCheckTest
```

## Tests

`php artisan test` runs the feature suite against an in-memory SQLite database. The current tests
cover:

* Public health response and JSON content type.
* Login success, token issuance, invalid credentials, and validation.
* AuthService credential checks, token issuance, logout revocation, and profile delegation.
* Profile access with valid, missing, and malformed tokens.
* Logout, revoked-token reuse, and two-token isolation.
* Token expiration, login throttling, missing registration, and secret safety.
* JSON error responses without an explicit `Accept` header, demo-seeder environment guard, and log secrecy.
* Uniform `401/404/405/422/429/500` envelopes, safe 500 responses, and 404/405 coverage.
* Public IP and authenticated-user rate limits, header checks, user isolation, and no mutation on throttled requests.
* Success and pagination compatibility after contract hardening.
* Book creation, validation, leading-zero ISBN, duplicate ISBN, and ownership spoofing.
* Owner-scoped book lists, fixed pagination, ordering, and empty pages.
* Owner-scoped book detail and 404 behavior for foreign/missing books.
* Partial/multi-field updates, empty updates, invalid fields, boundary values, owner spoofing, duplicate/leading-zero ISBN, foreign/missing updates, and validation/404 precedence.
* Owned deletion, absence from list/detail, foreign/missing deletion, and unauthenticated deletion.

Tests do not require an external database or a running server.

## Quality evidence

The final automated suite contains **79 tests and 723 assertions**. It covers:

* authentication, token issuance, expiration, revocation, throttling, and secret safety;
* owner-scoped create/list/detail/update/delete behavior;
* validation, leading-zero ISBN, duplicate ISBN, boundary values, and ownership spoofing;
* JSON envelopes for known errors, safe 500 handling, and successful response compatibility;
* login, public, and authenticated rate limits, prefix headers, and user isolation.

The delivery was also exercised through real HTTP flows:

* login → profile → logout → revoked-token `401`;
* create → list → detail → partial update → delete → `404` on the deleted book;
* validation `422`, missing/foreign `404`, unsupported method `405`, and throttling `429`;
* configuration cache with the default dummy hash and rejection of misaligned overrides.

The feature reviews in `docs/memory/` record the findings that were found and fixed, including the
JSON contract gap, malformed route IDs, the public rate-limit bypass, and the dummy-hash
short-circuit. The project does not claim a controlled timing or productivity benchmark.

## Configuration

* `.env.example` documents required local configuration without real secrets.
* `.env` and `database/database.sqlite` are local files and are ignored by version control.
* `APP_KEY` is generated with `php artisan key:generate` and must never be committed.
* `DB_CONNECTION=sqlite` uses `database/database.sqlite`.
* `SANCTUM_TOKEN_EXPIRATION=10080` sets the 7-day token lifetime in minutes.
* Rate limits are fixed at 5/minute for login and 60/minute for the general API.
* `AUTH_DUMMY_PASSWORD_HASH` optionally overrides the non-secret hash used for unknown-email login.
* Local cache and session use the `file` driver; the queue uses `sync` (ADR-001).
* `APP_DEBUG=true` is for local development only. Use `APP_DEBUG=false` outside local.

### Dummy password hash

Unknown emails still perform one password-hash check so login timing does not directly reveal
whether an account exists. The value is configuration, not a secret or a user credential.

* The built-in default is a bcrypt cost-12 hash, aligned with `BCRYPT_ROUNDS=12`.
* Override it with `AUTH_DUMMY_PASSWORD_HASH` only when the hasher or cost changes.
* Generate a bcrypt replacement with:

```bash
php -r "echo password_hash('dummy', PASSWORD_BCRYPT, ['cost' => 12]), PHP_EOL;"
```

* In `.env`, single-quote the value so its `$` characters are literal.
* Rebuild configuration after changing it:

```bash
php artisan config:clear
php artisan config:cache
```

A malformed or misaligned override (wrong algorithm, cost, or options) fails configuration loading
with a clear error that names the setting. The mitigation is partial; the generic `401`, login
throttling, and rate limits remain the primary protections.

## API

### Health

```text
GET /api/v1/health
```

* Public; no authentication.
* `200` with `{"status":"ok"}` when the application and database are available.
* `503` with `{"status":"unavailable"}` when the database check fails.
* No sensitive configuration, credentials, or exception details are returned.

### Authentication endpoints

```text
POST /api/v1/login     Public; returns a bearer token and public user.
GET  /api/v1/me        Bearer token; returns the public user identity.
POST /api/v1/logout    Bearer token; revokes the current token.
```

API responses are JSON, including validation, authentication, and throttling errors, even when the
client omits `Accept: application/json`.

### Error contract

All errors contain a non-empty `message`; validation errors also contain field-level `errors`.
Error responses never contain a `data` payload or internal exception details.

| Status | Meaning | Additional fields |
| ------ | ------- | ----------------- |
| `401` | Authentication missing, invalid, revoked, or expired | None |
| `404` | Resource or route not found | None |
| `405` | Unsupported HTTP method | None |
| `422` | Validation failed | `errors` |
| `429` | Rate limit exceeded | `Retry-After` and rate-limit headers |
| `500` | Unexpected server failure | None; generic message only |

Validation example:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "isbn": ["The isbn field must contain only digits."]
  }
}
```

Throttled example:

```json
{"message":"Too many requests."}
```

### Rate limits

| Scope | Limit | Key |
| ----- | ----- | --- |
| Login | 5/minute | Normalized email + IP |
| Public API | 60/minute | IP |
| Authenticated API | 60/minute | Authenticated user ID |

All non-login `/api/*` requests are counted, including protected requests without a valid token
and unknown routes. Responses carry `X-RateLimit-Limit` and `X-RateLimit-Remaining`; a `429` also
carries `Retry-After`. A throttled request performs no business mutation and issues no new token.

### Book endpoints

All book endpoints require `Authorization: Bearer <token>` and return JSON.

```text
POST   /api/v1/books          Create a book owned by the authenticated user.
GET    /api/v1/books          List only the authenticated user's books.
GET    /api/v1/books/{book}   View one of the authenticated user's books.
PATCH  /api/v1/books/{book}   Partially update one of the authenticated user's books.
DELETE /api/v1/books/{book}   Permanently delete one of the authenticated user's books.
```

#### Create a book

```bash
curl -sS -X POST http://127.0.0.1:8000/api/v1/books \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"name":"The Pragmatic Programmer","isbn":"9780135957059","value":"49.90"}'
```

Validation rules:

* `name`: required, string, max 255.
* `isbn`: required, digits only, max 20, leading zeros preserved, not unique.
* `value`: required, numeric, non-negative, max 2 decimals, max 9,999,999,999.99.

Successful creation returns `201` with the public book shape. Invalid data returns `422`.

#### List books

```bash
curl -sS "http://127.0.0.1:8000/api/v1/books?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

The list is owner-scoped, fixed at 15 items per page, ordered newest first, and includes
pagination metadata. There is no search or filtering in this scope.

#### View a book

```bash
curl -sS http://127.0.0.1:8000/api/v1/books/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

A book owned by another user is treated as not found (`404`), so the API does not reveal its
existence.

#### Update a book

```bash
curl -sS -X PATCH http://127.0.0.1:8000/api/v1/books/1 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"name":"The Pragmatic Programmer, 2nd Edition","value":"45.00"}'
```

Updates are partial: send only the fields that change (`name`, `isbn`, and/or `value`). Omitted
fields remain unchanged. At least one updatable field is required; an empty payload returns `422`.
The same validation rules as creation apply. `user_id` is ignored, so ownership cannot be changed.
A successful update returns `200` with the public book shape.

Validation runs before the ownership lookup: an invalid payload returns `422` even when the book
is foreign or missing. With a valid payload, a foreign or missing book returns `404`. Neither case
reveals whether the book exists or who owns it.

#### Delete a book

```bash
curl -sS -X DELETE http://127.0.0.1:8000/api/v1/books/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

Deletion is permanent and returns `200` with:

```json
{"message":"Book deleted."}
```

The book disappears from the list and detail endpoints. Foreign or missing books return `404` and
are not modified. Soft deletes and restore are not implemented.

## Repository map and delivery

Top-level delivery surface:

```text
AGENTS.md                     Agent behavior contract used during delivery
.aipf.json                    AIPF project marker
.opencode/                    Agent command configuration
README.md                     This delivery document
LICENSE                       MIT license
composer.json                 Dependencies and setup scripts
app/                          Laravel application
bootstrap/app.php             Routing, middleware, exception rendering
routes/api.php                Versioned API routes
database/                     Migrations, seeders, factory, local SQLite
tests/                        HTTP and service tests
docs/                         Constitution, project, features, memory
aipf/                         Intentionally absent: private author framework
```

The `aipf/` kit is not distributed. `AGENTS.md` and the feature artifacts remain public as evidence
of the process; contact [LinkedIn](https://www.linkedin.com/in/herjoroes/) for AIPF access.

Application structure:

```text
app/
├── Http/
│   ├── Controllers/Api/V1/AuthController.php
│   ├── Controllers/Api/V1/BookController.php
│   ├── Controllers/Api/V1/HealthController.php
│   ├── Controllers/Controller.php
│   ├── Middleware/ForceJsonResponse.php
│   ├── Middleware/ThrottleApiRequests.php
│   ├── Requests/Auth/LoginRequest.php
│   ├── Requests/Books/StoreBookRequest.php
│   ├── Requests/Books/UpdateBookRequest.php
│   ├── Resources/BookResource.php
│   └── Resources/UserResource.php
├── Models/Book.php
├── Models/User.php
├── Services/AuthService.php
├── Services/BookService.php
└── Providers/

bootstrap/app.php
routes/api.php
database/
├── factories/BookFactory.php
├── migrations/
├── seeders/DemoUserSeeder.php
└── database.sqlite          (local, ignored)

tests/
├── Feature/ApiContractTest.php
├── Feature/AuthTest.php
├── Feature/BookTest.php
├── Feature/Configuration/AuthDummyHashConfigTest.php
├── Feature/HealthCheckTest.php
├── Feature/Services/AuthServiceTest.php
├── Feature/Services/BookServiceTest.php
├── Unit/.gitkeep
└── TestCase.php

docs/
├── project/
├── constitution/
├── features/
└── memory/
```

## Assumptions and decisions

* The deliverable is API-only; there is no frontend or design system.
* The delivery database is SQLite, with a schema and queries intended to remain MySQL-portable.
* There is no public registration. A documented demo user is seeded for the assessment.
* Business logic follows ADR-008: one service per business controller. `AuthController` delegates
  to `AuthService`; `BookController` delegates to `BookService`.
* Controllers handle HTTP only; services return data, never HTTP responses. No repository or
  Actions pattern is used. `HealthController` is an infrastructure exception.
* Service tests live in `tests/Feature/Services`; HTTP feature tests remain in `tests/Feature`.
* Sanctum bearer tokens are used for authentication.
* Tokens expire after 7 days; logout revokes only the current token.
* Login is throttled at 5 attempts per minute per email and IP.
* Invalid credentials share one generic response to avoid user enumeration.
* Book ownership is enforced in `BookService`; foreign books return 404.
* Updates are partial and empty updates are rejected.
* Deletes are permanent; no soft-delete or restore behavior exists.
* ISBN values are not unique and leading zeros are preserved.
* Book value is a non-negative decimal with two decimals.
* Roles and administration do not exist yet.
* Local cache and session use files; queues use the sync driver (ADR-001).
* The application is versioned at `/api/v1`.
* Documentation and code are written in English.

Decision records and the full architecture live in:

* `docs/constitution/architecture.md`
* `docs/constitution/tech-stack.md`
* `docs/memory/decisions.md`

## Tradeoffs and limitations

This is a deliberately bounded assessment delivery, not a claim of production completeness.

* **SQLite** keeps setup simple and reproducible; the schema is portable, but MySQL/PostgreSQL
  would be the next step for concurrent production traffic.
* **No public registration.** A documented demo user is seeded locally; account lifecycle and
  roles are out of scope.
* **No soft delete or audit history.** Deleting a book is permanent by design.
* **Fixed pagination** at 15 items avoids unbounded responses; search, filters, and cursor
  pagination are future work.
* **No background jobs or cache** are needed at this size; the current sync/file setup keeps the
  delivery explainable.
* **The dummy-hash timing mitigation is partial.** Generic `401`, login throttling, and API rate
  limits remain the primary protections.
* **No UI, Docker, CI/CD, or OpenAPI.** The contract is documented in this README and exercised by
  PHPUnit and manual HTTP flows.
* **The 10% claim is a conjecture**, not a controlled benchmark. A real study would need multiple
  engineers, equivalent scopes, tracked time, and independent review.

## Scaling path

If this API grew beyond the assessment scope, the next changes would be:

1. Move to MySQL or PostgreSQL with managed migrations and backups.
2. Add an observability layer: structured request logs, error tracking, latency/error metrics, and
   dashboards.
3. Introduce CI to run Pint, PHPUnit, dependency audit, and migration checks on every change.
4. Add background jobs and a real cache/store for cross-instance rate limiting.
5. Add tenant-aware authorization or roles only when the product requires them.
6. Extend pagination/search only with measured query plans and indexes.
7. Keep secrets in a secret manager and rotate them outside the repository.

The current architecture leaves those changes localized rather than requiring a rewrite.

## Troubleshooting

### `APP_KEY` is missing

```bash
php artisan key:generate
```

### The SQLite file is missing

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

### Demo login returns `401`

Confirm the demo user exists:

```bash
php artisan db:seed
```

Use the documented credentials exactly. A `429` response means the login throttle was exceeded;
wait for the one-minute window to pass.

### An API request returns `429`

The request exceeded the end of a rate-limit window. Read `Retry-After` and retry after that many
seconds. Login, public, and authenticated limits are counted independently.

### The application fails while loading `AUTH_DUMMY_PASSWORD_HASH`

The override is not a valid hash for the configured hasher and cost. Generate a replacement with
the command above, keep it single-quoted in `.env`, and rebuild configuration. Do not paste a
placeholder, an empty value, or a hash generated with another algorithm/cost.

### A valid-looking token returns `401`

The token may be expired (7-day lifetime), revoked by logout, or malformed. Log in again to obtain
a new token.

### A book request returns `404`

The book may not exist, belong to another user, or have already been deleted. The API intentionally
treats all cases as not found. Confirm the book ID and that you are using the token of its owner.

### A book request returns `422`

Check the validation rules above: required `name`, digits-only `isbn`, and a non-negative `value`
with at most two decimals. A `PATCH` without `name`, `isbn`, or `value` is rejected because an
update must change at least one field.

### A deleted book cannot be recovered

Deletion is permanent by design. Recreate the book if it was removed by mistake.

### The health check returns `503`

Confirm that `DB_CONNECTION=sqlite` points to an existing, writable
`database/database.sqlite`, then run `php artisan migrate`. Check `storage/logs/laravel.log` for a
safe health failure entry. Do not expose database details in logs or API responses.

### `no such table: cache` or similar infrastructure errors

The local configuration must use file cache/session and sync queue:

```text
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Alternatively, apply the framework migrations with `php artisan migrate`.

### Port already in use

```bash
php artisan serve --port=8001
```

### Build configuration is stale

```bash
php artisan optimize:clear
```

### Composer install or platform checks

```bash
composer install --no-interaction
composer check-platform-reqs
```

Ensure the PHP extensions listed in **Requirements** are enabled.

## Delivery status

The delivery is complete through `006-delivery-documentation`. The public repository contains the
full source, tests, migrations, six-feature documentation trail, and final review material. It was
cloned and verified from a clean checkout, including setup, the full test suite, health, and the
authenticated CRUD flow.

Source: [github.com/herjoroes96/book_register_api](https://github.com/herjoroes96/book_register_api)  
Contact: [linkedin.com/in/herjoroes](https://www.linkedin.com/in/herjoroes/)

AIPF remains private; the public artifacts are the specification, plan, task, review, ADR, memory,
and code trail.
