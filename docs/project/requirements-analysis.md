# Requirements Analysis — Book Registry API

> Output of prompt `01-analyze-requirements.md`. **It contains no code and no feature specs.**
> It separates **facts** (what the brief says) from **accepted decisions** (what was approved
> to resolve ambiguity). Open questions also live in `requirements.md`.

**Date:** 2026-10-26
**Input:** `docs/project/client-info.md` (lines 27–53)
**Profile:** full

---

## 1. Product Summary

The Book Registry API is a REST API built with Laravel and PHP that lets authenticated users
manage **their own** book registry. A user logs in with a password, logs out, and can create,
view, update, and delete books. Each book has a name (required), an ISBN (digits only), and a
value (decimal). All responses are JSON.

This is not a commercial product: it is a **technical hiring assessment**. The real objective is
to demonstrate engineering judgment — structure, security, validation, data design,
maintainability, and scalability — and to defend every decision in a live review **without AI**.
The brief is intentionally short and expects documented assumptions.

The deliverable is a Git repository containing application code, migrations, tests, and a README
covering setup, assumptions, key decisions, and how the design supports a larger application.

---

## 2. Users and Main Flows

### Users

| User | Real role | Needs |
|---|---|---|
| Authenticated user | Owner of a book registry | Login/logout and CRUD restricted to their own books |
| Technical reviewer | Hiring-process evaluator | Install, run, read, and question the code |

There is no evidence of administrators, read-only users, or public registration.

### Flows

1. **Authentication**
   `credentials → login → authenticated access → operations → logout → access invalidated`.
   Token-based access is the accepted mechanism (D-03).
2. **Create book**
   `authenticated user → valid data → book created and associated with the user`.
3. **Read**
   `user → list/detail → only their own books`. "View" is interpreted as both list and detail.
4. **Update**
   `user → own book → valid data → book updated`. Updates are partial (`PATCH`).
5. **Delete**
   `user → own book → hard delete`. No recovery requirement was specified.
6. **Rejection flow (implicit)**
   No credentials → 401. Missing or foreign book → 404 without leaking data. Invalid data →
   422 in JSON. Excessive login attempts → 429.

---

## 3. Business Rules and Domain Entities

### Explicit rules (facts)

- **BR-01** `name` is required.
- **BR-02** `isbn` accepts digits only.
- **BR-03** `value` is a decimal.
- **BR-04** each user operates only on their own books ("their own book records").
- **BR-05** all responses are JSON.
- **BR-06** login uses a password; logout exists.
- **BR-07** persistence is relational through migrations on MySQL or SQLite.

### Accepted decisions (not explicit in the brief)

Approved by the human on 2026-10-26. These are now project requirements, mapped to BR-08 to
BR-17 and the Technical Requirements section in `requirements.md`.

- **D-01** No public registration; a demo user is created by a seeder/factory.
- **D-02** Login uses email and password.
- **D-03** Authentication uses Sanctum bearer tokens; tokens expire after 7 days (configurable);
  logout revokes only the current token.
- **D-04** SQLite is used for delivery; schema and migrations remain portable to MySQL.
- **D-05** `isbn` is stored as a string, digits only, max length 20, no checksum validation, not
  unique.
- **D-06** `value` is non-negative, `decimal(12,2)`, max 9,999,999,999.99, serialized as a
  string with 2 decimals; no currency field.
- **D-07** `name`, `isbn`, and `value` are all required on creation.
- **D-08** Updates are partial (`PATCH`); omitted fields remain unchanged.
- **D-09** Deletes are hard deletes; success returns a JSON message.
- **D-10** Cross-user access returns 404, not 403.
- **D-11** The list is paginated at 15 items per page, ordered by `created_at` desc, then `id`
  desc; no filters.
- **D-12** Errors use a single JSON envelope `{ "message": "...", "errors": { ... } }` with
  statuses 401, 404, 422, 429, 405, and 500.
- **D-13** The API is versioned at `/api/v1`.
- **D-14** Rate limits: login 5 requests/minute per email+IP; general API 60 requests/minute.
- **D-15** Testing uses PHPUnit feature tests, factories, and `RefreshDatabase`; Laravel Pint
  for style; Larastan optional.
- **D-16** Minimal dependencies; Sanctum is allowed as a first-party package.
- **D-17** No Docker; local setup with PHP/Composer is documented.
- **D-18** No OpenAPI required; the README is the primary API documentation. CI may be added
  if time allows.
- **D-19** Users and books store timestamps; concurrency is last-write-wins.

### Entities

```
User 1 ──── N Book
```

**User**
- `id`
- `name`
- `email` (unique)
- `password` (hash)
- `timestamps`

**Book**
- `id`
- `user_id` — FK to the owner, cascade on delete
- `name` — string, 255, required
- `isbn` — string, 20, digits only (BR-02, D-05)
- `value` — `decimal(12,2)`, non-negative (BR-03, D-06)
- `timestamps`

**Indexes:** `(user_id)` and `(user_id, created_at)`. No unique index on `isbn`.

