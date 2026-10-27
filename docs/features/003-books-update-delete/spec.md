# Feature Specification

# 003 · Books Update, Delete

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

Completes the book CRUD lifecycle for authenticated users. A user can partially update one of
their own books or permanently delete one of their own books.

Updates change only the fields that are provided. Deletion is permanent and removes the book from
the user's collection.

---

# Context

## Current situation

`002-books-create-list-show` is complete. An authenticated user can create books, list only their
own books, and view the details of one of their own books. There is no way to correct a book or
remove it.

## Problem

Book details can change, and users need to correct or retire records. Without update and delete,
the registry is incomplete and stale data cannot be managed.

## Who it affects

* The authenticated book owner who needs to correct or remove a book.
* The technical reviewer who must verify partial-update semantics, ownership, validation, and
  permanent deletion.

## Impact

This feature completes the CRUD surface, demonstrates safe partial updates, and proves that a
user cannot modify or delete another user's books. It also closes the book workflow before the
API contract hardening stage.

---

# Objective

Allow an authenticated user to partially update or permanently delete one of their own books,
with clear validation, strict ownership, and predictable JSON responses. Other users' books must
remain invisible.

---

# Users involved

## Main user

Name:

```text
Authenticated book owner
```

Need:

```text
Correct the details of a book or remove it permanently.
```

---

## Secondary users

* Technical reviewer: verifies partial updates, validation, ownership, and deletion behavior.
* Future feature developer: relies on the completed CRUD contract and ownership boundary.

---

# Scope

## Includes

* Partial update of a book owned by the authenticated user.
* Update of name, ISBN, and/or value while leaving omitted fields unchanged.
* Validation of any supplied update field.
* Permanent deletion of a book owned by the authenticated user.
* Ownership enforcement for update and delete.
* JSON success responses.
* Automated tests for partial updates, validation, ownership, and deletion.
* README/API documentation updates.

---

## Does not include

* Creating, listing, or viewing books; those exist in `002`.
* Updating or deleting another user's books.
* Changing the owner of a book.
* Soft deletes, restore, or recycle-bin behavior.
* Audit history or change tracking.
* Bulk updates or bulk deletes.
* Search, filtering, or sorting.
* Public or unauthenticated access.
* UI, Docker, CI/CD, or deployment work.

---

# Functional flow

## Partial update

```text
Authenticated user
    │
    ▼
Submits one or more updatable fields for their own book
    │
    ▼
System validates the provided fields
    │
    ├── no updatable field → validation error; book unchanged
    ├── invalid field → validation error; book unchanged
    ├── book missing or foreign → not found
    └── valid fields → only those fields are updated; book returned
```

## Delete

```text
Authenticated user
    │
    ▼
Requests deletion of their own book
    │
    ├── book missing or foreign → not found
    └── book owned by user → permanently deleted
                              │
                              ▼
                    success response; book no longer listed
```

---

# Business rules

| ID | Rule | Motive |
| -- | ---- | ------ |
| BR-01 | Update and delete require authentication. | Only the owner may manage their books. |
| BR-02 | A user can update or delete only their own books; foreign or missing books are treated as not found. | Prevents cross-user modification and resource enumeration. |
| BR-03 | Updates are partial: omitted fields remain unchanged. | Clients can correct one field without resending the full record. |
| BR-04 | An update must contain at least one of `name`, `isbn`, or `value`. | An empty update has no meaning and should not be reported as success. |
| BR-05 | Only name, ISBN, and value can be updated. The owner is immutable. | Prevents ownership changes and mass-assignment abuse. |
| BR-06 | When supplied, `name` must be a non-empty string of at most 255 characters. | Preserves a usable title. |
| BR-07 | When supplied, `isbn` must contain digits only, have a maximum length of 20, preserve leading zeros, and may duplicate another ISBN. | Matches the existing ISBN contract. |
| BR-08 | When supplied, `value` must be numeric, non-negative, have at most two decimal places, and not exceed 9,999,999,999.99. | Preserves decimal precision and the accepted value contract. |
| BR-09 | A successful update returns the book's public representation after the change. | Gives immediate confirmation of the new state. |
| BR-10 | Deletion is permanent. No soft-delete or restore behavior exists. | The brief asks for deletion, not recovery. |
| BR-11 | A successful delete returns a JSON success message. | Keeps the API contract consistent. |
| BR-12 | After deletion, the book must not appear in the owner's list or detail requests. | Confirms that the deletion is effective. |
| BR-13 | A failed update or delete must not change any book data. | Preserves data integrity. |
| BR-14 | Responses are JSON and expose no passwords, tokens, internal credentials, or another user's data. | Keeps the API safe and consistent. |
| BR-15 | Bulk update and bulk delete are not supported. | Keeps the scope focused and avoids accidental mass changes. |

---

# Acceptance criteria

## Scenario 1 — Update one field

Given:

```text
An authenticated user who owns a book
```

When:

```text
The user submits a new name and omits ISBN and value
```

Then:

```text
Only the name changes; ISBN and value remain unchanged; the updated book is returned
```

## Scenario 2 — Update multiple fields

Given:

```text
An authenticated user who owns a book
```

When:

```text
The user submits a new ISBN and value
```

Then:

```text
Both fields change; the name remains unchanged; the updated book is returned
```

## Scenario 3 — Empty update

Given:

```text
An authenticated user who owns a book
```

When:

```text
The user submits no updatable fields
```

Then:

```text
The request is rejected with a JSON validation error and the book is unchanged
```

## Scenario 4 — Invalid update field

Given:

```text
An authenticated user who owns a book
```

When:

```text
The user submits an invalid name, ISBN, or value
```

Then:

```text
The request is rejected with a JSON validation error and the book remains unchanged
```

## Scenario 5 — Update ownership isolation

Given:

```text
A book owned by another user
```

When:

```text
The authenticated user attempts to update it
```

Then:

```text
The response is not found and no book data is exposed or changed
```

## Scenario 6 — Owner cannot be changed

Given:

```text
An authenticated user who owns a book
```

When:

```text
The update includes an owner or user identifier for another user
```

Then:

```text
The owner remains unchanged and no other user's data is exposed
```

## Scenario 7 — Unauthenticated update

Given:

```text
A request without a valid access credential
```

When:

```text
The update is attempted
```

Then:

```text
The request is rejected as unauthenticated
```

## Scenario 8 — Delete an owned book

Given:

```text
An authenticated user who owns a book
```

When:

```text
The user deletes the book
```

Then:

```text
The response is a JSON success message and the book is permanently removed
```

## Scenario 9 — Deleted book is no longer available

Given:

```text
A book that was just deleted
```

When:

```text
The owner lists books or requests its detail
```

Then:

```text
The book is absent from the list and its detail is not found
```

## Scenario 10 — Delete ownership isolation

Given:

```text
A book owned by another user
```

When:

```text
The authenticated user attempts to delete it
```

Then:

```text
The response is not found and the foreign book remains unchanged
```

## Scenario 11 — Unauthenticated delete

Given:

```text
A request without a valid access credential
```

When:

```text
The delete is attempted
```

Then:

```text
The request is rejected as unauthenticated
```

## Scenario 12 — Safe JSON contract

Given:

```text
A successful or failed update/delete request
```

When:

```text
The response is inspected
```

Then:

```text
The response is JSON and contains no passwords, tokens, internal credentials, or another user's data
```

## Scenario 13 — Duplicate and leading-zero ISBN

Given:

```text
An authenticated user updating a book
```

When:

```text
The user submits an ISBN that duplicates another book or starts with zero
```

Then:

```text
The update is accepted and the leading zeros are preserved
```

---

# Alternative cases and errors

| Case | Situation | Expected result |
| ---- | --------- | --------------- |
| Missing authentication | No valid access credential | Authentication error |
| Empty update | No updatable fields supplied | JSON validation error; no change |
| Invalid name | Empty, non-string, or too long | JSON validation error; no change |
| Invalid ISBN | Non-digits, too long, or invalid type | JSON validation error; no change |
| Invalid value | Negative, too many decimals, or above maximum | JSON validation error; no change |
| Foreign book update | Update another user's book | Not found; no change |
| Missing book update | Update an unknown book | Not found |
| Foreign book delete | Delete another user's book | Not found; foreign book remains |
| Missing book delete | Delete an unknown book | Not found |
| Client-supplied owner | Update attempts to change owner | Owner remains unchanged |
| Bulk request | Multiple identifiers or mass operation | Not supported by this feature |

---

# Data involved

| Data | Description | Required |
| ---- | ----------- | -------- |
| Book identifier | Public identifier of the book | Yes |
| Owner | Authenticated user who owns the book | Immutable |
| Name | Book title | Only when supplied for update |
| ISBN | Digits only, leading zeros preserved | Only when supplied for update |
| Value | Non-negative decimal with two places | Only when supplied for update |
| Update timestamp | Refreshed after a successful update | Generated |
| Deletion result | Success message; the record is removed | Generated |

No passwords, tokens, or other users' book data are exposed.

---

# Permissions

| Role | Action |
| ---- | ------ |
| Unauthenticated visitor | No update or delete |
| Authenticated book owner | Update and delete their own books |
| Other authenticated user | Cannot update or delete another user's books |
| Administrator | Does not exist in this feature |

---

# Dependencies

Features required before:

```text
002 · books-create-list-show
```

External systems:

```text
None.
```

---

# Expected impact

* Completes the book CRUD workflow.
* Demonstrates safe partial-update behavior.
* Proves that ownership is enforced for modification and deletion.
* Prepares the project for API contract hardening in `004`.
* Keeps deletion simple and explicit, without hidden recovery behavior.

---

# Out of scope

* Create, list, and detail behavior already delivered by `002`.
* Soft deletes, restore, recycle bin, or undelete.
* Audit history or change tracking.
* Bulk update or bulk delete.
* Search, filtering, sorting, or pagination changes.
* Public or cross-user book access.
* Roles, administrators, or shared collections.
* UI, Docker, CI/CD, production deployment, or monitoring.

---

# Additional notes

Partial updates must not silently reset omitted fields. The owner is derived from authentication
and cannot be changed through input.

Deletion is permanent. The API must not imply restore capability.

Empty updates are rejected rather than treated as a no-op success. This keeps the contract
explicit and avoids ambiguous client behavior.

Validation runs before the ownership lookup. An invalid payload returns `422` even when the book
is foreign or missing; a valid payload against a foreign or missing book returns `404`. Neither
case reveals ownership.

The shared error envelope remains deferred to `004-api-contract-hardening`.

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
