# Mission.md

# Project Mission

> **Status:** Approved by the human on 2026-10-26.

## Purpose

This document defines why the project exists.

It describes:

* what product is being built;
* what problem it solves;
* who it exists for;
* what objectives it must meet.

This document is the primary reference for deciding whether a feature is aligned with the
product's purpose.

---

# Project Identity

## Project name

`test_backend_sas` — Book Registry API

---

## Short description

REST API built with Laravel/PHP that lets authenticated users keep a private registry of their
own books. Each user can log in, log out, and create, view, update, and delete only their own
books, with a consistent JSON contract.

It delivers value through **secure per-user isolation, reliable validation, and a predictable
API** that another application could consume.

---

# Problem it solves

## Current situation

There is no application. In the product framing, book records are kept manually or ad hoc, with
no per-user access control, no shared validation rules, and no API contract.

The real context is a **technical hiring assessment**: the project exists to demonstrate
engineering judgment, security, maintainability, and the ability to explain every decision.

## Difficulties

* No secure, per-user registry of books.
* No consistent validation for `name`, `isbn`, and `value`.
* No predictable JSON API or error contract.
* No evidence of maintainable structure, tests, or documentation.

## Affected users

* **Book owners** (product framing) who need a private, validated registry.
* **Technical reviewer** who must install, run, inspect, and question the solution.

## Impact

Real-world impact is intentionally small because this is an assessment. The project's value is
demonstrating senior engineering quality and a defensible, explainable implementation.

---

# Main objective

Deliver a working, secure, maintainable Book Registry API that satisfies the brief, passes its
quality gates, and can be explained line by line in a live review without AI assistance.

Success is not only a working endpoint: the code must be understandable, the decisions must be
documented, and the API must behave consistently.

---

# Main users

## Primary user

### Name

Authenticated book owner.

### Description

A person with valid credentials who manages a personal registry of books and must not see or
modify other users' records.

### Expected value

A simple, private, validated way to create, find, update, and remove their own books.

---

## Secondary users

* **Technical reviewer/evaluator:** installs the project, reviews code and tests, and performs
  a live review. Not an end user of the API, but a consumer of the delivery quality.
* **Future consuming application:** any client that will use the JSON API. It needs a stable,
  versioned contract.

---

# Main product capabilities

## Authentication

Allows a user to log in with a password, receive an access token, and log out by revoking the
current token.

## Book management

Allows an authenticated user to create, list, view, partially update, and hard-delete their own
books.

## API contract and security

Provides JSON-only responses, a single error envelope, ownership isolation, rate limiting, and
a versioned API surface.

## Delivery and documentation

Provides migrations, seeders, automated tests, a `.env.example`, and a README covering setup,
assumptions, decisions, and scalability.

---

# Product principles

## Principle 1 — Simplicity and proportionality

### Name:

Simplicity and proportionality.

### Application:

Build only what the brief requires, with the smallest defensible structure. Use thin
controllers and one service per business controller for business logic. No repository pattern,
no DDD ceremony, no microservices, and no abstractions that cannot be explained in the live
review.

## Principle 2 — Security by design

### Name:

Security by design.

### Application:

Authentication, ownership isolation, input validation, password hashing, token revocation, and
rate limiting are part of the design from the start, not later hardening.

## Principle 3 — Ownership isolation

### Name:

Ownership isolation.

### Application:

Every book operation starts from the authenticated user's relationship. A book that belongs to
someone else is treated as not found (404); no endpoint may leak another user's data.

## Principle 4 — Contract consistency

### Name:

Contract consistency.

### Application:

All responses are JSON; success and error shapes are stable; the API is versioned at
`/api/v1`; breaking changes require a new version or explicit approval.

## Principle 5 — Explainability and testability

### Name:

Explainability and testability.

### Application:

Every non-trivial decision must be explainable without AI. Business rules and security-critical
paths must have automated tests. Code is written for the next developer to read.

---

# Product scope

## Includes

* Login with email and password.
* Logout with current-token revocation.
* Create, list, view, partially update, and hard-delete own books.
* Book fields: `name` (required), `isbn` (digits only), `value` (decimal).
* JSON-only responses with a consistent error envelope.
* Ownership isolation and anti-IDOR behavior.
* Pagination, rate limiting, token expiration, and `/api/v1`.
* Migrations, seeders, tests, README, and Git repository.