**Notable absences:** no category, author, publisher, loan, or history entity. They must not be
invented.

---

## 4. Non-Functional Requirements

### Explicit (derived from the brief)

| Area | Requirement | Origin |
|---|---|---|
| Security | Explicitly assessed | Review: "security" |
| Validation | Explicitly assessed | Review: "validation" |
| Database design | Explicitly assessed | Review: "database design" |
| Maintainability | Maintainable, extensible, reusable code | Your Approach |
| Scalability | Support growth in features, users, and data; explain limits | Your Approach |
| Proportionality | Do not over-build for the assessment | Your Approach |
| Explainability | Every part must be explainable without AI | Review |
| Documentation | README with setup, assumptions, decisions, and growth | Submission |
| Testing | Appropriate tests at the developer's discretion | Submission |

### Implicit (engineering decisions aligned with the review criteria)

- **Security**
  - Authentication required on every book route.
  - Ownership authorization on read and write (anti-IDOR), not only on the list.
  - Mass-assignment protection: only validated fields are persisted.
  - Password hashing; passwords and tokens never returned or logged.
  - Login throttling to mitigate brute force (D-14).
  - Secrets through environment variables; `.env.example` without real values.
  - Validation at the boundary (Form Requests); error responses without stack traces.
- **Validation and contract**
  - Consistent 401/404/422/429/405/500 responses, always JSON.
  - `name` max 255, `isbn` max 20, `value` 2 decimals; documented limits.
  - Decimal values serialized as strings to preserve precision (D-06).
- **Performance**
  - Index on `books.user_id` because every query is filtered by owner.
  - Pagination and stable ordering (D-11).
  - No N+1 queries; no premature caching.
- **Scalability and evolution**
  - Stateless token authentication scales horizontally.
  - Portable schema for SQLite → MySQL.
  - Versioned API for non-breaking evolution.
  - Thin controllers with Form Requests, API Resources, and one service per business controller;
    no repository or empty layers.
- **Maintainability**
  - Laravel conventions, clear names, small classes, and no business logic in controllers;
    business rules live in services.
  - Feature tests per flow plus ownership tests; factories and seeders.
  - README with decisions and limits.
- **Observability**
  - Laravel logging without sensitive data or tokens.
  - No monitoring/APM requirement; do not invent one.
- **Compatibility**
  - Environment detected: PHP 8.2.12, Composer 2.5.4, Windows/XAMPP, MySQL not on PATH.
  - Laravel version must be validated against PHP 8.2 during setup (target 12.x).

---

## 5. Initial Risks

| # | Risk | Impact | Mitigation / status |
|---|---|---|---|
| R1 | Brief ambiguity (auth, registration, ISBN, `value`, update, delete) | Rework and wrong assumptions | Resolved through D-01 to D-11; residual risk documented in README |
| R2 | Over-engineering (layers, patterns, packages) | Violates "proportionate"; hard to explain live | Thin controllers + one service per business controller + Form Requests/Resources; no repository; each abstraction justified |
| R3 | Data leakage between users (IDOR) | Direct failure of "security" | Ownership-scoped queries + explicit cross-user tests |
| R4 | Incomplete or inconsistent validation/errors | Fails "validation"; poor API contract | Form Requests + single error envelope + contract tests |
| R5 | ISBN treated as a number | Lost leading zeros / overflow | Stored as string (D-05) |
| R6 | `value` stored as float | Rounding errors | `decimal(12,2)`, cast and serialized as string (D-06) |
| R7 | SQLite vs MySQL divergence | Green locally, broken elsewhere | Portable types; document the selected engine; optional MySQL CI |
| R8 | Live review without AI | Cannot explain generated or complex code | Simple, hand-understood code with behavior-documenting tests |
| R9 | Deadline/effort unknown | Unbalanced or incomplete delivery | Remaining open question Q1; prioritize the core and quality over extras |
| R10 | No registration makes login untestable | Reviewer cannot obtain credentials | Demo user seeded/factory; credentials in README (D-01) |
| R11 | MySQL absent from PATH | Broken setup for the reviewer | SQLite delivery; MySQL setup documented as optional (D-04) |
| R12 | Tests only against SQLite | Does not prove MySQL behavior | Document the difference; optional MySQL test job |
| R13 | Decimal exposed as string may surprise consumers | Contract mismatch | Document the response format and test it (D-06) |
| R14 | Token expiry during a long review | Reviewer must log in again | 7-day configurable expiry; documented (D-03) |
| R15 | Reviewer expects 403 instead of 404 for foreign resources | Perceived mismatch | Document the non-disclosure rationale (D-10) |
| R16 | A service grows into a god service | Poor cohesion and harder tests | One service per business controller; split by subdomain if methods grow; keep HTTP out of services |

---

## 6. Missing Information and Questions

### Resolved decision log (original analysis questions OQ-01 to OQ-18)

