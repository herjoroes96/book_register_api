# Feature Specification

# 002 · Books Create, List, Show

## Status

Allowed values:

* proposed
* approved
* in progress
* implemented
* cancelled

Current status:

```text
implemented
```

---

# Summary

## What it is

Adds the first book-management slice for authenticated users: create a book, list their own
books, and view the details of one of their own books.

Every book belongs to the authenticated user who created it. Users cannot see or retrieve other
users' books.

---

# Context

## Current situation

`001-auth-minimum` is complete. A documented demo user can log in, access an authenticated
profile, and log out. The application has no book data or book-management behavior yet.

## Problem

The product is a book registry, but users cannot register books or consult their collection.
Without this slice, authentication has no protected business capability and ownership rules
cannot be demonstrated.

## Who it affects

* The authenticated book owner who needs to register and consult their books.
* The technical reviewer who must verify validation, ownership, pagination, and JSON contracts.
* The next feature (`003-books-update-delete`), which depends on the book model and ownership
  boundary established here.

## Impact

This is the first useful business slice. It turns authentication into a working book registry,
proves that ownership is enforced, and prepares update/delete without implementing them yet.

---

# Objective

Allow an authenticated user to create valid books, list only their own books with stable
pagination, and view the details of one of their own books. Invalid input must be rejected
clearly, and foreign or nonexistent books must not be exposed.

---

# Users involved

## Main user

Name:

```text
Authenticated book owner
```

Need:

```text
Register books and consult only their own collection.
```

---

## Secondary users

* Technical reviewer: verifies creation, validation, pagination, and ownership isolation.
* Future feature developer: builds update/delete and later book capabilities on this contract.

---

# Scope

## Includes

* Create a book owned by the authenticated user.
* Required name, ISBN, and value validation.
* List the authenticated user's books with fixed pagination.
* View one of the authenticated user's books.
* Ownership enforcement for creation, listing, and detail.
* Stable ordering of the list.
* JSON success and error responses.
* Automated tests for creation, validation, pagination, and cross-user access.
* Basic README/API documentation updates.

---

## Does not include

* Updating books.
* Deleting books.
* Search or filtering by name, ISBN, or value.
* Changing the page size or requesting an unbounded list.
* Public catalog or access without authentication.
* Roles, administrators, or cross-user views.
* Categories, authors, publishers, loans, or reading status.
* Soft deletes, audit history, or import/export.
* UI, Docker, CI/CD, or production deployment.

---

# Functional flow

## Create

```text
Authenticated user
    │
    ▼
Submits name, ISBN, and value
    │
    ▼
System validates input
    │
    ├── invalid input → validation error; no book is created
    └── valid input → book created and assigned to the authenticated user
```

## List

```text
Authenticated user
    │
    ▼
Requests their book collection
    │
    ▼
System returns only their books, newest first, in fixed-size pages
```

## Detail

```text
Authenticated user
    │
    ▼
Requests one book
    │
    ├── book belongs to the user → details returned
    └── book is missing or belongs to someone else → not found
```

---

# Business rules

| ID | Rule | Motive |
| -- | ---- | ------ |
| BR-01 | All book operations require authentication. | A book registry is private to its owner. |
| BR-02 | The owner is always the authenticated user; the client cannot choose or override the owner. | Prevents cross-user data creation and ownership spoofing. |
| BR-03 | `name` is required, textual, and has a maximum length of 255 characters. | Ensures every book has a usable title. |
| BR-04 | `isbn` is required, accepts digits only, has a maximum length of 20, preserves leading zeros, is not checksum-validated, and is not unique. | Matches the accepted ISBN decision and avoids inventing uniqueness. |
| BR-05 | `value` is required, non-negative, has at most two decimal places, maximum 9,999,999,999.99, and is presented consistently with two decimals. No currency field is added. | Preserves decimal precision and a stable contract. |
| BR-06 | A successful creation returns the created book with its owner-scoped data. | Gives immediate confirmation to the client. |
| BR-07 | The list returns only the authenticated user's books, at 15 items per page, ordered by creation time descending and then by identifier descending. | Prevents cross-user leakage, unbounded responses, and unstable ordering. |
| BR-08 | The list does not accept search, filters, custom sorting, or a custom page size in this feature. | Keeps the slice small; search and filters are future work. |
| BR-09 | Viewing a book is allowed only when it belongs to the authenticated user. A missing or foreign book is treated as not found. | Prevents resource enumeration and cross-user disclosure. |
| BR-10 | Duplicate ISBN values are allowed. | The brief does not require uniqueness. |
| BR-11 | Responses are JSON and never expose passwords, tokens, internal credentials, or another user's data. | Keeps the API safe and consistent. |
| BR-12 | Update and delete behavior is not part of this feature. | Keeps the slice vertical and leaves those operations to `003`. |

---

# Acceptance criteria

## Scenario 1 — Successful creation

Given:

```text
An authenticated user
```

When:

```text
The user submits a valid name, ISBN, and value
```

Then:

```text
The book is created, assigned to that user, and returned with its public fields
```

## Scenario 2 — Required fields

Given:

```text
An authenticated user
```

When:

```text
The user omits the name, ISBN, or value
```

Then:

```text
The request is rejected with a JSON validation error and no book is created
```

## Scenario 3 — ISBN format

Given:

```text
An authenticated user
```

