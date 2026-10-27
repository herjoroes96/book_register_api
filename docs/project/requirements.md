# Requirements — Structured Requirements

> **What this file is:** the ordered definition of the project. **AI builds it** from
> `client-info.md` (prompt `01-analyze-requirements.md`) and **you validate it** before
> continuing. It is the OUTPUT of the analysis, not the raw input.
>
> **Priority:** this document sits at level 1 of the decision hierarchy (see `AGENTS.md`).
> It wins over the constitution and any preference.

---

## Project Information

**Name:** test_backend_sas — Book Registry API

**Client / Organization:** Not disclosed. This is a technical assessment for a Backend
Developer position, not an operating client with production data.

**AIPF profile:** full

**General description:**
REST API built with Laravel/PHP that lets authenticated users manage their own book registry.
Each user can log in, log out, and create, view, update, and delete their own books. The
deliverable is assessed on structure, functionality, security, validation, database design,
maintainability, and scalability.

---

## Business Context

**Industry / Sector:** Not applicable (sample domain: personal book registry). The real context
is a technical hiring assessment.

**Current situation:**
No application exists. The repository starts empty and must be built from scratch, including
migrations, tests, and a README. The brief is intentionally short and leaves decisions to the
developer.

**Identified problems:**
- The brief is deliberately incomplete: it expects documented assumptions and reasonable decisions.
- The solution must be proportionate to the exercise while remaining ready to grow in features,
  users, and data.
- Part of the evaluation is a live review without AI assistance, where the author must explain
  the whole codebase and complete a small change or debugging task. Code must be understandable
  and defensible, not merely functional.

---

## Objectives

**Primary objective:** deliver a working Book Registry API in Laravel that meets the brief and
demonstrates senior engineering quality: clear architecture, security, validation, solid data
design, tests, and documentation.

**Secondary objectives:**
- Implement authentication (login/logout) and book CRUD isolated per user.
- Deliver migrations, tests, and a README covering setup, assumptions, key decisions, and how
  the approach supports a larger application.
- Be able to explain every part of the code during the live review.
- Keep the implementation proportionate and free of unnecessary abstraction.

---

## Users

### Primary user
- **Name / role:** Authenticated user (book owner).
- **Description:** A person with valid credentials who manages their own book registry.
- **Needs:** log in/out; create, view, update, and delete their own books without seeing or
  modifying other users' records.
- **Main actions:** login, logout, create book, list/show books, update book, delete book.

### Secondary users
- **Name / role:** Technical reviewer/evaluator.
- **Description / needs / actions:** Not an end user of the API. Installs and runs the project,
  reviews code, tests, migrations, and README, and performs a live review. Requires the system
  to be installable, understandable, and explainable.

---

## Functional Scope

### In scope (explicit)

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | A user can log in with a password. | High |
| FR-02 | A user can log out. | High |
| FR-03 | An authenticated user can create a book owned by them. | High |
| FR-04 | An authenticated user can list their own books. | High |
| FR-05 | An authenticated user can view the details of one of their own books. | High |
| FR-06 | An authenticated user can update one of their own books. | High |
| FR-07 | An authenticated user can delete one of their own books. | High |
| FR-08 | Every book includes a name, an ISBN, and a value. | High |
| FR-09 | All API responses are JSON. | High |
| FR-10 | The delivery includes application code, migrations, tests, README, and a Git repository. | High |

### Out of scope (not requested)

- Public user registration (sign-up).
- Roles, permissions, administration, reporting, notifications.
- Frontend/UI.
- External integrations.
- Search/filtering, soft deletes, audit trails.
- OpenAPI/Swagger, Docker, CI/CD (optional enhancements if time allows, not requirements).
- Multi-tenancy beyond per-user ownership.

---

## Business Processes

### Authentication
- **Name:** Login / logout.
- **Description:** The user submits credentials and receives access to the API; they can then
  log out and invalidate that access.
- **Current flow:** Does not exist.
- **Expected flow:** `POST /api/v1/login` with valid credentials grants authenticated access;
  `POST /api/v1/logout` invalidates the current access.
- **Known rules:** password-based authentication; token-based access is the accepted mechanism
  (see Accepted Decisions).

