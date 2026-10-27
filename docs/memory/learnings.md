# Learnings

> What worked, what did not, and what we would do differently. Filled when closing features
> (prompt 06/07) and when something surprises us during development. The goal is for each project
> to improve the next one and for reusable lessons to move into `aipf/knowledge/`.

---

## 2026-10-26 · 000-setup-base · Setup foundation

**Situation:** Building the first slice of the Book Registry API: install Laravel, configure
SQLite, expose a health check, and establish the quality gates without adding business logic.

**What happened / what we learned:**

* Scaffolding Laravel in a temporary directory and merging the skeleton into the repository
  preserved `docs/`, `aipf/`, `AGENTS.md`, `.aifp.json`, and `.opencode/`. This avoided
  overwriting project documentation.
* An invokable health controller kept `php artisan optimize` working; route closures would have
  broken route caching.
* The health endpoint checks the database and returns a safe `503` without exposing details.
  The failure path was verified with an invalid SQLite path.
* `phpunit.xml` already used in-memory SQLite; `RefreshDatabase` plus `HealthCheckTest` verified
  the public 200 JSON contract without an external database.
* Laravel 12 defaults to database-backed cache/session/queue. Before migrations,
  `php artisan optimize:clear` failed with `no such table: cache`. File cache/session and sync
  queue fixed the build cleanup and are recorded in ADR-001.
* The default `DatabaseSeeder` created a Test User. The feature explicitly forbids demo data, so
  the seeder was emptied before running migrations.
* `composer setup` did not create the SQLite file. The script was updated to create it before
  `php artisan migrate`, and the setup was verified end to end.
* The first `composer install` exceeded the command wrapper timeout after installing everything;
  a second run reported "Nothing to install" and confirmed the environment was complete.

**How to apply it next time:**

* Scaffold in a temporary directory and review the merge for overwritten project files.
* Inspect framework default seeders whenever a feature forbids demo data.
* Run the build gate early: route closures and database-backed infrastructure drivers surface
  problems before business code exists.
* Keep the SQLite file creation in setup instructions and automation for fresh clones.
* Treat command-wrapper timeouts as unknown until a follow-up command confirms the real state.

**Move to knowledge?** Not yet. These are project-specific setup lessons; ADR-001 records the
driver decision.

## 2026-10-26 · 001-auth-minimum · Authentication walking skeleton

**Situation:** Adding the minimum auth slice: Sanctum, a documented demo user, login, profile, logout, token expiration, and login throttling.

**What happened / what we learned:**

* Installing Sanctum with Composer and publishing the `sanctum-config` and `sanctum-migrations` tags avoided `install:api`, which could have touched the existing API routing.
* The demo seeder used `updateOrCreate` by email, so re-seeding kept exactly one account and preserved the documented credentials.
* Manual `Hash::check` plus a shared generic `401` avoided session side effects and user enumeration.
* The named `login` limiter (5/minute by lowercased email + IP) worked with route middleware and was verified at the sixth attempt.
* Logout deleted only the current token; the two-token test proved other sessions stay valid.
* Feature tests reuse the application instance, so the auth guard cached the authenticated user between requests. Calling `Auth::forgetGuards()` after logout was necessary to model separate HTTP clients and verify the revoked token.
* `travel(8)->days()` tested the 7-day expiration without waiting, and `travelBack()` restored the clock.
* The live curl flow (login → profile → logout → `401`, health `200`) confirmed the documented contract; the token count returned to zero after logout.

**How to apply it next time:**

* Reset auth guards in revocation tests when a test client reuses the application.
* Use time travel for time-based token expiration.
* Keep demo credentials test-only and documented; verify re-seeding is idempotent.
* Test generic authentication errors and secret absence explicitly.
* Run the real HTTP flow after the automated suite for the README example.

**Move to knowledge?** The guard-reset testing pattern is reusable, but keep it here until a second authentication project confirms it.

## 2026-10-26 · Architecture · Adopt one service per business controller

**Situation:** After `001-auth-minimum`, the project adopted a service layer per business controller instead of leaving business logic in controllers.

**What happened / what we learned:**