| Decision ID | Original question | Decision |
|---|---|---|
| D-01 | OQ-01 — Public registration? | No; seeded demo user |
| D-02 | OQ-02 — Login credential? | Email + password |
| D-03 | OQ-03 — Token or session? | Sanctum bearer token; 7-day expiry; current token revoked on logout |
| D-04 | OQ-04 — MySQL or SQLite? | SQLite delivery; MySQL-portable schema |
| D-05 | OQ-05 — ISBN uniqueness/length? | Not unique; digits only; max 20; no checksum |
| D-06 | OQ-06 — `value` range/precision? | Non-negative; `decimal(12,2)`; 2 decimals; no currency; string output |
| D-07 | OQ-07 — Are all fields required? | Yes, all three on creation |
| D-08 | OQ-07 — PUT or PATCH? | Partial `PATCH` |
| D-09 | OQ-09 — Soft or hard delete? | Hard delete |
| D-10 | Ownership response (interpretation) | 404 for foreign resources |
| D-11 | OQ-08 — Pagination/order/filters? | 15/page; `created_at` desc, `id` desc; no filters |
| D-12 | OQ-10 — Error format? | `{message, errors}` envelope with standard statuses |
| D-13 | OQ-11 — API versioning? | `/api/v1` |
| D-14 | OQ-14 — Rate limiting/password policy? | Login 5/min; API 60/min; no public registration |
| D-15 | OQ-13 — Test framework? | PHPUnit + Pint; Larastan optional |
| D-16 | OQ-15 — Third-party packages? | Minimal; Sanctum allowed |
| D-17 | OQ-16 — Docker? | No; local setup documented |
| D-18 | OQ-17 — OpenAPI? | Not required; README is the primary documentation |
| D-19 | Timestamps/concurrency (additional) | Timestamps; last-write-wins |
| — | OQ-12 — Deadline? | Still open (see Q1 below) |
| — | OQ-18 — Repository visibility? | Still open (see Q2 below) |

### Remaining open questions

| ID | Question | Why it matters | Status |
|---|---|---|---|
| Q1 | Delivery deadline and expected effort? | Calibrates optional hardening and artifacts | Pending |
| Q2 | Repository visibility and delivery method? | Affects delivery and secret management | Pending |

> If unanswered, these remain documented assumptions and do not block design or implementation.

---

## 7. Proposed Features (names, objective, priority only)

Thin vertical slices. None should exceed ~5–8 tasks. The order follows the walking skeleton:
first the thinnest flow that crosses the whole system and works.

| # | Feature | Objective | Priority |
|---|---|---|---|
| 000 | `setup-base` | Laravel installed, SQLite configured, JSON responses, health endpoint, Pint/PHPUnit configured. No business logic. | High |
| 001 | `auth-minimum` | Walking skeleton: seeded demo user, login/logout with Sanctum, protected route, tests. | High |
| 002 | `books-create-list-show` | Create a book, list and show books scoped to the owner; validation and cross-user tests. | High |
| 003 | `books-update-delete` | Partial update and hard delete of own books; validation and ownership tests. | High |
| 004 | `api-contract-hardening` | Error envelope, rate limiting, token expiry, `/api/v1`, contract tests. | Medium |
| 005 | `auth-dummy-hash-config` | Move the unknown-email dummy password hash into configuration, test the wiring, and document hasher/cost alignment and mitigation limits. | Medium |
| 006 | `delivery-documentation` | README with setup, assumptions, decisions, scaling limits, `.env.example`, clean-install verification. | High |

**Future / out of scope:** public registration (unless requested), search/filtering, roles,
soft delete, OpenAPI, Docker, CI/CD, frontend.

> If a public registration feature is later requested, it must be inserted before `001` and
> sized separately; it is not part of the current scope.

---

## 8. Facts vs Accepted Decisions

**Facts from the brief:** Laravel, PHP, MySQL or SQLite, login/logout, CRUD of own books, three
fields and their rules, JSON, migrations, tests, README, Git repository, review criteria, and a
live defense without AI.

**Not in the brief but now accepted:** no public registration, email login, Sanctum tokens,
SQLite delivery, ISBN stored as a non-unique string, `value` as non-negative decimal, all fields
required, partial updates, hard deletes, 404 for foreign resources, pagination, error envelope,
API versioning, rate limits, PHPUnit/Pint, no Docker/OpenAPI, English documentation.

**Still unknown:** deadline/effort and repository visibility. Optional artifacts (OpenAPI,
Docker, CI/CD) are out of scope and only added if time allows.

---

## 9. Next Step

`000-setup-base` and `001-auth-minimum` are implemented and verified. AUTH-001 is complete:
`AuthController` delegates to `AuthService`. `002-books-create-list-show` is implemented and
verified. `003-books-update-delete` is complete and documented. `004-api-contract-hardening` is
complete. `005-auth-dummy-hash-config` is complete. The last feature is
`006-delivery-documentation`; its spec and plan are approved, TASK-001 and TASK-002 are complete,
and TASK-003 through TASK-005 remain. **Continue one task at a time and keep the Definition of Done
green.**