### Book management
- **Name:** Book CRUD.
- **Description:** An authenticated user manages only their own books.
- **Current flow:** Does not exist.
- **Expected flow:** create → list/view → update → delete, always scoped to the owner.
- **Known rules:** name required, ISBN digits only, value decimal, JSON responses.

---

## Business Rules

### Explicit (from `client-info.md`)

| ID | Rule |
|---|---|
| BR-01 | A book must have a name; `name` is required. |
| BR-02 | `isbn` accepts digits only (no hyphens or letters). |
| BR-03 | `value` is a decimal value. |
| BR-04 | A user can only create, view, update, and delete **their own** books. |
| BR-05 | All API responses must be JSON. |
| BR-06 | Access requires prior password authentication; logout is available. |
| BR-07 | Data is persisted through migrations on MySQL or SQLite. |

### Accepted assumptions and decisions (approved by the human on 2026-10-26)

These rules are **not explicit** in the brief. They are deliberate decisions needed to build a
consistent solution and are part of the project requirements from this point on.

| ID | Rule | Rationale |
|---|---|---|
| BR-08 | `name`, `isbn`, and `value` are all required on creation. | The brief says each book "must include" all three; only Name is labeled Required, which is ambiguous. |
| BR-09 | `isbn` is stored as a string, accepts digits only, has a maximum length of 20, is not checksum-validated, and is not unique. | Preserves leading zeros; the brief asks for digits only, not ISBN-10/13 validation or uniqueness. |
| BR-10 | `value` is non-negative, has at most 2 decimal places, maximum 9,999,999,999.99, and is serialized as a string with 2 decimals. No currency field is added. | Preserves decimal precision; currency was not specified. |
| BR-11 | There is no public registration. A demo user is created through a seeder/factory. | The brief only asks for login/logout; keeps scope and attack surface small. |
| BR-12 | Login uses email and password. | Laravel convention; clear user schema. |
| BR-13 | Updates are partial (`PATCH`): omitted fields remain unchanged. | More realistic and flexible than forcing full replacement. |
| BR-14 | Deletes are hard deletes; success returns a JSON message. | No recovery requirement was specified; JSON keeps the response contract consistent. |
| BR-15 | Cross-user access returns 404, not 403. | Avoids revealing the existence of other users' records. |
| BR-16 | The list is paginated at 15 items per page, ordered by `created_at` desc, then `id` desc. | Prevents unbounded responses and unstable ordering. |
| BR-17 | Timestamps are stored on users and books. Concurrency is last-write-wins. | Minimal auditability and a documented concurrency behavior. |

---

## Data / Entities

### User
- **Name:** User.
- **Main data:** `id`, `name`, `email` (unique), `password` (hash), `timestamps`.
- **Relationships:** one user has many books.

### Book
- **Name:** Book.
- **Main data:** `id`, `user_id` (FK, cascade on delete), `name` (string, 255), `isbn`
  (string, 20), `value` (`decimal(12,2)`), `timestamps`.
- **Relationships:** each book belongs to one user.
- **Indexes:** `(user_id)` and `(user_id, created_at)`. No unique index on `isbn`.

```
User 1 ──── N Book
```

---

## Integrations

None. The brief does not mention external services, payments, email, or third parties.

---

## Technical Requirements

- **Mandatory technology:** Laravel + PHP.
- **Framework version:** latest stable Laravel compatible with the available PHP; target
  Laravel 12.x with PHP 8.2.12, validated during setup.
- **Database:** SQLite for the delivered application, with a schema and migrations that remain
  portable to MySQL.
- **Authentication:** Laravel Sanctum bearer tokens. Tokens expire after 7 days (configurable)
  and logout revokes only the current token.
- **API:** REST, JSON only, versioned at `/api/v1`.
- **Error contract:** single JSON envelope `{ "message": "...", "errors": { ... } }` for
  validation; statuses 401, 404, 422, 429, 405, and 500.
- **Rate limiting:** login 5 requests/minute per email+IP; general API 60 requests/minute.
- **Testing:** PHPUnit feature tests by flow, factories, and `RefreshDatabase`. Pest is not used.
- **Code quality:** Laravel Pint. Larastan is optional if time allows.
- **Delivery:** Git repository with code, migrations, seeders, tests, `.env.example`, and a
  README covering setup, assumptions, key decisions, and scalability.
