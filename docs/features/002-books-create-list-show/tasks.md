# 002 · Books Create, List, Show — Tasks

Reference: `spec.md` · `plan.md`

Feature status: Completed

> Converted from the approved plan into small, ordered, verifiable steps.
> **One task format:** `TASK-NNN`. Implement one task at a time (prompt 06).
> At the end of each task, pass the Definition of Done (lint/test/build).
> Commit only when the human explicitly asks; if asked, use `TASK-NNN: <what changed>`.

---

## Implementation objective

An authenticated user can create a valid book, list only their own books with fixed pagination,
and view one of their own books. Ownership is enforced in `BookService`, invalid data is rejected,
and foreign or missing books return 404. No update, delete, search, or public access is added.

---

## Phase order

1. Schema and model → 2. Service → 3. Create → 4. List → 5. Detail → 6. Documentation and DoD

Never start a task before its dependencies are complete.

---

## Tasks

### TASK-001 · Create the books schema, model, factory, and relationships

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add the `books` migration with owner, name, ISBN, value, timestamps, and list
  indexes. Add the `Book` model with fillable fields, decimal cast, owner relation, and factory.
  Add `User::books()`.
- **Files / location:** `database/migrations/*_create_books_table.php`, `app/Models/Book.php`,
  `app/Models/User.php`, `database/factories/BookFactory.php`.
- **Dependencies:** None; `001-auth-minimum` is complete.
- **Validation:** `php artisan migrate` creates `books`; `php artisan migrate:status` shows it as
  applied; the table has `user_id`, `name`, `isbn`, `value`, and timestamps; factory-created books
  belong to a user; existing tests pass; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — created the `books` table with both owner/list indexes, the `Book`
  model and decimal cast, `User::books()`, and `BookFactory`. Factory ownership verified; 18
  tests and 78 assertions passed; DoD passed.
- **Note:** No book endpoints or business behavior yet.

### TASK-002 · Implement BookService and service tests

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Create `BookService` with owner-scoped `paginateForUser`, `createForUser`, and
  `findForUser`. Add service tests for owner assignment, list isolation/order/pagination, detail
  lookup, foreign/missing 404 behavior, and duplicate ISBN acceptance.
- **Files / location:** `app/Services/BookService.php`,
  `tests/Feature/Services/BookServiceTest.php`.
- **Dependencies:** TASK-001.
- **Validation:** Service tests pass; a created book belongs to the authenticated user; lists
  return only that user's books at 15 per page with stable order; foreign/missing detail throws
  not-found; duplicate ISBNs are accepted; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `BookService` with owner-scoped create/paginate/find and six
  service tests (31 assertions). Full suite passed (24 tests, 109 assertions). DoD passed.
- **Note:** No HTTP endpoints yet.

### TASK-003 · Add book creation with validation

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `StoreBookRequest`, `BookResource`, `BookController@store`, and the
  protected `POST /api/v1/books` route. Delegate creation to `BookService`. Add HTTP tests for
  success, required fields, non-digit ISBN, invalid value, leading-zero ISBN, duplicate ISBN,
  ignored client `user_id`, unauthenticated access, and secret safety.
- **Files / location:** `app/Http/Requests/Books/StoreBookRequest.php`,
  `app/Http/Resources/BookResource.php`,
  `app/Http/Controllers/Api/V1/BookController.php`, `routes/api.php`,
  `tests/Feature/BookTest.php`.
- **Dependencies:** TASK-001, TASK-002.
- **Validation:** Valid creation returns `201` with the public book shape; missing/invalid fields
  return `422`; non-digit ISBN returns `422`; leading zeros are preserved; duplicate ISBNs are
  accepted; client `user_id` does not change ownership; unauthenticated access returns `401`;
  responses expose no password, token, or owner identifier; `./vendor/bin/pint --test`,
  `php artisan test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `StoreBookRequest`, `BookResource`, `BookController@store`,
  and protected `POST /api/v1/books`. Eight book-creation tests passed (49 assertions); full suite
  passed (32 tests, 158 assertions). DoD passed.

### TASK-004 · Add the paginated book list

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `BookController@index` and the protected `GET /api/v1/books` route.
  Delegate to `BookService::paginateForUser`, passing the requested page (default 1). Add tests
  for owner isolation, 15-item pagination, newest-first ordering with identifier tie-breaking,
  page beyond range, and unauthenticated access.
- **Files / location:** `app/Http/Controllers/Api/V1/BookController.php`, `routes/api.php`,
  `tests/Feature/BookTest.php`.
- **Dependencies:** TASK-002, TASK-003.
- **Validation:** The list returns only the authenticated user's books; a full page has 15
  records; ordering is `created_at` desc then `id` desc; a page beyond range returns empty data
  with pagination metadata; unauthenticated access returns `401`; no foreign book appears;
  `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `BookController@index` and protected `GET /api/v1/books`.
  List/pagination tests passed; full suite passed (34 tests, 189 assertions). DoD passed.

