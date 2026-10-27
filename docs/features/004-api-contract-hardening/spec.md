# Feature Specification

# 004 · API Contract Hardening

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

Hardens the existing API contract without adding a new business capability. Every API consumer
gets the same predictable JSON error shape, abuse protection across the API, verified token
expiration behavior, and documented status-code rules.

## Why it matters

Clients should not need special cases for validation errors, expired tokens, missing resources,
unsupported methods, throttling, or server failures. A stable contract reduces integration bugs
and makes the API easier to trust and review.

---

# Context

## Current situation

Authentication and book CRUD already work. Login has a specific throttle, tokens expire after
seven days, and the API returns JSON for the main error paths.

However, error handling is not yet exercised as one contract: validation, authentication,
not-found, throttling, method-not-allowed, and server errors can still differ in shape or lack
explicit regression coverage. There is also no general API rate limit.

## Problem

* API clients must handle response details that are not consistently specified.
* Abuse protection exists for login but not for the rest of the authenticated API.
* Token expiration and status-code behavior are implemented but not consolidated as one verified
  contract.
* The final delivery should be defensible in a live review, with no undocumented error behavior.

## Who it affects

* Any client consuming the API.
* The technical reviewer validating consistency and security.
* Future feature developers who need a stable error contract.

---

# Objective

Make all existing API operations follow one JSON-only response contract, with a consistent error
envelope, explicit status codes, general API rate limiting, verified token expiration, and
contract tests, without changing successful business behavior.

---

# Users involved

## Main user

Name:

```text
API consumer
```

Need:

```text
Predictable success and error responses, clear retry behavior, and no unexpected HTML or
implementation details.
```

---

## Secondary users

* Technical reviewer: verifies the contract, limits, and tests.
* Future feature developer: builds on a stable error and status-code contract.
* Demo user: uses the API without being affected by the hardening work.

---

# Scope

## Includes

* One consistent JSON error envelope for all API error responses.
* Validation errors with field-level messages.
* Authentication, not-found, method-not-allowed, throttling, and safe server-error responses.
* A general API rate limit of 60 requests per minute per authenticated user.
* A public API limit of 60 requests per minute per IP, while login keeps its stricter
  5-requests-per-minute email + IP limit.
* Standard rate-limit headers and `Retry-After` on throttled responses.
* Verified token-expiration behavior: expired credentials return the authentication error
  envelope.
* JSON responses even when the client omits `Accept: application/json`.
* Contract tests across existing authentication and book operations.
* Documentation of the error envelope, status codes, rate limits, and retry behavior.

## Does not include

* New endpoints or business features.
* Changes to book validation, ownership, pagination, creation, update, or deletion rules.
* Changes to authentication, token issuance, logout semantics, or token lifetime.
* Registration, password reset, roles, or account administration.
* Refresh tokens, token rotation, or session redesign.
* Custom machine-readable error codes, localization, or request correlation IDs.
* UI, OpenAPI, Docker, CI/CD, monitoring, or production deployment.
* The configurable dummy password hash (`005`) or final delivery documentation (`006`).

---

# Functional flow

## Successful request

```text
Client
    │
    ▼
Sends an authenticated API request
    │
    ▼
Rate limit and authentication checks pass
    │
    ▼
Existing business behavior runs
    │
    ▼
Client receives the existing JSON success shape
```

## Error request

```text
Client
    │
    ▼
Sends an API request
    │
    ├── validation fails → 422 envelope with errors
    ├── credential missing/invalid/expired → 401 envelope
    ├── resource missing or foreign → 404 envelope
    ├── method unsupported → 405 envelope
    ├── rate limit exceeded → 429 envelope + Retry-After
    └── unexpected server failure → safe 500 envelope
```

No error path returns HTML, stack traces, SQL details, file paths, credentials, tokens, or another
user's data.

---

# Business rules

