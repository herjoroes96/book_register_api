# Feature Implementation Plan

# 004 · API Contract Hardening

## General information

Related feature:

```text
docs/features/004-api-contract-hardening/spec.md
```

Status:

```text
completed
```

---

# Technical objective

Formalize one JSON error contract across the existing API and add general abuse protection without
changing business behavior:

* normalize the error envelope for validation, authentication, not-found, method-not-allowed,
  throttling, and server errors;
* add a general API limiter (60/minute) keyed by authenticated user or public IP;
* preserve the stricter login limiter (5/minute by email + IP);
* expose rate-limit headers and `Retry-After` on throttled responses;
* verify token expiration and successful response compatibility;
* cover the contract with feature tests and update the README.

No new endpoints, database changes, packages, UI, or business rules are introduced.

---

# Architecture analysis

## Current architecture

The API already has:

* `ForceJsonResponse` at the start of the API middleware group;
* `shouldRenderJsonWhen` for `/api/*`;
* controller-level JSON responses for authentication and book operations;
* a named `login` limiter at 5 attempts/minute by normalized email + IP;
* seven-day Sanctum token expiration.

Validation, authentication, framework 404/405, throttling, and unexpected failures still do not
share one explicitly tested envelope. There is no general API limiter.

## Architectural impact

* **Backend:** API exception rendering, rate-limit definitions, route middleware.
* **Database:** none.
* **Frontend:** none.
* **Infrastructure:** none; the existing cache store backs limiter counters.
* **Tests:** new cross-cutting contract test plus targeted updates to existing auth tests.
* **Documentation:** README, context, roadmap, and feature closure records.

## Architectural decisions

| ID | Decision | Motive | Alternatives | Consequence |
| -- | -------- | ------ | ------------ | ----------- |
| D-001 | Centralize API exception rendering in `bootstrap/app.php` with one callback that handles known HTTP/validation/auth exceptions and a safe fallback. | Keeps error shaping in the shared HTTP boundary and avoids per-controller duplication. | Per-controller responses; custom middleware for every exception; a new exception layer. | One contract path; controllers stay HTTP-only and existing success behavior is untouched. |
| D-002 | Use a single `api` limiter at 60 requests/minute, keyed by authenticated user ID or public IP. | Matches the spec, protects the whole existing API, and isolates clients. | Per-route limits; a limiter per controller; a global limit that also counts login. | One predictable policy; login keeps its stricter own limiter. |
| D-003 | Apply the general limiter through a global API middleware before routing; resolve the Sanctum user inside the limiter for user keys and use IP otherwise; skip login, which keeps `throttle:login`. | The public limit must also cover unauthenticated `401` responses and unknown `404` routes, while authenticated requests still key by user. | Route-level throttle after auth (misses 401/404); throttle before auth without resolving the user (keys everyone by IP); double-limit login. | All non-login API traffic is limited; authenticated requests use user keys; login remains 5/minute. |
| D-004 | Return a custom JSON 429 through `Limit::response`, preserving limiter headers. | Keeps the envelope and `Retry-After` consistent without a second exception path. | Let Laravel render the default throttle body; add a separate throttle exception renderer. | 429 has the shared envelope and standard headers. |
| D-005 | Handle unexpected failures with a safe generic 500 JSON response and preserve reporting. | The contract must not expose stack traces or internals. | Leave debug rendering enabled; add a test-only failure route. | Safe client response; tests exercise the renderer with a sentinel exception. |
| D-006 | Keep existing controller 404 messages; normalize framework 404/405 messages only. | Minimizes breaking changes while making the envelope consistent. | Rewrite every 404 message globally. | Clients keep the same book-specific 404 text; all errors share the same structural rules. |
| D-007 | Make no schema, dependency, or business-rule changes. | This is contract hardening, not a product expansion. | Add error-code tables, request IDs, or new monitoring packages. | Small, reversible change with no data migration. |

---

# Error envelope contract

All errors are JSON and contain `message`. Validation errors also contain `errors`. No error
contains `data`.

| Status | Message | Additional fields |
| ------ | ------- | ----------------- |
| `401` | `Unauthenticated.` | None |
| `404` | Existing controller message or `Resource not found.` for framework 404s | None |
| `405` | `Method not allowed.` | None |
| `422` | `The given data was invalid.` | `errors` keyed by field |
| `429` | `Too many requests.` | Rate-limit headers and `Retry-After` |
| `500` | `Server error.` | None |

The exact success payloads remain unchanged:

* single resources: `{ "data": ... }`;
* paginated resources: `{ "data": [...], "meta": {...}, "links": {...} }`;
* deletes: `{ "message": "Book deleted." }`.

---

# Implementation strategy

1. Add the centralized API exception renderer and preserve safe reporting.
2. Define the general `api` limiter and shared throttled JSON response.
3. Register a global API limiter middleware that runs before routing, resolves Sanctum for user
   keys, skips login, and covers `401`/`404` traffic; keep the login limiter unchanged.
4. Add cross-cutting contract tests, including rate-limit isolation and a safe 500.
5. Update README and close out docs, roadmap, and context.
6. Run the full quality gate and a manual contract flow.

---

# Components affected

## `bootstrap/app.php`

Path:

```text
bootstrap/app.php
```

Responsibility:

```text
Application bootstrap, API routing, middleware, and exception configuration.
```

Expected change:

```text
Add the centralized API exception renderer for validation, authentication, 404, 405, and safe 500
responses. Keep shouldRenderJsonWhen and ForceJsonResponse.
```

## `app/Providers/AppServiceProvider.php`

Path:

```text
app/Providers/AppServiceProvider.php
```

Responsibility:

```text
Application service configuration and named rate limiters.
```

Expected change:

```text
Add the `api` limiter at 60/minute keyed by authenticated user or IP. Add a shared JSON 429
response to both `api` and `login` limiters. Keep the login key at normalized email + IP.
```

## `routes/api.php`

Path:

```text
routes/api.php
```

Responsibility:

```text
Versioned API route definitions and middleware composition.
```

Expected change:

```text
Keep the versioned routes and `auth:sanctum` on protected routes. The general limiter is applied
by `ThrottleApiRequests` globally, not per route; login remains on `throttle:login`.
```

## `tests/Feature/ApiContractTest.php`

Path:

```text
tests/Feature/ApiContractTest.php
```

Responsibility:

```text
Cross-cutting API contract coverage.
```

Expected change:

```text
New feature test for error envelopes, 404/405, no-Accept JSON, login/API/public throttling, rate-limit
isolation, token expiration, safe 500, and success compatibility.
```

## `tests/Feature/AuthTest.php`

Path:

```text
tests/Feature/AuthTest.php
```

Responsibility:

```text
Authentication contract coverage.
```

Expected change:

```text
Update or extend throttle/expiration assertions so the new envelope and headers are explicit.
```

## `README.md`

Path:

```text
README.md
```

Responsibility:

```text
Public setup, API, and behavior documentation.
```

Expected change:

```text
Document the error envelope, status codes, login/public/authenticated limits, headers, retry
behavior, and the fact that success payloads are unchanged.
```

---

# New components

## `ThrottleApiRequests`

Name:

```text
ThrottleApiRequests
```

Location:

```text
app/Http/Middleware/ThrottleApiRequests.php
```

Responsibility:

```text
Apply the general API limiter to all non-login /api/* requests, including unauthenticated
protected requests and unknown routes, while preserving per-user keys for authenticated traffic.
```

## `ApiContractTest`

Name:

```text
ApiContractTest
```

Location:

```text
tests/Feature/ApiContractTest.php
```

Responsibility:

```text
Protect the cross-cutting HTTP contract and rate-limit behavior from regressions.
```

No new production class is required; the behavior belongs to the existing HTTP boundary and
rate-limiter configuration.

---

# Data model

## New entities

None.

## Existing changes

* No new tables, columns, indexes, or migrations.
* No changes to books, users, or personal access tokens.
* Limiter counters use the existing cache store and expire automatically.

---

# API / Contracts

No endpoint is added or removed.

## Error examples

Validation `422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "isbn": ["The isbn field must contain only digits."]
  }
}
```

Authentication `401`:

```json
{
  "message": "Unauthenticated."
}
```

Method not allowed `405`:

```json
{
  "message": "Method not allowed."
}
```

Throttled `429`:

```json
{
  "message": "Too many requests."
}
```

Safe server error `500`:

```json
{
  "message": "Server error."
}
```

## Rate limits

| Scope | Limit | Key |
| ----- | ----- | --- |
| Login | 5/minute | Normalized email + IP |
| Public API | 60/minute | IP |
| Authenticated API | 60/minute | Authenticated user ID |

Limited responses carry `X-RateLimit-Limit` and `X-RateLimit-Remaining`; throttled responses also
carry `Retry-After`.

---

# External integrations

None.

---

# Security

