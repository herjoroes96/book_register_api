# 003 · Books Update, Delete — Tasks

Reference: `spec.md` · `plan.md`

Feature status: Completed

> Converted from the approved plan into small, ordered, verifiable steps.
> **One task format:** `TASK-NNN`. Implement one task at a time (prompt 06).
> At the end of each task, pass the Definition of Done (lint/test/build).
> Commit only when the human explicitly asks; if asked, use `TASK-NNN: <what changed>`.

---

## Implementation objective

An authenticated user can partially update or permanently delete one of their own books. Omitted
fields remain unchanged, empty updates are rejected, ownership is enforced, foreign/missing books
return 404, and the full behavior is covered by service and HTTP tests.

No new migration, soft delete, audit, bulk operation, search, public catalog, UI, Docker, or CI/CD
is added.

---

## Phase order

1. Service update → 2. Service delete → 3. PATCH endpoint → 4. DELETE endpoint →
   5. Documentation and DoD

Never start a task before its dependencies are complete.

---

## Tasks

### TASK-001 · Add owner-scoped update to BookService

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `BookService::updateForUser(User $user, int $bookId, array $data): Book`.
  Resolve the book through `findForUser`, update only the supplied validated fields, refresh the
  model, and return it. Add service tests for single-field, multi-field, owner immutability,
  duplicate/leading-zero ISBN, and foreign/missing not-found behavior.
- **Files / location:** `app/Services/BookService.php`,
  `tests/Feature/Services/BookServiceTest.php`.
- **Dependencies:** None; `002-books-create-list-show` is complete.
- **Validation:** A single-field update preserves omitted fields; a multi-field update works; the
  owner never changes; duplicate/leading-zero ISBN works; foreign/missing updates throw not-found
  and change nothing; `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize`
  pass.
- **Completion:** 2026-10-26 — added owner-scoped `updateForUser` and five service tests for
  partial updates, owner immutability, duplicates/leading zeros, and foreign/missing books. Full
  suite passed (42 tests, 217 assertions). DoD passed.
- **Note:** HTTP exposure was completed in TASK-003.

### TASK-002 · Add owner-scoped delete to BookService

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Add `BookService::deleteForUser(User $user, int $bookId): void`. Resolve the
  book through `findForUser` and delete it. Add service tests for an owned book, a foreign book,
  and a missing book.
- **Files / location:** `app/Services/BookService.php`,
  `tests/Feature/Services/BookServiceTest.php`.
- **Dependencies:** TASK-001 (same service file; serialize the work).
- **Validation:** An owned book is removed; a foreign book throws not-found and remains intact; a
  missing book throws not-found; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added owner-scoped `deleteForUser` and three service tests for
  owned, foreign, and missing books. Full suite passed (45 tests, 221 assertions). DoD passed.
- **Note:** HTTP exposure was completed in TASK-004.

### TASK-003 · Add partial update request and PATCH endpoint

- **Status:** [x] Completed
- **Description:** Add `UpdateBookRequest` with optional name/ISBN/value rules and an
  after-validation check requiring at least one updatable field. Add `BookController@update` and
  the protected `PATCH /api/v1/books/{book}` route. Map not-found to a generic 404. Add HTTP tests
  for partial/multi-field updates, empty update, invalid fields, owner spoofing, duplicate and
  leading-zero ISBN, foreign/missing books, unauthenticated access, and secret safety.
- **Files / location:** `app/Http/Requests/Books/UpdateBookRequest.php`,
  `app/Http/Controllers/Api/V1/BookController.php`, `routes/api.php`,
  `tests/Feature/BookTest.php`.
