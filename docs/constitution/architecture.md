# Architecture.md

# Project Architecture

> **Status:** Approved by the human on 2026-10-26.

## Purpose

This document defines the main architectural decisions of the project.

Its goal is to establish how the system is organized, how its components communicate, and which
structural principles must be respected during development.

The architecture is intentionally proportionate to a small API assessment. It is not a generic
enterprise blueprint and it does not introduce patterns that cannot be explained in a live
review.

---

# Architectural principles

## Separation of responsibilities

The system keeps these responsibilities separate:

* **HTTP/presentation:** routes, middleware, controllers, form requests, API resources.
* **Application/business logic:** one service per business controller for use-case flow and coordination.
* **Domain/model:** entities, invariants, relationships, ownership rules.
* **Infrastructure:** database, authentication provider, logging, configuration.
* **Delivery:** tests, seeders, migrations, README, and operational documentation.

Each part has one clear responsibility.

## Low coupling

Modules depend only on what they need. Avoid:

* circular dependencies;
* highly connected modules;
* shared logic without a clear reason;
* cross-module access to internal structures.

## High cohesion

Each module groups related behavior. A change inside a module should affect as few external
components as possible.

## Incremental evolution

The architecture allows progressive growth. Do not design complex solutions for problems that
do not exist yet. Add structure when a concrete need appears, and document the reason.

---

# Architectural style

## Type of architecture

```
Modular monolith, layered by HTTP / application / domain / infrastructure,
organized by business capability (Auth, Books), using Laravel conventions.
```

This is **not** Clean Architecture, Hexagonal Architecture, full DDD, CQRS, event sourcing, or
microservices. Those options would add ceremony disproportionate to a handful of API operations and
would make the live review harder.

The monolith is modular in the sense that `Auth` and `Books` are separate capabilities with
clear boundaries, even though they share the Laravel application and database.

---

# System organization

```
Client (API consumer)
        │
        ▼
routes/api.php  +  middleware (auth:sanctum, throttle, JSON)
        │
        ▼
HTTP layer
  Controllers · Form Requests · API Resources
        │
        ▼
Application layer
  Services · one per business controller
        │
        ▼
Domain / model layer
  Eloquent models · relationships · invariants · policies
        │
        ▼
Infrastructure
  SQLite / MySQL · Sanctum · logging · configuration
```

There is no frontend layer. There is no external integration layer in the current scope.

---

# Main modules

## Module — Auth

### Responsibility

Authenticate a user with email and password, issue an access token, and revoke the current token
on logout. Business logic lives in `AuthService`; `AuthController` only translates HTTP input and
output.

### Must not be responsible for

* Public registration or account creation.
* Roles, permissions, password reset, or email verification.
* Book management.

## Module — Books

### Responsibility

Create, list, view, partially update, and hard-delete books owned by the authenticated user.
Business rules and ownership live in `BookService`; the controller only translates HTTP.

### Must not be responsible for

* Authentication or token issuance.
* User administration.
* Reporting, search, or cross-user views.
* UI concerns.

## Module — API platform (shared)

### Responsibility

Provide the versioned API surface, JSON error handling, response conventions, rate limiting, and
shared HTTP behavior.

### Must not be responsible for

* Business rules for books or authentication.
* Persistence decisions specific to a domain module.
* Cross-module orchestration beyond HTTP concerns.

---

# Communication flow

## Login

```
Client ──POST /api/v1/login──▶ LoginRequest (validation)
                              │
                              ▼
                        AuthController
                              │
                        AuthService
                              │
                     credential check / token issue
                              │
                              ▼
                     UserResource + token
                              │
                              ▼
                        JSON response
```

## Book operation

```
Client ──Bearer token──▶ auth:sanctum middleware
                              │
                              ▼
                        FormRequest (write operations)
                              │
                              ▼
                        BookController
                              │
                        BookService
                              │
            $request->user() → ownership boundary
                              │
                              ▼
                     Eloquent model / query
                              │
                              ▼
                        BookResource
                              │
                              ▼
                        JSON response
```