* The limiter resolves the authenticated user before route auth, so one user cannot exhaust
  another user's quota; public, unauthenticated `401`, and unknown-route `404` traffic is keyed by IP.
* Login remains email + IP and is excluded from the general limiter.
* Unknown identifiers still return 404; this feature does not expose ownership data.
* Error rendering never includes stack traces, SQL, file paths, credentials, tokens, or request
  bodies.
* Unexpected failures are reported server-side but return only a generic 500 message.
* No secrets or new environment variables are introduced.
* Rate limits do not mutate business data.

---

# Performance

* One rate-limiter cache lookup per limited request.
* No extra database query for authenticated limits; the authenticated user is already resolved.
* No change to list pagination or database access.
* The existing file cache store is sufficient for the assessment; no new infrastructure.

---

# Tests required

## Unit tests

* No new pure unit cases; the behavior is HTTP and limiter integration.
* Configuration of the dummy hash is explicitly out of scope for `004`.

## Integration tests

* Validation, authentication, 404, 405, 429, and 500 responses use the expected JSON shape.
* Unmatched API routes and requests without `Accept` still return JSON.
* Login remains limited to 5 attempts/minute with `Retry-After`.
* Public health, unauthenticated protected requests, and unknown API routes are limited to
  60/minute per IP.
* Authenticated API is limited to 60/minute per user; another user is unaffected.
* Throttled requests perform no business mutation.
* Expired token returns the 401 envelope.
* Existing 200/201 success and pagination shapes remain unchanged.
* A sentinel `RuntimeException` rendered through the API exception handler returns a safe 500
  without the internal message.

Rate-limit tests must clear or isolate limiter keys so cache state cannot contaminate tests.

## Manual tests

* Send a validation failure, missing token, malformed token, unknown route, and wrong method.
* Exhaust login throttle and confirm 429 plus `Retry-After`.
* Exhaust a test user's general limit and confirm another user remains available.
* Confirm successful login and book operations still return their existing success shapes.

---

# Migrations required

None. No database changes.

---

# Risks

## Risk — Broad exception renderer hides or misclassifies errors

Description:

```text
A catch-all 500 renderer could mask expected 404/405/validation behavior or lose reporting.
```

Impact:

```text
High
```

Mitigation:

```text
Match known exception types first, keep a generic fallback only for unexpected failures, report the
exception, and test each status.
```

## Risk — Rate limiter keyed before authentication

Description:

```text
If throttle runs before auth:sanctum, all protected requests count by IP and the per-user isolation
fails.
```

Impact:

```text
High
```

Mitigation:

```text
Order middleware as auth:sanctum then throttle:api and test two users with separate counters.
```

## Risk — Missing throttle headers or Retry-After

Description:

```text
The custom 429 envelope may omit headers supplied by the limiter.
```

Impact:

```text
Medium
```

Mitigation:

```text
Build the JSON response from the headers passed to Limit::response and assert them in tests.
```

## Risk — Limiter state contaminates tests

Description:

```text
The cache-backed limiter can carry counters between tests.
```

Impact:

```text
Medium
```

Mitigation:

```text
Use distinct users/IPs or clear the relevant keys in test setup/teardown.
```

## Risk — Validation message change affects clients

Description:

```text
Normalizing all validation messages to one summary changes the current message text.
```

Impact:

```text
Low
```

Mitigation:

```text
Keep field-level errors unchanged, document the stable envelope, and assert both message and errors.
```

## Risk — 500 test requires a forced failure

Description:

```text
There is no production failure endpoint, and adding a test-only route would pollute the API.
```

Impact:

```text
Medium
```

Mitigation:

```text
Exercise the exception renderer directly with a sentinel `RuntimeException`; `BookService` is
`final`, so it cannot be mocked through the container type hint. Do not add a test route.
```

---

# Technical dependencies

* Existing Laravel 12.70.1 exception handling and `RateLimiter`.
* Existing `ForceJsonResponse` and `shouldRenderJsonWhen`.
* Sanctum and the current auth middleware order.
* Existing cache store for limiter counters.
* No new Composer package, database migration, or external service.

---

# Implementation order

1. Add the centralized API exception renderer and safe 500 handling.
2. Define the general and throttled-response behavior in `AppServiceProvider`.
3. Register the global limiter middleware and keep login on its specific limiter.
4. Add cross-cutting contract tests and adjust existing auth assertions.
5. Update README.
6. Run Pint, tests, optimize/clear, and the manual contract flow.
7. Close docs, context, roadmap, and known issues.

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