### TASK-005 · Add authenticated book detail with 404 ownership

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `BookController@show` and the protected `GET /api/v1/books/{book}` route.
  Resolve the raw identifier through `BookService::findForUser`. Add tests for an owned book,
  foreign book, missing book, unauthenticated access, and absence of owner data.
- **Files / location:** `app/Http/Controllers/Api/V1/BookController.php`, `routes/api.php`,
  `tests/Feature/BookTest.php`.
- **Dependencies:** TASK-002, TASK-003.
- **Validation:** An owned book returns `200` with the public shape; a foreign book returns
  `404`; a missing book returns `404`; unauthenticated access returns `401`; no owner identifier
  or other user's data is exposed; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `BookController@show` and protected
  `GET /api/v1/books/{book}` with a generic 404 for foreign/missing books. Detail tests passed;
  full suite passed (37 tests, 207 assertions). DoD passed.

### TASK-006 · Document book endpoints and run the final quality gate

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Update the README with the create/list/detail endpoints, request/response
  examples, validation rules, pagination behavior, ownership rules, and troubleshooting. Run the
  full quality gate and a manual authenticated create/list/detail flow.
- **Files / location:** `README.md`.
- **Dependencies:** TASK-001 through TASK-005.
- **Validation:** README examples work with a real token; create returns `201`; list is paginated
  and owner-scoped; detail returns the book; foreign/missing detail returns `404`; validation
  returns `422`; unauthenticated access returns `401`; `composer validate --strict`,
  `./vendor/bin/pint --test`, `php artisan test`, `php artisan optimize`, and
  `php artisan optimize:clear` pass.
- **Completion:** 2026-10-26 — README book docs added; live login → create → list → detail → 404 →
  logout flow verified; final DoD passed (37 tests, 207 assertions).

---

## Feature Definition of Done

Before marking the feature complete:

- [x] All TASK items are Completed.
- [x] The acceptance criteria in `spec.md` are satisfied.
- [x] `architecture.md` and `conventions.md` are respected.
- [x] Book rules and ownership live in `BookService`; controllers are HTTP-only.
- [x] Lint, tests, and build pass using the commands in `tech-stack.md`.
- [x] No update, delete, search, filters, public catalog, UI, Docker, or CI/CD work was added.
- [x] No unnecessary debt remains; any known issue is recorded in
  `docs/memory/known-issues.md`.

---

## Closing the feature

1. `spec.md` → status `implemented`.
2. `roadmap.md` → move the feature to *Completed features*.
3. `docs/memory/learnings.md` → record what worked, what did not, and what to improve.
4. `docs/memory/decisions.md` → add an ADR if an impactful decision was made.
5. Update `docs/project/context.md` with the new state.

---

## Post-completion review remediation (2026-10-26)

**Status:** Completed

**Reference:** code review findings for `002-books-create-list-show`

**Files:** `routes/api.php`, `bootstrap/app.php`, `tests/Feature/BookTest.php`,
`tests/Feature/Services/BookServiceTest.php`,
`database/migrations/2026_10_26_230000_drop_redundant_books_user_id_index.php`,
`docs/features/002-books-create-list-show/plan.md`.

Changes:

* Restricted `GET`, `PATCH`, and `DELETE` book routes to numeric identifiers; `/books/4.5` and
  `/books/not-a-number` now return 404 instead of resolving `(int)`.
* Ensured unmatched `/api/*` exceptions render JSON as well, covering 404s that occur before the
  API middleware group.
* Added boundary tests for name (255/256), ISBN (20/21), value (maximum/accepted/rejected), and a
  service test for `created_at` ties resolved by `id` descending.
* Removed the redundant `books_user_id_index`; the final schema keeps
  `books_user_id_created_at_index`.

**Validation:** `php artisan test` passed (64 tests, 327 assertions); BookTest + BookServiceTest
passed (42 tests, 219 assertions); Pint passed; local migration applied and the final `books`
indexes are only `books_user_id_created_at_index`; `optimize` and `optimize:clear` passed.

---

## Agent rules

1. Implement one task at a time. Mark real progress, not optimistic progress.
2. Report modified files and executed validations with actual results.
3. Do not skip tasks or expand the scope silently.
4. If the plan proves wrong, stop, explain, and propose an update. Do not patch around it.
5. Commit or push only when the human explicitly asks; use `TASK-NNN: <what changed>` when asked.
