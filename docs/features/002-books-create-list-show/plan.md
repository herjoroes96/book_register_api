# Feature Implementation Plan

# 002 · Books Create, List, Show

## General information

Related feature:

```text
docs/features/002-books-create-list-show/spec.md
```

Status:

```text
completed
```

---

# Technical objective

Add the first book-management slice on top of the completed authentication layer:

* create the book persistence model and migration;
* implement create, list, and detail through `BookService`;
* keep `BookController` HTTP-only;
* enforce ownership in every query;
* validate name, ISBN, and value;
* paginate the list at 15 items per page;
* cover service and HTTP behavior with tests;
* document the new endpoints.

Update, delete, search, filters, and public access remain out of scope.

---

# Architecture analysis

## Current architecture

The application is an API-only modular monolith on Laravel 12.70.1 and SQLite. Authentication is
complete through `AuthService` and `AuthController`, following ADR-008: controllers handle HTTP,
services own business logic, Form Requests validate, and API Resources shape JSON. There is no
book entity, migration, service, controller, or endpoint yet.

## Architectural impact

* **Backend:** new `Book` model, `BookService`, `BookController`, `StoreBookRequest`,
  `BookResource`, routes, and factory.
* **Database:** new `books` table with user ownership and list indexes. No change to users or
  authentication.
* **Seeding:** no book seed data; tests use factories. The existing demo user remains the
  authenticated actor.
* **Tests:** service tests for ownership/pagination and HTTP tests for the contract.
* **Documentation:** README/API examples and feature documentation.
* **Frontend, infrastructure, integrations:** none.

## Architectural decisions

| ID | Decision | Motive | Alternatives | Consequence |
| -- | -------- | ------ | ------------ | ----------- |
| D-001 | Put all book business rules and ownership in `BookService`; keep `BookController` HTTP-only. | Follows ADR-008 and keeps the first business slice testable. | Controller-owned logic; Actions; repository. | The controller delegates; service tests protect rules directly. |
| D-002 | Create a `books` table with `user_id`, `name`, `isbn`, `value`, and timestamps; index `(user_id, created_at)`; no separate `user_id` index; no unique ISBN. | The composite index covers owner-scoped lookups through its leftmost prefix; ISBN uniqueness is not a requirement. | Unique ISBN; no indexes; separate metadata tables. | Efficient owner-scoped lists and duplicate ISBN support without a redundant index. |
| D-003 | Use `BookResource` for public fields: id, name, isbn, value, created_at, updated_at. | Prevents leaking internal ownership fields and keeps a stable contract. | Return models directly; include `user_id`. | Resource tests define the public shape. |
| D-004 | Derive the owner from the authenticated user through `$user->books()`; never accept `user_id` from input. | Prevents ownership spoofing and cross-user creation. | Client-supplied owner; policy-only checks. | Service scoping is the single ownership boundary. |
| D-005 | Paginate at 15 items per page, ordered by `created_at` descending then `id` descending; no filters or custom page size. | Meets the accepted list contract and prevents unbounded or unstable results. | Cursor pagination; configurable page size; filters now. | Fixed, deterministic pages; search is deferred. |
| D-006 | Resolve the detail route through `BookService::findForUser()` using the raw book identifier. Do not use route model binding for ownership. | Keeps the ownership rule in the service and avoids fetching a foreign record before authorization. | Route model binding plus policy; controller query. | Missing and foreign books both produce 404. |
| D-007 | Validate with `StoreBookRequest` and persist only validated fields. | Keeps validation at the HTTP boundary and prevents mass assignment. | Inline controller validation; service-level request validation. | `user_id` is never accepted; invalid data is rejected before the service. |

---

# Implementation strategy

1. Create the `books` migration, `Book` model, `User::books()` relationship, and `BookFactory`.
2. Implement `BookService` with owner-scoped create, pagination, and detail methods.
3. Add `StoreBookRequest`, `BookResource`, `BookController`, and the protected book routes.
4. Implement create and its HTTP/service tests.
5. Implement list with pagination/order and its tests.
6. Implement detail with ownership/404 behavior and its tests.
7. Update README/API documentation and run the full quality gate.

Dependencies flow top to bottom. The service and ownership boundary must exist before the HTTP
contract is tested.

---

# Affected components