* ADR-008 superseded architecture ADR-004. The new rule is `app/Services`, one service per business controller, with Form Requests for validation and API Resources for HTTP output.
* Services own business rules, ownership, transactions, and token operations. They return models, paginators, or arrays and never HTTP responses.
* No repository and no Actions pattern are kept as parallel abstractions.
* The existing `AuthController` predated the decision; AUTH-001 retrofitted it to `AuthService` before `002`.
* Documentation had to change in architecture, tech stack, conventions, constraints, mission, context, README, roadmap, analysis, and memory; leaving any “no service layer” statement would have made the decision look like a patch.

**How to apply it next time:**

* Put the decision in an ADR and mark the previous decision superseded.
* Search the whole docs tree for contradictory phrases, not just the architecture file.
* Align the existing code or record an explicit retrofit with a plan and a known-issue entry.
* Use service-level tests for business rules and HTTP feature tests for the contract.

**Move to knowledge?** Not yet. The decision is project-specific; revisit if a second project adopts the same service boundary.

## 2026-10-26 · 002-books-create-list-show · First book slice

**Situation:** Building owner-scoped book creation, paginated listing, and detail on top of the service layer.

**What happened / what we learned:**

* `BookService` kept ownership, pagination, and lookup rules out of the controller; service tests covered them directly.
* Deriving the owner from `$user->books()` and exposing only public resource fields removed mass-assignment and owner-spoofing risk.
* A raw route identifier resolved through the service produced a consistent generic 404 for foreign and missing books.
* The HTTP tests covered validation, leading-zero ISBN, duplicate ISBN, pagination, page boundaries, ownership, and authentication.
* The manual flow (login → create → list → detail → 404 → logout) passed; the local test book and token were cleaned up.
* Pint repeatedly detected line endings in files written by scripts; running the formatter after scripted edits kept the gate green.

**How to apply it next time:**

* Keep service methods owner-scoped and HTTP-free; map not-found in the controller.
* Test service rules and HTTP contracts separately.
* Reuse the same create/list/detail pattern for update/delete without adding repository or Actions.
* Run Pint after scripted file edits rather than waiting for the final DoD.

**Move to knowledge?** Not yet; the pattern is project-specific, but it is a good template for the next book slice.

## 2026-10-26 · 003-books-update-delete · Book update and hard delete

**Situation:** Completing the book CRUD lifecycle with partial updates, permanent deletion, HTTP tests, README examples, and a live verification flow.

**What happened / what we learned:**

* `UpdateBookRequest` used `sometimes` rules plus an after-validation check so an empty `PATCH` returned `422`, while `validated()` excluded client-supplied `user_id` and kept ownership immutable.
* Service tests covered update/delete ownership before the HTTP layer; the controller only mapped `ModelNotFoundException` to the generic `404`.
* The PATCH response reused `BookResource`, and the DELETE contract returned a small JSON message instead of a body.
* HTTP tests verified partial/multi-field updates, leading-zero and duplicate ISBN, foreign/missing books, unauthenticated access, deletion from list/detail, and data safety.
* The live flow on port 8001 passed (login → create → update → list/detail → delete → 404/list absence → logout), and cleanup left `books=0`, `tokens=0`, `users=1`.
* Script-created test files again acquired CRLF endings; running Pint before the final gate fixed them.

**How to apply it next time:**

* Validate partial updates against the presence of updatable fields, not against an empty request body alone.
* Keep ownership and mutation rules in the service; keep controllers limited to HTTP mapping.
* Verify deletion through both the database and the public list/detail endpoints.
* Run the real authenticated flow and confirm cleanup before closing the feature.
* Run Pint immediately after scripted edits.

**Move to knowledge?** Not yet; the pattern is project-specific and already follows ADR-008.

## 2026-10-26 · 001-auth-minimum · Review remediation

**Situation:** A production-minded review found that API error responses depended on the client sending `Accept: application/json`, plus a few low-severity security and consistency gaps.

**What happened / what we learned:**

* Laravel returns JSON validation/auth errors only when the request expects JSON. Without `Accept`, the auth middleware tried to redirect to a nonexistent login route, producing a 500, and validation returned a 302.
* A `ForceJsonResponse` middleware at the start of the API group fixed 401/422/429 behavior without changing the shared error envelope planned for 004.
* Tests using only `getJson`/`postJson` cannot catch missing-Accept regressions; explicit `get`/`post` calls are needed.
* The demo seeder is now a local/testing-only concern, preventing known credentials from being seeded into other environments.
* A dummy bcrypt check for unknown emails reduces the timing difference without changing the generic 401 contract.
* `Log::spy()` verified that the auth flow emits no password/token log entries.