## Error flow

```
Exception / validation failure
            │
            ▼
Laravel exception handler (JSON for /api/*)
            │
            ▼
{ "message": "...", "errors": { ... } }
```

No stack traces or internal details are exposed in API responses.

---

# Domain management

## Main entities

* **User:** owns many books.
* **Book:** belongs to one user.

## Business rules

* `name`, `isbn`, and `value` are required on creation.
* `name` is a string with a maximum length of 255.
* `isbn` accepts digits only, is stored as a string (max 20), and is not unique.
* `value` is non-negative, has two decimals, and is serialized as a string.
* A user can only operate on their own books.
* Updates are partial (`PATCH`); deletes are hard deletes.

## Where logic lives

* **Validation:** Form Requests at the HTTP boundary.
* **Business logic:** Services, one per business controller. `AuthService` owns login, logout,
  and token operations; `BookService` owns book rules, ownership, and transactions.
* **Invariants and relationships:** Eloquent models and database constraints where appropriate.
* **Ownership:** enforced inside `BookService` using the authenticated user's `books()`
  relationship; controllers do not query or decide ownership.
* **HTTP mapping:** controllers delegate to services and return resources. They do not query the
  database or contain reusable business rules.
* **Persistence:** services use Eloquent directly. There is no repository layer.
* **Exceptions:** `HealthController` is an infrastructure endpoint with no business logic and
  does not need a service. A trivial read with no rules may remain in a controller only when it
  adds no decision or query logic.

Actions are not used. A service method is the default business-logic unit.

---

# Data management

* Eloquent is the only data-access mechanism. Raw SQL is allowed only when justified and must be
  parameterized.
* Every schema change is a migration. Applied migrations are never edited; add a new migration.
* SQLite is the delivery database; the schema and queries must remain portable to MySQL.
* Ownership queries start from the authenticated user's `books()` relationship inside
  `BookService`; foreign IDs yield 404.
* Transactions are used by services only when a use case performs multiple dependent writes.
  Single-row CRUD does not need an artificial transaction.
* Indexes: `books(user_id)` and `books(user_id, created_at)`. No unique index on `isbn`.
* Seeds and factories provide the demo user and test data.

---

# External integrations

There are no external integrations in the current scope.

If one is added later:

* isolate it behind an adapter or dedicated service;
* handle timeouts, retries, and failures explicitly;
* log meaningful events without sensitive data;
* do not leak provider-specific types into the domain;
* document the integration and its contract.

---

# Inter-module communication

* `Auth` establishes the authenticated user context. `Books` consumes it through
  `$request->user()`; there is no direct call from `Books` into `Auth` internals.
* `Books` uses the `User::books()` relationship inside `BookService`; this is the ownership
  boundary.
* Shared HTTP behavior belongs to the API platform module, not to either business module.
* Modules must not reach into each other's internal structures. If a shared contract is needed,
  define it explicitly.

---

# Configuration management

* All environment-specific values live in `.env`; `.env.example` documents required keys without
  real secrets.
