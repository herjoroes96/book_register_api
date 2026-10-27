# Constraints.md

# Project Constraints and Limits

> **Status:** Approved by the human on 2026-10-26.

## Purpose

This document defines the technical, functional, and security constraints that must be respected
during development.

Its goal is to prevent decisions that could create future problems, break existing behavior, or
introduce unnecessary risk.

These rules take priority over individual implementation preferences.

---

# General rules

## Controlled changes

Before making important changes:

The agent must:

* analyze impact;
* identify affected files;
* explain risks;
* propose a plan.

Do not make broad modifications without prior validation.

---

# Architectural constraints

## Respect for the existing architecture

Do not create parallel structures that contradict the architecture defined in
`architecture.md`.

Do not create without justification:

* new patterns;
* new layers;
* new organization schemes.

A new architectural layer or pattern requires an ADR and explicit approval.

## Services are the business-logic layer

Business logic belongs in one service per business controller:

* `AuthService` owns authentication and token operations.
* `BookService` owns book rules, ownership, pagination, and transactions.
* Controllers may delegate and map responses, but must not query the database or apply reusable
  business rules.
* Services return models, paginators, or arrays, never HTTP responses, and do not accept HTTP
  `Request` objects.
* `HealthController` is an infrastructure endpoint without a service.
* No repository layer and no Actions pattern.

## Do not mix responsibilities

It is forbidden to:

* put business rules in routes, middleware, controllers, or API resources;
* run database queries from arbitrary classes outside the service/model boundary;
* put HTTP response logic inside a service;
* mix infrastructure concerns with domain behavior;
* duplicate validation between requests and services.

---

# Code constraints

## Minimum quality

Do not accept:

* duplicated code without justification;
* unnecessarily large functions or classes;
* ambiguous names;
* code without validation when handling external data;
* dead code or unused imports.

## Typing

When the language allows it:

Prefer:

* strong typing;
* clear interfaces;
* explicit contracts.

Project rules:

* application and test PHP files use `declare(strict_types=1);`;
* use parameter, return, and property types where practical;
* do not leave external values unvalidated;
* document array shapes when they are not obvious.

Avoid:

* unnecessary generic types;
* untyped arrays used as informal objects.

## Dependencies

Do not install new dependencies without:

* explaining the need;
* evaluating alternatives;
* validating impact;
* confirming that the architecture does not change.

Avoid adding libraries for simple problems. Sanctum, PHPUnit, and Pint are approved; Larastan is
optional.

---

# Security constraints

## Sensitive information

Never store directly in code:

* passwords;
* tokens;
* private keys;
* credentials;
* external service secrets.

Use:

* environment variables;
* secret managers when available;
* secure configuration.

## User data

Protect:

* personal information;
* financial information;
* private data;
* credentials.

Apply:

* least privilege;
* validation;
* access controls.

Project-specific rules:

* every book route requires authentication;
* every book query is scoped to the authenticated user;
* foreign resources return 404;
* passwords and tokens are never returned or logged;
* API errors never expose stack traces;
* rate limits apply to login and the general API;
* HTTPS is required outside local development.

---

# Database constraints

Do not perform:

* manual schema changes without a migration;
* data deletion without confirmation;
* destructive modifications without a backup.

Before schema changes:

Review:

* impact;
* compatibility;
* required migration.

Project rules:

* applied migrations are immutable; add a new migration instead of editing one;
* schema and queries must remain portable between SQLite and MySQL;
* `isbn` is a string; `value` is `decimal(12,2)`;
* foreign keys and indexes are declared in migrations;
* no unique index on `isbn`.

---

# Production constraints

No production environment is in scope.

If one is added, never directly perform:

* destructive changes;
* mass data deletion;
* critical modifications;

without:

* backup;
* validation;
* approval.

---

# File constraints

Define protected files or folders.

## Do not modify

```text
aipf/
```

Reason:

```text
It is the AIPF kit used to run the project. It must not be edited during the project.
```

```text
docs/project/client-info.md
```

Reason:

```text
It is the raw client input. It must remain faithful and unedited so assumptions and traceability
can be audited.
```

```text
AGENTS.md
```

Reason:

```text
It is the project behavior contract. Changes require explicit human approval.
```

```text
docs/constitution/
```

Reason:

```text
It contains the permanent project rules (level 2). Changes require analysis and approval.
```