- **Dependencies:** TASK-001.
- **Validation:** `PATCH` returns `200` and changes only supplied fields; empty update returns
  `422`; invalid fields return `422`; owner remains unchanged when `user_id` is supplied; duplicate
  and leading-zero ISBN are accepted; foreign/missing updates return `404`; unauthenticated
  updates return `401`; responses expose no sensitive fields; `./vendor/bin/pint --test`,
  `php artisan test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `UpdateBookRequest`, `BookController@update`, and the protected
  `PATCH /api/v1/books/{book}` route with generic 404 handling. Added eight HTTP tests covering
  partial/multi-field updates, empty and invalid updates, owner spoofing, duplicate and leading-zero
  ISBN, foreign/missing books, unauthenticated access, and sensitive-field safety. Full suite passed
  (53 tests, 265 assertions). DoD passed.
- **Note:** DELETE was completed in TASK-004.

### TASK-004 · Add the DELETE endpoint

- **Status:** [x] Completed
- **Description:** Add `BookController@destroy` and the protected
  `DELETE /api/v1/books/{book}` route. Delegate to `BookService::deleteForUser`, map not-found to
  the generic 404, and return a JSON success message. Add tests for owned deletion, disappearance
  from list/detail, foreign/missing deletion, unauthenticated access, and data safety.
- **Files / location:** `app/Http/Controllers/Api/V1/BookController.php`, `routes/api.php`,
  `tests/Feature/BookTest.php`.
- **Dependencies:** TASK-002, TASK-003.
- **Validation:** Deletion returns `200` with a JSON message; the book is absent from the database,
  list, and detail; foreign/missing deletion returns `404`; the foreign book remains; unauth
  deletion returns `401`; `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — added `BookController@destroy` and the protected
  `DELETE /api/v1/books/{book}` route with generic 404 handling and a `{"message":"Book deleted."}`
  success response. Added four HTTP tests covering owned deletion, absence from list/detail,
  foreign/missing deletion, unauthenticated access, and foreign-book data safety. Full suite passed
  (57 tests, 282 assertions). DoD passed.
- **Note:** Feature closure was completed in TASK-005.

### TASK-005 · Document update/delete and run the final quality gate

- **Status:** [x] Completed
- **Description:** Update the README with PATCH and DELETE examples, partial-update rules,
  empty-update rejection, ownership behavior, hard deletion, and troubleshooting. Run a manual
  authenticated create → update → delete flow and the full quality gate.
- **Files / location:** `README.md`.
- **Dependencies:** TASK-001 through TASK-004.
- **Validation:** README examples work with a real token; update returns `200`; empty/invalid
  update returns `422`; foreign/missing update/delete returns `404`; delete removes the book from
  list/detail; unauth access returns `401`; `composer validate --strict`,
  `./vendor/bin/pint --test`, `php artisan test`, `php artisan optimize`, and
  `php artisan optimize:clear` pass.
- **Completion:** 2026-10-26 — updated the README with PATCH/DELETE examples, partial-update and
  empty-update rules, ownership, hard-delete behavior, and troubleshooting. The real HTTP flow
  login → create → update → list/detail → delete → 404/list absence → logout passed, and the local
  database was left with `books=0`, `tokens=0`, `users=1`. `composer validate --strict`, Pint, the
  full suite (57 tests, 282 assertions), `optimize`, and `optimize:clear` passed.

---

## Feature Definition of Done

Before marking the feature complete:

- [x] All TASK items are Completed.
- [x] The acceptance criteria in `spec.md` are satisfied.
- [x] `architecture.md` and `conventions.md` are respected.
- [x] Update/delete rules and ownership live in `BookService`; controllers are HTTP-only.
- [x] Lint, tests, and build pass using the commands in `tech-stack.md`.
- [x] No soft delete, bulk operations, search, public catalog, UI, Docker, or CI/CD work was added.
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

**Reference:** code review findings for `003-books-update-delete`

**Files:** `docs/features/003-books-update-delete/plan.md`,
`docs/features/003-books-update-delete/spec.md`, `README.md`, `tests/Feature/BookTest.php`.

Changes:

* Marked the implementation plan as `completed`.
* Added update boundary tests for name 255/256, ISBN 20/21, and the maximum value.
* Added a test proving `user_id` alone is rejected as an empty update and the owner remains
  unchanged.
* Added a test fixing the precedence: invalid update payloads return `422` before the ownership
  lookup for foreign/missing books; valid payloads still return `404`.
* Clarified the validation/404 precedence in the README and feature spec.

**Validation:** `php artisan test` passed (66 tests, 348 assertions); `BookTest` passed (29 tests,
194 assertions); Pint passed; `optimize` and `optimize:clear` passed. No runtime behavior changed.

---

## Agent rules

1. Implement one task at a time. Mark real progress, not optimistic progress.
2. Report modified files and executed validations with actual results.
3. Do not skip tasks or expand the scope silently.
4. If the plan proves wrong, stop, explain, and propose an update. Do not patch around it.
5. Commit or push only when the human explicitly asks; use `TASK-NNN: <what changed>` when asked.