| ID | Rule | Motive |
| -- | ---- | ------ |
| BR-01 | All API responses are JSON, including errors and unmatched API routes, regardless of the client's `Accept` header. | Clients must have one parsing path. |
| BR-02 | Every error response contains a non-empty `message` string; validation errors also contain an `errors` object keyed by field. | Provides a stable, human-readable contract. |
| BR-03 | Error responses never include a `data` payload or successful business data. | Prevents mixing success and failure semantics. |
| BR-04 | Validation failures return `422`; authentication failures return `401`; missing or foreign resources return `404`; unsupported methods return `405`; throttling returns `429`; unexpected failures return `500`. | Removes ambiguity about status codes. |
| BR-05 | Error messages never expose stack traces, SQL, file paths, credentials, tokens, or internal implementation details. | Protects security and implementation boundaries. |
| BR-06 | Successful `200`/`201` responses keep their existing `data`, `meta`, and `links` shapes. | Avoids breaking existing clients. |
| BR-07 | Login remains limited to 5 attempts per minute per normalized email and IP. | Preserves the existing brute-force protection. |
| BR-08 | Authenticated API traffic is limited to 60 requests per minute per authenticated user. | Reduces abuse without penalizing other users. |
| BR-09 | Public API traffic is limited to 60 requests per minute per IP; login uses its stricter specific limit instead of the public limit. | Protects public endpoints while keeping login stricter. |
| BR-10 | Throttled responses include `Retry-After`; limited responses expose `X-RateLimit-Limit` and `X-RateLimit-Remaining`. | Clients can back off predictably. |
| BR-11 | A throttled request performs no business mutation and issues no new credential. | Prevents partial effects from rejected requests. |
| BR-12 | Tokens expire after seven days and an expired token receives the same `401` envelope as any other invalid credential. | Keeps the session contract explicit. |
| BR-13 | Rate-limit counters are independent per the defined key; one user or IP exhausting a limit must not block another key. | Avoids cross-client denial of service. |
| BR-14 | Hardening does not change business rules, ownership, pagination, or successful response data. | Keeps this a contract slice, not a feature expansion. |

---

# Acceptance criteria

## Scenario 1 — Validation error envelope

Given:

```text
An authenticated client
```

When:

```text
It sends invalid or missing fields to login, book creation, or book update
```

Then:

```text
The response is 422 JSON with a non-empty message and field-level errors, with no data payload
and no internal details
```

## Scenario 2 — Authentication envelope

Given:

```text
A request without a credential, with a malformed credential, or with an expired credential
```

When:

```text
It reaches a protected API operation
```

Then:

```text
The response is 401 JSON with a non-empty message and no business data
```

## Scenario 3 — Not-found envelope

Given:

```text
A request for a foreign book, a missing book, or an unknown API route
```

When:

```text
The request is made
```

Then:

```text
The response is 404 JSON with the same envelope shape and no ownership or resource data
```

## Scenario 4 — Method-not-allowed envelope

Given:

```text
An existing API route that does not support the requested HTTP method
```

When:

```text
The client sends that method
```

Then:

```text
The response is 405 JSON with the error envelope, not HTML
```

## Scenario 5 — Login throttling remains scoped

Given:

```text
Five failed login attempts for one email and IP within one minute
```

When:

```text
A sixth attempt occurs in the same window
```

Then:

```text
The response is 429 JSON with Retry-After and no token
```

## Scenario 6 — General API throttling

Given:

```text
An authenticated user who has made 60 API requests in one minute
```

When:

```text
The same user makes another API request
```

Then:

```text
The response is 429 JSON with Retry-After, no mutation occurs, and another user is not affected
```

## Scenario 7 — Public API throttling

Given:

```text
A client from one IP that has made 60 public API requests in one minute
```

When:

```text
It makes another public request
```

Then:

```text
The response is 429 JSON with Retry-After and no business action occurs
```

## Scenario 8 — Successful responses remain compatible

Given:

```text
A valid authenticated request
```

When:

```text
The existing authentication or book operation succeeds
```

Then:

```text
The response keeps its current 200/201 and data/meta/links shape
```

## Scenario 9 — JSON without an Accept header

Given:

```text
A client that omits Accept: application/json
```

When:

```text
It triggers a validation, authentication, not-found, method, throttle, or server error
```