**How to apply it next time:**

* Enforce the API response format at the HTTP boundary, not per controller.
* Test both the documented JSON client and the bare client.
* Guard seeders with environment checks when they create known credentials.
* Use a dummy hash for failed user lookups when generic auth errors are required.
* Assert log secrecy, not only response secrecy.

**Move to knowledge?** Not yet; keep the API-boundary JSON pattern in this project until another API confirms reuse.

## 2026-10-26 · 002-books-create-list-show · Review remediation

**Situation:** A code review found that the book detail route coerced malformed identifiers to integers, boundary ordering behavior was under-tested, and the books table carried a redundant owner index.

**What happened / what we learned:**

* Casting a route parameter with `(int)` before lookup makes `/books/4.5` resolve book 4. Route constraints (`whereNumber`) reject non-canonical identifiers before the controller runs.
* A malformed route does not enter the API middleware group, so `ForceJsonResponse` alone is not enough for unmatched API 404s. `shouldRenderJsonWhen` covers exceptions rendered before route middleware.
* The migration kept the original schema history and added a follow-up migration to drop `books_user_id_index`; the composite `(user_id, created_at)` index covers the owner prefix.
* Boundary tests (255/256 name, 20/21 ISBN, maximum value) and a shared-`created_at` tie test expose regressions that happy-path tests miss.
* When a review requires canonical input, test the malformed form explicitly rather than only the missing-ID case.

**How to apply it next time:**

* Constrain numeric route parameters at the route layer.
* Test malformed identifiers alongside missing and foreign identifiers.
* Keep API exception rendering independent of route middleware.
* Add boundary and tie-break tests to pagination and validation suites.
* Use a follow-up migration when changing an already-applied schema.

**Move to knowledge?** Not yet; keep these as project-specific API patterns until reused.

## 2026-10-26 · 003-books-update-delete · Review remediation

**Situation:** A review of the final book CRUD slice found a stale plan status and gaps in update-specific boundary and error-precedence tests.

**What happened / what we learned:**

* The plan remained `approved` after the feature closed; plan, spec, tasks, and roadmap statuses must be reconciled at closure.
* Create boundary tests do not protect update rules. `UpdateBookRequest` has its own `sometimes` rules, so name/ISBN/value boundaries and `user_id`-only payloads need their own tests.
* Validation intentionally runs before the ownership lookup. Invalid payloads return `422` even for foreign/missing books; valid payloads return `404`. This behavior was true but undocumented.
* A test now fixes that precedence so a future reordering cannot change the contract silently.
* No runtime code changed; only tests and documentation.

**How to apply it next time:**

* Update the plan status when closing a feature.
* Test validation rules at the request where they are defined, not only through a sibling endpoint.
* Document and test error precedence when validation and authorization can both fail.
* Treat stale plan status as a documentation defect, not a harmless leftover.

**Move to knowledge?** Not yet; keep it as a project closure checklist item.

## 2026-10-26 · 004-api-contract-hardening · Unified API contract

**Situation:** Hardening the existing API with one JSON error envelope, general rate limits, safe 500 responses, and cross-cutting contract tests.

**What happened / what we learned:**

* A single exception-render callback in `bootstrap/app.php` keeps the API error shape at the HTTP boundary and preserves controller-specific 404 messages.
* Expected HTTP exceptions must bypass the generic 500 fallback; otherwise 403/429/etc. would be misclassified. `HttpExceptionInterface` and `HttpResponseException` are excluded.
* The HTTP kernel reports exceptions before rendering, so the fallback does not double-report.
* `BookService` is `final`; a named limiter or book-service mock cannot be type-hinted in the controller. The 500 test exercises the exception handler directly with a sentinel exception.
* Rate limits must be keyed after authentication for per-user quotas. Laravel middleware priority places authentication before throttling even when both are listed in a route group.
* `Limit::response` preserves `Retry-After` and `X-RateLimit-*` headers while keeping the 429 envelope uniform.
* Sixty-request limit tests are practical but require clearing specific limiter keys so cache state does not contaminate other tests.