## Excludes

* Public registration (sign-up).
* Roles, permissions, administration, reporting, notifications.
* Frontend/UI.
* External integrations.
* Search/filtering, soft deletes, audit trails.
* Multi-tenancy beyond per-user ownership.
* OpenAPI/Swagger, Docker, and CI/CD as required deliverables.
* Production hosting or operations.

---

# Criteria for accepting new features

Before adding a feature, evaluate:

## Value

Does it provide real value to the authenticated book owner or the reviewer?

## Alignment

Is it related to the mission and the accepted scope?

## Complexity

Does the benefit justify the cost and the additional surface area?

## Maintenance

Does it increase complexity unnecessarily or make the live review harder?

## Scope fit

Does it belong to this assessment, or is it a future product idea? If it expands the scope, it
must be approved explicitly before implementation.

---

# Important product decisions

## Decision — No public registration

**Description:** Accounts are created by a seeder/factory; there is no sign-up endpoint.

**Motive:** The brief only requires login and logout. Avoiding registration keeps scope and
attack surface small.

**Impact:** The reviewer receives demo credentials in the README. Adding registration later
requires a new feature and security review.

## Decision — Per-user ownership

**Description:** A user can only operate on their own books; foreign resources return 404.

**Motive:** "Their own book records" is an explicit requirement and a key security criterion.

**Impact:** Every query is scoped through the authenticated user; tests must prove isolation.

## Decision — API-only

**Description:** No frontend or UI is part of the deliverable.

**Motive:** The brief asks for an API; a UI would add unrequested scope.

**Impact:** No design system is implemented. If a UI is added, `docs/constitution/ui-ux.md`
applies and must be revisited.

## Decision — No roles or administration

**Description:** There are no roles, administrators, or cross-user views.

**Motive:** Not requested, and they would complicate authorization without adding assessed value.

**Impact:** Permissions are limited to the owner check. Future roles require an explicit feature
and an architectural review.

---

# Opportunity

This is not a commercial market opportunity. The opportunity is to demonstrate senior backend
engineering: clear structure, secure defaults, reliable tests, and well-documented trade-offs.
The resulting codebase can also serve as a small reference implementation for a personal library
registry if the product is ever extended.

---

# Success indicators

* Every functional requirement in `requirements.md` is implemented and verified.
* Lint, tests, and build pass as defined in `tech-stack.md`.
* Cross-user access is blocked for list, view, update, and delete.
* Validation rejects invalid `name`, `isbn`, and `value` with 422 JSON responses.
* A clean setup from `README.md` works without undocumented steps.
* The author can explain every part of the code and complete a live change/debugging task.
* No critical security issue remains (IDOR, mass assignment, exposed secrets, stack traces).

---

# Known product constraints

* The brief is intentionally short; assumptions are documented rather than silently invented.
* The solution must remain proportionate to the assessment.
* Part of the evaluation is a live review without AI assistance.
* No budget, deadline, or legislation is specified.
* Documentation and code are written in English; working discussions may be in Spanish.
* There is no production data or production environment.

---

# Product evolution

The product may grow only through explicit, approved features. Possible directions:

* Public registration and account management.
* Search and filtering by name or ISBN.
* Roles and administration.
* Soft deletes and audit history.
* Multi-tenancy for organizations.
* A frontend consuming the API.
* OpenAPI documentation, Docker, and CI/CD.

None of these are approved. They remain ideas until a spec is created, prioritized, and
approved. Growth in users or data may require a database and infrastructure review; that is a
technical decision documented in the architecture.

---

# Rules for AI agents

Before proposing a feature, the agent must validate:

1. Is it aligned with the mission?
2. Does it solve a real problem?
3. Does it provide value to the user?
4. Does it respect the product boundaries?

If a proposal contradicts this document, the agent must request review before implementing it.

---

# Document maintenance

Update this document when:

* the product purpose changes;
* the target user changes;
* the main scope changes.

Do not modify it for:

* visual changes;
* new libraries;
* minor technical decisions.