```text
.env
```

Reason:

```text
It contains local secrets and environment-specific values. Never commit or expose it.
```

```text
vendor/
```

Reason:

```text
Generated dependencies, not project source code.
```

```text
storage/  and  bootstrap/cache/
```

Reason:

```text
Generated runtime files; do not edit manually.
```

---

## Sensitive files

Example:

```text
.env
config/production.*
credentials/*
storage/logs/*
```

Rule:

Do not modify or expose sensitive information.

---

# Functional constraints

Register business rules that cannot be broken.

## Rule — Ownership isolation

Description:

```text
A user can only list, view, update, and delete their own books.
```

Impact:

```text
Protects the core security requirement. Foreign IDs must return 404.
```

## Rule — Required book fields

Description:

```text
name, isbn, and value are required on creation.
```

Impact:

```text
Prevents incomplete book records.
```

## Rule — ISBN format

Description:

```text
isbn accepts digits only, is stored as a string, has a maximum length of 20, and is not unique.
```

Impact:

```text
Preserves leading zeros and avoids inventing uniqueness rules not present in the brief.
```

## Rule — Value format

Description:

```text
value is non-negative, has at most two decimals, and is serialized as a string.
```

Impact:

```text
Preserves decimal precision and defines a stable API contract.
```

## Rule — JSON-only responses

Description:

```text
All API responses, including errors, are JSON.
```

Impact:

```text
Prevents HTML error pages and inconsistent client handling.
```

## Rule — No public registration

Description:

```text
Accounts are created by seeders/factories; there is no sign-up endpoint.
```

Impact:

```text
Keeps the current scope and attack surface small. Adding registration requires approval.
```

## Rule — Partial updates and hard deletes

Description:

```text
Updates use PATCH semantics; omitted fields remain unchanged. Deletes are permanent.
```

Impact:

```text
Defines update behavior and prevents accidental expectations of soft-delete recovery.
```

## Rule — Pagination and ordering

Description:

```text
Lists are paginated at 15 items per page, ordered by created_at desc, then id desc.
```

Impact:

```text
Prevents unbounded responses and unstable ordering.
```

## Rule — Token and rate limits

Description:

```text
Tokens expire after 7 days; logout revokes the current token. Login is limited to 5 requests
per minute per email+IP; the general API is limited to 60 requests per minute.
```

Impact:

```text
Reduces abuse and defines session lifetime.
```

---

# Compatibility

When modifying existing functionality:

Evaluate:

* current users;
* existing data;
* dependent integrations;
* previous versions.

Avoid breaking functionality without planning.

Project rules:

* all API routes remain under `/api/v1`;
* no breaking JSON contract changes without a new version or approval;
* database changes remain SQLite/MySQL portable;
* schema changes must be reversible where possible.

---

# Technology changes

Do not change:

* main framework;
* database;
* architecture;
* infrastructure;

without:

1. Analyzing the need.
2. Evaluating alternatives.
3. Documenting impact.
4. Obtaining approval.

---

# Error handling

Do not hide errors through:

* empty catches;
* generic messages that erase context;
* removing validation.

Errors must be handled correctly and logged without sensitive data.

---

# Testing constraints

Do not consider a feature finished if:

* acceptance criteria are not met;
* existing tests fail;
* new known errors are introduced;
* business services have no direct tests when they contain rules;
* lint, tests, or build fail.

---

# Documentation constraints

Keep updated:

* technical documentation;
* important decisions;
* architectural changes;
* API contract changes;
* setup instructions.

Do not leave relevant decisions only in conversations.

---

# Rules for AI agents

Before modifying code, the agent must:

1. Review these constraints.
2. Review the architecture.
3. Review the conventions.
4. Evaluate impact.

The agent must stop and ask when:

* there is risk of data loss;
* a business rule is ambiguous;
* an architecture change is needed;
* a decision has high impact;
* a protected file must be changed.

---

# Exceptions

No exceptions have been approved.

If a constraint must be broken:

Document:

## Exception

Description:

```text
<Which rule is broken>
```

## Motive

```text
<Why it is necessary>
```

## Impact

```text
<Consequences>
```

## Approval

```text
<Who authorized it>
```

---

# Maintenance

Update this document when:

* new constraints appear;
* important lessons are learned;
* business rules change;
* new risks are identified.
