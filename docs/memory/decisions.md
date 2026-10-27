# Decisions (ADRs)

> Record of important decisions and their *why*. When you ask "why did we do this?" six months
> from now, the answer lives here. The AI must review this file before proposing changes that
> contradict a previous decision, and add an entry when it makes (or the human approves) a
> decision with impact.

---

## ADR-001 · Use file cache/session and sync queue during setup

**Date:** 2026-10-26
**Status:** accepted

**Context:** Laravel 12 defaults to database-backed cache, session, and queue drivers. TASK-001
removes the default web route and defers migrations to TASK-005. With no cache table yet,
`php artisan optimize:clear` failed with `no such table: cache`, even though the build gate had
passed. The project does not use application caching, queues, or database sessions in the current
scope.

**Decision:** Local `.env` and `.env.example` use `CACHE_STORE=file`, `SESSION_DRIVER=file`, and
`QUEUE_CONNECTION=sync`. Tests keep `array` cache/session and `sync` queue. The database remains
the source of truth for domain data.

**Alternatives considered:** Run migrations early in TASK-001; keep the database drivers and
accept an unreliable `optimize:clear`; use `array` drivers in local development.

**Consequences:** The build gate and its cleanup work before migrations. Local operation does not
depend on cache/session/jobs tables. If caching, queues, or database sessions are introduced
later, the drivers must be revisited and documented in a new ADR.

## ADR-002 · Adopt one service per business controller (Architecture ADR-008)

**Date:** 2026-10-26
**Status:** accepted; supersedes the application-layer part of architecture ADR-004

**Context:** The completed auth slice placed credential checks, token issuance, and token revocation in `AuthController`. The next feature adds book ownership and CRUD, where rules, ownership, pagination, and transactions need a consistent home. The project previously avoided a service layer to stay proportionate.

**Decision:** Adopt `app/Services` with one service per business controller. Controllers handle HTTP only; services own business rules, ownership, transactions, and token operations; Form Requests validate; API Resources shape JSON; Eloquent remains the persistence mechanism. Services return models, paginators, or arrays, never HTTP responses. No repository and no Actions pattern are used. `HealthController` remains an infrastructure exception.

**Alternatives considered:** Keep logic in controllers; use Action classes; full DDD with repositories and domain services; one service per endpoint.

**Consequences:** Auth and book behavior become directly testable at the service level, and controllers stay thin. The number of classes grows by one service per business controller, with a risk of god services mitigated by cohesive methods and splitting by subdomain if needed. `AuthController` was retrofitted to `AuthService` without API changes (AUTH-001). See `docs/constitution/architecture.md` ADR-008.