| Path | Responsibility | Expected change |
| ---- | -------------- | --------------- |
| `database/migrations/*_create_books_table.php` | Book schema. | New table with owner, fields, and indexes. |
| `app/Models/Book.php` | Book persistence and relationships. | New model with fillable fields, decimal cast, and owner relation. |
| `app/Models/User.php` | User relationships. | Add `books()` has-many relationship. |
| `database/factories/BookFactory.php` | Test data. | New factory for valid books and owner association. |
| `app/Services/BookService.php` | Book business logic. | New service with create, paginate, and detail methods. |
| `app/Http/Requests/Books/StoreBookRequest.php` | Creation validation. | New form request for name, ISBN, and value. |
| `app/Http/Resources/BookResource.php` | Public book contract. | New resource for public fields. |
| `app/Http/Controllers/Api/V1/BookController.php` | HTTP orchestration. | New controller with index, store, and show. |
| `routes/api.php` | Versioned routes. | Add protected book create/list/detail routes. |
| `tests/Feature/Services/BookServiceTest.php` | Service behavior. | New service tests for ownership and pagination. |
| `tests/Feature/BookTest.php` | HTTP contract. | New feature tests for create, list, detail, validation, and auth. |
| `README.md` | API documentation. | Add book endpoints and examples. |

---

# New components

| Component | Location | Responsibility |
| --------- | -------- | -------------- |
| `Book` | `app/Models/Book.php` | Book fields, owner relation, and decimal cast. |
| `BookService` | `app/Services/BookService.php` | Owner-scoped creation, pagination, and lookup. |
| `StoreBookRequest` | `app/Http/Requests/Books/StoreBookRequest.php` | Validate name, ISBN, and value. |
| `BookResource` | `app/Http/Resources/BookResource.php` | Expose only public book fields. |
| `BookController` | `app/Http/Controllers/Api/V1/BookController.php` | Translate HTTP and delegate to the service. |
| `BookFactory` | `database/factories/BookFactory.php` | Generate valid books for tests. |
| `BookServiceTest` | `tests/Feature/Services/BookServiceTest.php` | Protect ownership, ordering, and service rules. |
| `BookTest` | `tests/Feature/BookTest.php` | Protect the HTTP and JSON contract. |

---

# Data model

## New entities

### Book

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | bigint | Primary key. |
| `user_id` | foreign key | Owner; cascade on user deletion. |
| `name` | string(255) | Required book title. |
| `isbn` | string(20) | Digits only; leading zeros preserved; not unique. |
| `value` | decimal(12,2) | Non-negative value, two decimals. |
| `created_at`, `updated_at` | timestamps | Audit and stable ordering. |

## Existing changes

* `User` gains a `books()` has-many relationship.
* No changes to the users schema or authentication tables.
* No book seed data.
* Indexes: `(user_id, created_at)`; no separate `user_id` index.
* No unique index on `isbn`.

---


## Service contract

`BookService` exposes the only business entry points for this slice:

| Method | Input | Output |
| ------ | ----- | ------ |
| `paginateForUser` | `User $user`, optional page number | `LengthAwarePaginator` of the owner's books, 15 per page. |
| `createForUser` | `User $user`, validated book data | Created `Book` owned by the user. |
| `findForUser` | `User $user`, book identifier | Owned `Book`; throws not-found for missing or foreign books. |

The service never accepts a `Request`, never returns an HTTP response, and never accepts a
client-supplied owner. `BookController` maps these results to status codes and resources.


## Validation contract

`StoreBookRequest` applies:

* `name`: required, string, maximum 255 characters.
* `isbn`: required, string, digits only, maximum 20 characters.
* `value`: required, numeric, maximum 2 decimal places, minimum 0, maximum 9,999,999,999.99.

`user_id` is not an accepted field. The controller passes only validated fields to the service.

# API / Contracts

All book routes require `Authorization: Bearer <token>` and return JSON.

## Create

Method: `POST`

Route: `/api/v1/books`

Request:

```json
{
  "name": "The Pragmatic Programmer",
  "isbn": "9780135957059",
  "value": "49.90"
}
```

Success `201`:

```json
{
  "data": {
    "id": 1,
    "name": "The Pragmatic Programmer",
    "isbn": "9780135957059",
    "value": "49.90",
    "created_at": "2026-10-26T16:30:00.000000Z",
    "updated_at": "2026-10-26T16:30:00.000000Z"
  }
}
```

Errors:

* `401` unauthenticated.
* `422` missing/invalid fields.

## List

Method: `GET`

Route: `/api/v1/books`

Success `200`:

```json
{
  "data": [
    {
      "id": 1,
      "name": "The Pragmatic Programmer",
      "isbn": "9780135957059",
      "value": "49.90",
      "created_at": "2026-10-26T16:30:00.000000Z",
      "updated_at": "2026-10-26T16:30:00.000000Z"
    }
  ],
  "links": {},
  "meta": {}
}
```

The response is paginated at 15 items per page, newest first, then identifier descending.

## Detail

Method: `GET`

Route: `/api/v1/books/{book}`

Success `200`:

```json
{
  "data": {
    "id": 1,
    "name": "The Pragmatic Programmer",
    "isbn": "9780135957059",
    "value": "49.90",
    "created_at": "2026-10-26T16:30:00.000000Z",
    "updated_at": "2026-10-26T16:30:00.000000Z"
  }
}
```

Errors:

* `401` unauthenticated.
* `404` missing or foreign book.

The shared error envelope remains deferred to `004-api-contract-hardening`.

---

# External integrations

None.

---

# Security

* **Authentication:** every book route uses `auth:sanctum`.
* **Authorization:** all queries start from the authenticated user's `books()` relationship.
  Foreign identifiers return 404.
* **Mass assignment:** only validated name, ISBN, and value are persisted; `user_id` is assigned
  by the relationship, never by the client.
* **Validation:** required fields, ISBN digits only, value numeric/decimal bounds.
* **Data exposure:** `BookResource` exposes no owner identifier, password, token, or other user's
  data.
* **Logging:** no sensitive book or auth data is logged.
* **Scope:** no public catalog, cross-user list, update, delete, or admin behavior.

---

# Performance

* Owner-scoped queries use the `user_id` prefix of the `(user_id, created_at)` index.
* List queries use `(user_id, created_at)` with deterministic `id` tie-breaking.
* Pagination is fixed at 15; no unbounded list is possible.
* No N+1 queries; no eager-loaded relations are needed for the book contract.
* No caching or queues are introduced.

---

# Tests required

## Unit tests

* None for pure rules beyond the service tests; validation is covered at the HTTP boundary.

## Integration / service tests

* Service create assigns the authenticated owner.
* Service list returns only the owner's books, ordered and paginated.
* Service detail returns an owned book.
* Service detail throws not-found for a foreign or missing book.
* Service create accepts duplicate ISBN values.

## Feature tests

* Authenticated create returns `201` and the public book shape.
* Missing name, ISBN, or value returns `422` and creates nothing.
* Non-digit ISBN returns `422`.
* Leading-zero ISBN is preserved.
* Negative, over-precision, or over-limit value returns `422`.
* Two books with the same ISBN are accepted.
* Client-supplied `user_id` does not change ownership.
* List returns only the authenticated user's books.
* List paginates at 15 and orders newest first with identifier tie-breaking.
* A page beyond the available range returns empty data with pagination metadata.
* Detail returns an owned book.
* Detail returns `404` for a foreign or missing book.
* Unauthenticated create/list/detail returns `401`.
* Responses are JSON and expose no sensitive or foreign data.

## Manual tests

* Use the demo user to create a book, list it, and view it through the README examples.
* Create a second account fixture in tests and confirm isolation.
* Confirm duplicate ISBNs are accepted.

---

# Migrations required

* **New migration:** `create_books_table`.
* **Fields:** `id`, `user_id`, `name`, `isbn`, `value`, timestamps.
* **Foreign key:** `user_id` references users with cascade delete.
* **Indexes:** `(user_id, created_at)`.
* **No unique ISBN** and no extra domain tables.
* **Rollback:** drop `books`.
* **Compatibility:** SQLite and MySQL portable types.

---

# Risks

| Risk | Impact | Mitigation |
| ---- | ------ | ---------- |
| Ownership leak through an unscoped query. | High | Every query starts from `$user->books()`; service and cross-user tests. |
| Client changes ownership via mass assignment. | High | `StoreBookRequest` drops `user_id`; model only fillable book fields. |
| Foreign book detail reveals existence. | High | Service scoped lookup returns 404. |
| ISBN loses leading zeros. | Medium | Store as string and test a leading-zero value. |
| Decimal precision or shape drifts. | Medium | `decimal(12,2)` cast and resource contract tests. |
| Pagination order is unstable. | Medium | Order by `created_at` desc then `id` desc; test page boundaries. |
| Migration is not MySQL-portable. | Medium | Use portable column types and verify foreign key/index definitions. |
| Service grows into a god service. | Low | Only create/list/detail methods; split later if the controller grows. |
| Route binding bypasses ownership. | Medium | Use the service with the raw identifier, not implicit book binding. |
| Shared error envelope is still pending. | Low | Follow the current JSON contract; 004 hardens it. |

---

# Technical dependencies

* `001-auth-minimum` complete.
* ADR-008 service-layer pattern.
* Laravel 12.70.1, PHP 8.2.12, SQLite, PHPUnit, Pint.
* No new packages.
* No external services.

---

# Implementation order

1. Create the migration, `Book` model, `User::books()` relationship, and `BookFactory`.
2. Implement `BookService` with owner-scoped create, paginate, and find methods.
3. Add `StoreBookRequest`, `BookResource`, `BookController`, and protected routes.
4. Implement and test creation.
5. Implement and test listing/pagination/order.
6. Implement and test detail/404 ownership behavior.
7. Update README/API examples and run the full quality gate.

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