- **Local environment:** PHP 8.2.12, Composer 2.5.4, Windows/XAMPP; MySQL is not on PATH.
- **Language convention:** all project documentation and code are written in English, except
  the raw client input preserved verbatim in `client-info.md`. Working discussions may be held
  in Spanish; decisions are documented in English.

---

## Constraints

- The brief is intentionally short; assumptions must be documented instead of silently
  inventing requirements.
- The implementation must be **proportionate** to the assessment (avoid over-engineering).
- It must remain maintainable, extensible, and reusable, and support future growth in features,
  users, and data.
- The live review is **without AI assistance**: the author must understand and explain every
  line and complete a small change/debugging task.
- No budget, deadline, or legislation is specified.
- The reviewer assesses structure, functionality, security, validation, database design,
  maintainability, and scalability.

---

## Priorities

- **High (indispensable):**
  - Login/logout.
  - CRUD of own books.
  - Name required, ISBN digits only, value decimal.
  - JSON responses.
  - Migrations, tests, README, Git repository.
  - Ownership isolation.
- **Medium (quality expected by the review, not detailed in the brief):**
  - Consistent JSON error handling.
  - Ownership/IDOR tests.
  - Pagination and stable ordering.
  - Login throttling.
  - API versioning.
  - Rate limiting.
- **Low (future ideas, out of scope):**
  - Public registration.
  - Search/filtering by name or ISBN.
  - Roles/administration.
  - Soft deletes and audit trails.
  - OpenAPI/Swagger, Docker, CI/CD.
  - Frontend.

---

## Traceability

> Where each requirement came from. Links to the `client-info.md` block that originated it.
> It justifies decisions and helps detect when a requirement changes at the source.

| Requirement / rule | Origin in `client-info.md` |
|---|---|
| Technical hiring assessment | `Contenido crudo` (raw content), line 27 |
| Book Registry API | `Fuentes / referencias` (sources/references) › Objective (lines 31–33) |
| Laravel + PHP | Objective (line 33) |
| MySQL or SQLite | Objective (line 33) |
| Login with password and logout | Objective (lines 34–35) |
| CRUD of own books | Objective (lines 35–36) |
| `name` required | Objective, Name field (line 37) |
| `isbn` digits only | Objective, ISBN field (line 38) |
| `value` decimal | Objective, Value field (line 39) |
| All responses JSON | Objective (line 39) |
| Identify assumptions and decide | Your Approach (lines 42–43) |
| Suitable for a larger application / growth | Your Approach (line 43) |
| Maintainable, extensible, reusable, proportionate | Your Approach (lines 43–44) |
| Explain decisions and scaling limits | Your Approach (line 44) |
| Dedicated Git repository | Submission (line 46) |
| Code and migrations | Submission (line 47) |
| Appropriate tests | Submission (line 48) |
| README: setup, assumptions, decisions, growth | Submission (line 49) |
| Criteria: structure, functionality, security, validation, DB, maintainability, scalability | Review (line 51) |
| Live review without AI; explain all code | Review (lines 52–53) |
| Accepted assumptions and technical decisions | Human approval, 2026-10-26 (not in `client-info.md`) |

---

## Remaining Open Questions

These do not block the design and will remain documented assumptions if no answer is provided.

### Q1 — Delivery deadline and expected effort
- **Detail:** Is there a deadline or an expected number of hours?
- **Why it matters:** It calibrates how much hardening and which optional artifacts to include.
- **Status:** Pending
- **Answer:**

### Q2 — Repository visibility and delivery method
- **Detail:** Should the Git repository be public, private, or shared by link?
- **Why it matters:** It affects delivery and secret management.
- **Status:** Pending
- **Answer:**

---

## Validation Checklist

- [x] The project objective is clear
- [x] Users are identified
- [x] Scope is delimited
- [x] Main business rules are captured
- [x] Constraints are recorded
- [x] Critical questions are answered or converted into accepted decisions
- [x] Reviewed and approved by the human (2026-10-26)