**How to apply it next time:**

* Keep cross-cutting error shaping in one HTTP-boundary renderer.
* Exclude expected HTTP exceptions from generic 500 handling.
* Test safe 500 through the handler when the dependency is `final`.
* Verify limiter key order (auth before throttle) with two-user isolation tests.
* Clear named limiter keys rather than flushing all cache in tests.

**Move to knowledge?** Not yet; these are project-specific API patterns.

## 2026-10-26 · 004-api-contract-hardening · Public rate-limit remediation

**Situation:** Review found that route-level `throttle:api` after `auth:sanctum` left unauthenticated `401` and unknown-route `404` traffic outside the public IP limit.

**What happened / what we learned:**

* Route middleware cannot count an unauthenticated `401` when authentication runs first.
* Unknown routes do not enter route middleware groups, so a route-level limiter cannot cover them.
* A global `ThrottleApiRequests` middleware that delegates to Laravel's named limiter covers both; the `api` limiter resolves Sanctum explicitly before route authentication to keep per-user keys.
* Login must be skipped by the global middleware so its 5/minute limiter remains the only policy.
* Laravel hashes named limiter keys with `md5($name.$key)`; test cleanup must clear the hashed key, not only the readable prefix.
* Live probes confirmed the fix: unauthenticated `/me` and unknown routes now transition from `401/404` to `429`.

**How to apply it next time:**

* Put cross-cutting rate limits before authentication if they must cover auth failures.
* Resolve optional identity inside the limiter, not by moving authentication earlier.
* Test rate limits for `401`, `404`, `429`, and authenticated isolation.
* Clear the hashed named-limiter key in tests.

**Move to knowledge?** Not yet; keep it as a project-specific rate-limit pattern.

## 2026-10-26 · 005-auth-dummy-hash-config · Configuration verification

**Situation:** Moving the unknown-email dummy hash from a service constant into authentication configuration.

**What happened / what we learned:**

* A valid `AUTH_DUMMY_PASSWORD_HASH` survives `config:cache` and is the value read at runtime.
* An invalid override fails configuration loading with a safe message that names the setting but not the value.
* `config:cache` validates configuration files at build time, so the check is useful in deployment and local verification.
* The service test exposed a short-circuit bug: `! $user || ! Hash::check(...)` skipped the dummy hash entirely for unknown emails. Assigning the hash check result before the user condition fixes it.
* Documentation must cover quoting bcrypt `$` characters in `.env`, cache rebuilds, hasher/cost alignment, and the partial nature of the mitigation.

**How to apply it next time:**

* Test the cache-built configuration, not only the uncached app.
* Fail fast on invalid security configuration; do not silently fall back.
* Verify that guards/conditions do not short-circuit the security work they are meant to equalize.
* Document the deployment steps for any environment-backed security value.

**Move to knowledge?** Not yet; keep as a project-specific configuration pattern.

## 2026-10-26 · 005-auth-dummy-hash-config · Hasher alignment remediation

**Situation:** Review found that the configuration validator accepted any recognized PHP hash, including bcrypt cost 4 and argon2id under a bcrypt cost-12 app.

**What happened / what we learned:**

* Format validation is not alignment validation. A valid hash with the wrong algorithm or cost can weaken the timing mitigation or fail under hasher verification.
* Validation now compares the hash algorithm, bcrypt cost, or Argon memory/time/threads against the configured hashing options.
* `phpunit.xml` uses `BCRYPT_ROUNDS=4`, so tests need a matching cost-4 dummy hash; production keeps the cost-12 default.
* Config-cache subprocess tests are useful for proving fail-fast behavior without adding test-only routes or production classes.
* The tests must assert the password hash was actually calculated; the earlier short-circuit bug was only caught because the test inspected `Hash::check`.

**How to apply it next time:**

* Validate security configuration against the actual runtime algorithm and work factor.
* Keep test-only hashes aligned with test hashing cost.
* Test invalid configuration through the real configuration loader/cache.
* Prefer behavior assertions that prove the security operation ran.

**Move to knowledge?** Not yet; retain as project-specific configuration hardening.