Then:

```text
The response is still JSON
```

## Scenario 10 — Safe server error

Given:

```text
An unexpected server failure
```

When:

```text
The API returns the failure
```

Then:

```text
The response is 500 JSON with a generic message and no stack trace, SQL, file path, credential,
or token
```

---

# Alternative cases and errors

| Case | Situation | Expected result |
| ---- | --------- | --------------- |
| Missing authentication | Protected request without a token | 401 JSON envelope |
| Malformed token | Token is invalid or unknown | 401 JSON envelope |
| Expired token | Token is older than seven days | 401 JSON envelope |
| Invalid input | Required or malformed fields | 422 JSON envelope with field errors |
| Foreign book | Book belongs to another user | 404 JSON envelope |
| Missing book | Book does not exist | 404 JSON envelope |
| Unknown route | Path does not match the API | 404 JSON envelope |
| Unsupported method | Existing route with another method | 405 JSON envelope |
| Login throttle | Sixth attempt in one minute | 429 JSON envelope with Retry-After |
| General API throttle | Sixty-first request in one minute | 429 JSON envelope with Retry-After |
| Public API throttle | Sixty-first public request in one minute | 429 JSON envelope with Retry-After |
| Unexpected failure | Unhandled server exception | 500 JSON envelope without internals |
| Successful create | Valid book creation | Existing 201 success shape |
| Successful list | Valid book list | Existing 200 data/meta/links shape |

---

# Data involved

| Data | Description | Required |
| ---- | ----------- | -------- |
| Error message | Human-readable summary of the failure | Yes, on errors |
| Field errors | Field-to-messages mapping | Yes, on validation errors |
| Status code | HTTP result category | Yes |
| Rate-limit headers | Remaining limit and retry delay | Yes, on limited responses |
| Token expiration | Seven-day credential lifetime | Existing |
| Business data | Books, users, pagination metadata | Existing; unchanged on success |

No new persistent data or schema change is introduced.

---

# Permissions

| Role | Action |
| ---- | ------ |
| Public client | Access public operations within the public limit |
| Authenticated user | Access protected operations within the per-user limit |
| Reviewer | Inspect the contract and retry behavior |
| Administrator | Does not exist in this feature |

---

# Dependencies

Features required before:

```text
001 · auth-minimum
002 · books-create-list-show
003 · books-update-delete
```

External systems:

```text
None.
```

---

# Expected impact

* Clients get one error parser and predictable retry behavior.
* Brute-force and general abuse protection cover the whole API.
* Token expiration and status codes become explicit, tested contract behavior.
* The delivery becomes easier to review and defend.
* No business capability or user data changes.

---

# Out of scope

* New endpoints, filters, sorting, or book fields.
* Changes to book creation, update, deletion, ownership, or pagination rules.
* Changes to login, logout, token issuance, token lifetime, or registration.
* Roles, account administration, password reset, or refresh tokens.
* Custom error codes, localization, request IDs, tracing, or monitoring.
* UI, OpenAPI, Docker, CI/CD, production deployment, or external integrations.
* The configurable dummy password hash (`005-auth-dummy-hash-config`).
* Final delivery documentation (`006-delivery-documentation`).

---

# Additional notes

This is a contract-hardening slice, not a new product capability. The success response contract
must remain stable.

The token lifetime is already seven days in the current implementation. `004` verifies and
documents that behavior; it does not change the lifetime.

The general API limit is separate from the existing login limiter. Login keeps its stricter
5-per-minute email + IP rule; the public and authenticated API limits apply to the rest of the
surface as defined above.

The shared envelope was mentioned as deferred in `001`, `002`, and `003`; this feature completes
that deferred contract work.

The dummy-hash configuration is intentionally deferred to `005`, and final delivery documentation
to `006`.

---

# Validation before implementation

The feature must be approved when:

* [x] The problem is clear.
* [x] The target user is defined.
* [x] The scope is delimited.
* [x] The acceptance criteria are verifiable.
* [x] The business rules are documented.
* [x] The feature is approved by the human (2026-10-26).