* Secrets are never committed or hardcoded.
* Relevant configuration: `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `DB_CONNECTION`, `DB_DATABASE`,
  `SANCTUM_TOKEN_EXPIRATION` (or the configured equivalent), and rate-limit values.
* Configuration is read through `config/*.php`, not directly from `env()` outside config files.
* Local development uses SQLite; tests use an in-memory SQLite database.

---

# Architectural security

* **Authentication:** Sanctum bearer tokens; passwords hashed with Laravel's hasher; tokens
  expire after 7 days (configurable); logout revokes the current token only.
* **Authorization:** per-user ownership. Every book query is scoped through the authenticated
  user. Foreign resources return 404 to avoid enumeration. No roles in the current scope.
* **Data protection:** only validated fields are persisted; passwords and tokens are never
  returned or logged; API errors do not expose stack traces.
* **Rate limiting:** login 5 requests/minute per email+IP; general API 60 requests/minute.
* **Transport:** HTTPS is required in any non-local deployment. CORS is configured only if a
  browser client is added.
* **Audit:** no audit trail is in scope. Add one only with an approved feature.

---

# Scalability

The design scales first by staying stateless:

* Bearer tokens allow horizontal scaling behind a load balancer.
* No server-side session affinity is required.
* Pagination prevents unbounded list responses.
* Indexes support owner-scoped queries.

Known limits and growth path:

* **SQLite** has a single-writer model and file-level locking. Move to MySQL or PostgreSQL when
  concurrent writes grow.
* **Offset pagination** degrades at very large offsets; cursor pagination is the next step.
* **Sanctum** validates the token against the database per request; cache or a different token
  strategy can be introduced later if needed.
* **Caching, queues, and search** are not implemented. Add them only when the workload requires
  them.
* **Multi-tenancy** is not implemented. Organizations would require a tenant boundary and
  policies, not just a `user_id`.
* **API evolution** is handled through `/api/v1`; breaking changes require a new version or
  explicit approval.

No complexity is added now for hypothetical scale.

---

# Architectural decisions (ADR)

## ADR-001

### Decision

Use a modular monolith with layered HTTP, application services, model, and infrastructure
responsibilities, organized by capability (`Auth`, `Books`).

### Context

The project is a small API with two business capabilities. It must be maintainable, testable,
and explainable in a live review.

### Alternatives considered

* Clean/Hexagonal Architecture.
* Full DDD with repositories and domain services.
* Microservices.
* Serverless functions.

### Motive

Those alternatives add layers and operational complexity disproportionate to a handful of API operations.
Laravel conventions already provide a clear structure that a reviewer can follow.

### Consequence

The codebase stays small and readable. If a module grows, it can be extracted or given a
dedicated Service without changing the overall style.

## ADR-002

### Decision

Use Laravel Sanctum bearer tokens for API authentication.

### Context

The API needs password login, logout, and stateless access from an API client.

### Alternatives considered

* Laravel session cookies.
* JWT.
* Laravel Passport/OAuth2.

### Motive

Sanctum is first-party, simple, well understood, and designed for API tokens. JWT would add
signing/key complexity; Passport is OAuth2 overkill; session cookies are less natural for an API.

### Consequence

Tokens are stored in `personal_access_tokens`, expire after 7 days, and are revoked on logout.
Token validation requires a database lookup per request.

## ADR-003

### Decision

Deliver on SQLite with a schema and queries portable to MySQL.

### Context

The brief allows MySQL or SQLite. The reviewer must be able to run the project with minimal
setup.

### Alternatives considered

* MySQL only.
* PostgreSQL.
* SQLite only, with no portability consideration.

### Motive

SQLite removes setup friction; portability keeps the door open for production growth. MySQL is
the brief's other allowed option and matches the developer's background.

### Consequence

SQLite is used locally and in tests. Avoid SQLite-only features; verify types, foreign keys, and
indexes remain valid in MySQL.

## ADR-004

**Status:** Superseded by ADR-008.

### Decision

**Historical decision (superseded by ADR-008):** Do not introduce a repository layer or a service
layer by default.

### Context

The domain has two entities and simple CRUD. The assessment values proportionality and
explainability.

### Alternatives considered

* Repository pattern over Eloquent.
* Service layer for every use case.
* Full DDD with domain services.

### Motive

Eloquent is already the persistence abstraction. Extra layers would add indirection without a
concrete benefit and would be harder to justify in the live review.

### Consequence

> **Historical:** This consequence is superseded by ADR-008. Do not apply it.

Controllers orchestrate simple flows. Extract an Action only when logic becomes multi-step,
reused, or complex enough to test separately. Never add an abstraction "for the future".

## ADR-005

### Decision

Enforce ownership through scoped queries and return 404 for foreign resources.

### Context

Users must not read or modify other users' books. The API must not reveal whether a foreign
record exists.

### Alternatives considered

* Global scopes on the `Book` model.
* Policies returning 403.
* Route model binding with a policy.

### Motive

Explicit scoped queries are easy to read, easy to test, and avoid the hidden behavior of global
scopes. Returning 404 avoids resource enumeration.

### Consequence

Every book query starts from `$request->user()->books()`. Tests must cover list, view, update,
and delete across users. If policies are introduced later, they must preserve the 404 behavior.

## ADR-006

### Decision

Version the API at `/api/v1`.

### Context

The API will evolve. Consumers need a stable contract.

### Alternatives considered

* Unversioned routes.
* Header-based versioning.

### Motive

A path prefix is explicit, simple, and cheap. It demonstrates an evolution strategy without
adding infrastructure.

### Consequence

All endpoints live under `/api/v1`. Breaking changes require a new version or explicit approval.

## ADR-007

### Decision

Serialize `value` as a string with two decimals.

### Context

`value` is a decimal amount. JSON numbers can lose precision, and PHP float handling is
error-prone for money-like values.

### Alternatives considered

* JSON number (float).
* Integer cents.
* String with two decimals.

### Motive

Laravel's `decimal:2` cast returns a string and preserves precision. A string is explicit in the
contract and avoids float surprises.

### Consequence

The API returns `"12.50"`, not `12.5` or `12.50` as a number. Tests and README must document this
contract.

---


## ADR-008

**Status:** Accepted. Supersedes ADR-004.

### Decision

Adopt one service per business controller as the application/business-logic layer.
`AuthService` owns authentication and token operations; `BookService` owns book rules,
ownership, pagination, and transactions. Controllers handle HTTP orchestration, Form Requests
handle validation, API Resources shape JSON, and services use Eloquent directly. Services must
not return HTTP responses or accept HTTP `Request` objects. No repository, interfaces, DTOs, or
Actions are introduced by default. `HealthController` remains an infrastructure endpoint without
a service.

### Context

`001-auth-minimum` placed login, credential checks, and token revocation in `AuthController`.
The next feature adds book ownership and CRUD, where business rules, ownership, and pagination
need one consistent home. The developer prefers a service layer per controller, and the project
must remain testable and explainable without adding repository ceremony.

### Alternatives considered

* Keep all logic in controllers.
* Use Action classes for each use case.
* Full DDD with repositories and domain services.
* One service per endpoint instead of per controller.

### Motive

Services separate HTTP concerns from business logic, make auth and ownership directly testable,
and give later features a consistent place for transactions and rules. One service per controller
keeps the number of classes proportionate and avoids a repository that would only wrap Eloquent.

### Consequence

Controllers become thin; business logic gets service-level tests in addition to feature tests.
`AuthController` was retrofitted to `AuthService` without an API change (AUTH-001). Services must stay
cohesive: split by subdomain if a controller grows, never create one service per endpoint. The
existing `HealthController` is an explicit infrastructure exception.

# Architectural constraints

* No microservices, CQRS, event sourcing, or full DDD.
* No repository layer. Business logic lives in Services, one per business controller.
* No global scopes or hidden query filters.
* No frontend in this scope.
* No direct database access from routes, views, or controllers; services and models own queries.
* No business rules duplicated across controllers, services, and requests.
* No Actions as a parallel business-logic pattern.
* No unversioned breaking API changes.
* No SQLite-only schema features that break MySQL portability.
* No direct cross-module access to internal structures.
* No new architectural layer or pattern without an ADR and human approval.

---

# Architecture evolution

The architecture may change when:

* the product grows (registration, roles, multi-tenancy);
* new requirements appear;
* a technical problem demands a different structure;
* the database or deployment model changes.

Every important evolution must:

* be documented as an ADR;
* justify the motive and alternatives;
* evaluate impact on existing features and tests;
* be approved before implementation.

---

# Instructions for AI agents

Before creating a new feature, the agent must:

1. Review this architecture.
2. Identify where the change belongs.
3. Preserve existing responsibilities.
4. Avoid creating parallel structures.
5. Document important decisions.

If a proposed solution requires changing the architecture, the agent must explain:

* motive;
* impact;
* alternatives;
* risks.

The agent must not change the architecture silently.