When:

```text
The user submits an ISBN containing letters, hyphens, spaces, or other non-digit characters
```

Then:

```text
The request is rejected with a JSON validation error and no book is created
```

## Scenario 4 — ISBN leading zeros

Given:

```text
An authenticated user
```

When:

```text
The user creates a book with an ISBN that starts with zero
```

Then:

```text
The leading zeros are preserved when the book is returned or listed
```

## Scenario 5 — Value validation

Given:

```text
An authenticated user
```

When:

```text
The user submits a negative value, more than two decimal places, or a value above the allowed maximum
```

Then:

```text
The request is rejected with a JSON validation error and no book is created
```

## Scenario 6 — Duplicate ISBN

Given:

```text
An authenticated user who already owns a book with a given ISBN
```

When:

```text
The user creates another book with the same ISBN
```

Then:

```text
The second book is accepted because ISBN uniqueness is not required
```

## Scenario 7 — Owner cannot be spoofed

Given:

```text
An authenticated user
```

When:

```text
The creation request includes an owner or user identifier belonging to someone else
```

Then:

```text
The book still belongs to the authenticated user, and no other user's data is exposed
```

## Scenario 8 — List isolation and pagination

Given:

```text
Two users with books
```

When:

```text
One user lists their books
```

Then:

```text
Only that user's books are returned, 15 per page, newest first, with pagination metadata
```

## Scenario 9 — Detail isolation

Given:

```text
A book owned by another user
```

When:

```text
The authenticated user requests that book's details
```

Then:

```text
The response is not found and no book data is exposed
```

## Scenario 10 — Detail of an owned book

Given:

```text
A book owned by the authenticated user
```

When:

```text
The user requests its details
```

Then:

```text
The book's public fields are returned
```

## Scenario 11 — Unauthenticated access

Given:

```text
A request without a valid access credential
```

When:

```text
The user attempts to create, list, or view books
```

Then:

```text
The request is rejected as unauthenticated
```

## Scenario 12 — Safe JSON contract

Given:

```text
A successful or failed book request
```

When:

```text
The response is inspected
```

Then:

```text
The response is JSON and contains no passwords, tokens, internal credentials, or another user's data
```

---

# Alternative cases and errors

| Case | Situation | Expected result |
| ---- | --------- | --------------- |
| Missing authentication | No valid access credential | Authentication error |
| Missing name | Creation without name | JSON validation error; no book |
| Missing ISBN | Creation without ISBN | JSON validation error; no book |
| Missing value | Creation without value | JSON validation error; no book |
| Malformed ISBN | ISBN contains non-digits | JSON validation error; no book |
| Invalid value | Negative, too many decimals, or above maximum | JSON validation error; no book |
| Duplicate ISBN | ISBN already exists | Accepted; books remain independent |
| Foreign book | Detail request for another user's book | Not found; no data exposed |
| Nonexistent book | Detail request for an unknown identifier | Not found |
| Page beyond range | List page with no records | Empty page with valid pagination metadata |
| Client-supplied owner | Creation includes another user's identifier | Ignored or rejected; book stays with the authenticated user |

---

# Data involved

| Data | Description | Required |
| ---- | ----------- | -------- |
| Book identifier | Public identifier of the book | Generated |
| Owner | Authenticated user who owns the book | Derived from authentication |
| Name | Book title | Yes |
| ISBN | Digits only, leading zeros preserved | Yes |
| Value | Non-negative decimal with two places | Yes |
| Creation timestamp | Used for stable ordering | Generated |
| Pagination metadata | Current page, total, and related page information | Generated for lists |

No passwords, tokens, or other users' book data are exposed.

---

# Permissions

| Role | Action |
| ---- | ------ |
| Unauthenticated visitor | No book operations |
| Authenticated book owner | Create, list, and view their own books |
| Other authenticated user | Cannot list or view another user's books |
| Administrator | Does not exist in this feature |

---

# Dependencies

Features required before:

```text
001 · auth-minimum
```

External systems:

```text
None.
```

---

# Expected impact

* Delivers the first useful business capability of the product.
* Proves ownership isolation for creation, listing, and detail.
* Establishes the book data contract used by `003-books-update-delete`.
* Gives the reviewer a concrete CRUD slice to evaluate.
* Keeps the scope narrow: no update, delete, search, or public catalog.

---

# Out of scope

* Updating books.
* Deleting books.
* Search, filtering, custom sorting, or configurable page size.
* Public or cross-user book access.
* Roles, administrators, or shared collections.
* Categories, authors, publishers, loans, or reading status.
* Soft deletes, audit history, import, or export.
* UI, Docker, CI/CD, production deployment, or monitoring.

---

# Additional notes

This is the first book slice, not the complete book module. Update and delete belong to `003`.

The owner is always derived from authentication. A client-supplied owner identifier must never
override it.

The list is fixed at 15 items per page with newest-first ordering. Search and filtering are
deliberately deferred.

Duplicate ISBN values are valid. No uniqueness constraint or checksum validation is added.

Technical implementation details belong to `plan.md`.

---

# Validation before implementation

The feature must be approved when:

* [x] The problem is clear.
* [x] The target user is defined.
* [x] The scope is delimited.
* [x] The acceptance criteria are verifiable.
* [x] The business rules are documented.
* [x] The feature is approved by the human (2026-10-26).
