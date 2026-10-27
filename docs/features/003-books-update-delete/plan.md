# Feature Implementation Plan

# 003 · Books Update, Delete

## General information

Related feature:

```text
docs/features/003-books-update-delete/spec.md
```

Status:

```text
completed
```

---

# Technical objective

Complete the book CRUD lifecycle on top of `002-books-create-list-show`:

* add partial book updates through `BookService::updateForUser`;
* add permanent deletion through `BookService::deleteForUser`;
* keep `BookController` HTTP-only;
* validate only supplied fields and reject empty updates;
* enforce ownership for both operations;
* cover service and HTTP behavior with tests;
* document the update/delete contract.

No new database table or column is required. Create/list/detail behavior must remain unchanged.

---

# Architecture analysis

## Current architecture

The book slice uses `Book` + `BookService` + `BookController` under ADR-008. `BookService` already
provides owner-scoped create, paginated listing, and detail lookup. There is no update or delete
behavior yet.

## Architectural impact

* **Backend:** extend `BookService`, `BookController`, routes, and add `UpdateBookRequest`.
* **Database:** no schema change. Updates modify existing fields and `updated_at`; deletes remove
  the row.
* **Tests:** extend `BookServiceTest` and `BookTest`.
* **Documentation:** README/API examples and feature documentation.
* **Frontend, integrations, infrastructure:** none.

## Architectural decisions

| ID | Decision | Motive | Alternatives | Consequence |
| -- | -------- | ------ | ------------ | ----------- |
| D-001 | Add `UpdateBookRequest` with `sometimes` rules and an after-validation check requiring at least one of name, ISBN, or value. | Partial updates must not be empty or silently reset fields. | Treat empty updates as no-op; require all fields. | Empty and invalid updates return `422` before the service. |
| D-002 | Add `BookService::updateForUser` and `deleteForUser`, both using `findForUser` first. | Keeps ownership and persistence rules in the service. | Controller queries; route model binding plus policy. | Foreign/missing books throw not-found before mutation. |
| D-003 | Use `PATCH /api/v1/books/{book}` with only validated supplied fields. | Matches partial-update semantics accepted in the requirements. | `PUT` full replacement. | Omitted fields remain unchanged. |
| D-004 | Use `DELETE /api/v1/books/{book}` with hard deletion and a JSON success message. | The brief specifies deletion and no recovery requirement. | Soft delete/restore. | Deleted rows are gone; subsequent detail/list access returns not found. |
| D-005 | Keep the owner immutable; reject or ignore `user_id` in updates. | Prevents ownership spoofing and cross-user movement. | Allow reassignment; policy-only checks. | Only name, ISBN, and value can change. |
| D-006 | Map not-found from the service to a generic `404` in the controller, as in detail. | Avoids exposing whether a foreign book exists. | Return 403; expose model messages. | Both missing and foreign books produce the same safe response. |
| D-007 | Add no new migration and no new package. | The existing `books` schema already supports both operations. | Add audit/soft-delete columns. | The change stays small and reversible through the existing migration. |

---

# Implementation strategy

1. Add `UpdateBookRequest` with partial rules and the empty-update check.
2. Extend `BookService` with owner-scoped update and delete methods; add service tests.
3. Add `BookController@update` and the protected PATCH route; add HTTP update tests.
4. Add `BookController@destroy` and the protected DELETE route; add HTTP delete tests.
5. Complete the remaining contract tests (ownership, empty update, duplicate/leading-zero ISBN,
   unauth, deleted book no longer available) and update README.
6. Run Pint, tests, build, and cleanup.

The service method is the only business entry point; the controller maps results and not-found
errors to HTTP.

---

# Affected components

| Path | Responsibility | Expected change |
| ---- | -------------- | --------------- |
| `app/Http/Requests/Books/UpdateBookRequest.php` | Partial-update validation. | New request with `sometimes` rules and empty-update rejection. |
| `app/Services/BookService.php` | Book business logic. | Add `updateForUser` and `deleteForUser`. |
| `app/Http/Controllers/Api/V1/BookController.php` | HTTP orchestration. | Add `update` and `destroy`; map not-found to generic 404. |
| `routes/api.php` | Versioned API routes. | Add protected PATCH and DELETE book routes. |
| `tests/Feature/Services/BookServiceTest.php` | Service behavior. | Add update/delete ownership, partial-change, and not-found tests. |
| `tests/Feature/BookTest.php` | HTTP contract. | Add partial update, validation, delete, and isolation tests. |
| `README.md` | API documentation. | Add PATCH/DELETE examples, rules, and deletion behavior. |

No migration or model change is expected.

---

# New components

| Component | Location | Responsibility |
| --------- | -------- | -------------- |
| `UpdateBookRequest` | `app/Http/Requests/Books/UpdateBookRequest.php` | Validate only supplied name/ISBN/value and require at least one field. |

---

# Data model

## New entities

None.

## Existing changes

* No schema change.
* `name`, `isbn`, and `value` can be updated one at a time or together.
* `updated_at` refreshes on update.
* Delete removes the `books` row permanently.
* The owner (`user_id`) is never changed.
* No soft-delete columns.
* No audit table.

---

# Service contract

`BookService` adds the final CRUD methods:

| Method | Input | Output |
| ------ | ----- | ------ |
| `updateForUser` | `User $user`, book identifier, validated partial data | Updated `Book`; throws not-found for missing/foreign books. |
| `deleteForUser` | `User $user`, book identifier | No return; throws not-found for missing/foreign books. |

Both methods start with the owner-scoped `findForUser`. The service never accepts a `Request`,
never returns an HTTP response, and never accepts an owner change.

---

# Validation contract

`UpdateBookRequest` applies:

* `name`: optional, but when present required, string, maximum 255 characters.
* `isbn`: optional, but when present required, string, digits only, maximum 20 characters.
* `value`: optional, but when present required, numeric, maximum 2 decimals, minimum 0, maximum
  9,999,999,999.99.
* No `user_id` field.
* At least one of `name`, `isbn`, or `value` must be present.
* Empty updates return `422`.

The controller passes only validated fields to `BookService`.

---

# API / Contracts

All routes require `Authorization: Bearer <token>` and return JSON.

## Update

Method: `PATCH`

Route: `/api/v1/books/{book}`

Request examples:

```json
{
  "name": "The Pragmatic Programmer, 2nd Edition"
}
```

```json
{
  "isbn": "0012345678901",
  "value": "54.90"
}
```

Success `200`:

```json
{
  "data": {
    "id": 1,
    "name": "The Pragmatic Programmer, 2nd Edition",
    "isbn": "0012345678901",
    "value": "54.90",
    "created_at": "2026-10-26T16:30:00.000000Z",
    "updated_at": "2026-10-26T17:00:00.000000Z"
  }
}
```

Errors:

* `401` unauthenticated.
* `404` missing or foreign book.
* `422` empty update or invalid supplied field.

## Delete

Method: `DELETE`

Route: `/api/v1/books/{book}`

Success `200`:

```json
{
  "message": "Book deleted."
}
```

Errors:

* `401` unauthenticated.
* `404` missing or foreign book.

After deletion, the book is absent from the owner's list and its detail returns not found.

The shared error envelope remains deferred to `004-api-contract-hardening`.

---

# External integrations

None.

---

# Security

* **Authentication:** every update/delete route uses `auth:sanctum`.
* **Authorization:** both operations start from the authenticated user's `books()` relationship.
  Foreign or missing identifiers return 404.
* **Mass assignment:** only validated update fields are passed to the service; `user_id` is not
  accepted.
* **Owner integrity:** the owner is immutable; no cross-user transfer is possible.
* **Validation:** partial rules reject empty or invalid updates before persistence.
* **Data exposure:** responses use `BookResource` and contain no owner identifier, password, or
  token.
* **Deletion:** permanent; no hidden recovery or audit claim.
* **Logging:** no sensitive book or auth data is logged.

---

# Performance

* One owner-scoped lookup plus one update or delete per request.
* Existing indexes cover the owner lookup.
* No list/pagination changes and no N+1 queries.
* No caching, queues, or background jobs.

---

# Tests required

## Service tests

* Partial update changes only supplied fields and preserves the owner.
* Multiple-field update changes ISBN and value without changing the name.
* Duplicate ISBN and leading-zero ISBN are accepted on update.
* Foreign or missing update throws not-found and changes nothing.
* Delete removes only the current owner's book.
* Foreign or missing delete throws not-found and leaves the foreign book intact.

## Feature tests

* Partial update returns `200` with the public shape and unchanged omitted fields.
* Multiple-field update returns the expected values.
* Empty update returns `422` and leaves the book unchanged.
* Invalid name, ISBN, or value returns `422` and leaves the book unchanged.
* Client-supplied `user_id` does not change the owner.
* Foreign or missing update returns `404` and does not change data.
* Unauthenticated update returns `401`.
* Delete returns `200` with the success message and removes the book.
* After delete, list and detail no longer expose the book.
* Foreign or missing delete returns `404`; the foreign book remains.
* Unauthenticated delete returns `401`.
* Responses are JSON and contain no sensitive or foreign data.

## Manual tests

* Log in with the demo user, create a book, update one field, update multiple fields, and delete
  it through the README examples.
* Confirm the deleted book no longer appears in the list and its detail returns 404.

---

# Migrations required

* **New migrations:** none.
* **Modified migrations:** none.
* **Schema:** unchanged.
* **Rollback:** not applicable.
* **Deleted data:** permanent by design; no recovery.

---

# Risks

| Risk | Impact | Mitigation |
| ---- | ------ | ---------- |
| Empty update is accepted as a no-op. | Medium | `UpdateBookRequest` after-validation check; HTTP test. |
| Partial update clears omitted fields. | High | Pass only validated fields; service tests. |
| Owner changes through input. | High | No `user_id` rule; model fillable excludes owner; test. |
| Foreign update/delete reveals data. | High | Scoped service lookup + generic 404. |
| Delete is not effective in list/detail. | Medium | Database assertions and post-delete list/detail tests. |
| Accidental soft-delete expectation. | Low | Document hard deletion; no `deleted_at`. |
| Route parameter bypasses ownership. | Medium | Service lookup with raw identifier; no route model binding. |
| Error contract drifts. | Low | Keep current JSON; 004 hardens the shared envelope. |
| Service becomes a god service. | Low | Only six cohesive CRUD methods; split if scope grows. |

---

# Technical dependencies

* `002-books-create-list-show` complete.
* ADR-008 service-layer pattern.
* Laravel 12.70.1, PHP 8.2.12, SQLite, PHPUnit, Pint.
* No new packages.
* No external services.

---

# Implementation order

1. Add `UpdateBookRequest`.
2. Extend `BookService` with update/delete and add service tests.
3. Add PATCH route/controller action and update tests.
4. Add DELETE route/controller action and delete tests.
5. Complete ownership/validation/contract tests and update README.
6. Run Pint, tests, build, and cleanup.

---

# Plan validation

Before implementing, verify:

* [x] It respects `architecture.md`.
* [x] It respects `tech-stack.md`.
* [x] It respects `conventions.md`.
* [x] It considers security.
* [x] It considers tests.
* [x] It defines affected files.
* [x] It defines risks.
* [x] It has human approval (2026-10-26).
