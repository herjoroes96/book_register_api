# Feature Specification

# 005 · Auth Dummy Hash Configuration

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

Moves the non-secret password hash used for unknown-email login attempts out of application code and
into authentication configuration. The behavior remains a partial timing mitigation, but the value
becomes explicit, reviewable, configurable, and documented.

## Why it matters

The current implementation works, but a hardcoded hash inside the authentication service is not
self-explanatory to a reviewer or maintainer. It also becomes stale if the application changes its
password hasher or cost. Configuration plus documentation makes the intent clear without claiming a
stronger security guarantee than the mechanism provides.

---

# Context

## Current situation

Login returns the same generic `401` for a wrong password and an unknown email. To reduce the timing
difference between those paths, the service performs a password-hash check against a fixed dummy
hash when the email does not exist.

The value currently lives inside the authentication service. It is non-secret, but it is not
configurable, its hasher/cost assumptions are implicit, and the operational behavior is not fully
documented.

## Problem

* A maintainer sees a hash without context and may consider it dead or accidental code.
* A hasher or cost change can make the dummy check inconsistent with real password checks.
* There is no explicit test that the configured value is the one used for unknown emails.
* Operators do not know whether the value can be overridden or what must remain aligned.

## Who it affects

* The technical reviewer evaluating security and maintainability.
* The developer maintaining authentication.
* The operator configuring the application.

API clients should see no change.

---

# Objective

Keep the unknown-email timing mitigation, but make its dummy hash an explicit authentication
configuration value with a safe default, optional environment override, direct tests, and clear
documentation of its purpose, hasher/cost assumptions, and limitations.

---

# Users involved

## Main user

Name:

```text
API consumer
```

Need:

```text
No change in login behavior, error shape, or timing mitigation side effects.
```

---

## Secondary users

* Technical reviewer: verifies that the value is intentional, configurable, and documented.
* Maintainer: changes the hasher or cost safely without hunting through service code.
* Operator: understands which configuration value affects unknown-email login work.

---

# Scope

## Includes

* Move the dummy hash out of service code and into authentication configuration.
* Provide a safe built-in default aligned with the current bcrypt cost.
* Allow an optional environment override for deployments that change the hasher or cost.
* Add code comments explaining why the hash exists and what must stay aligned.
* Add tests proving the configured value is used for unknown emails and that login behavior is
  unchanged.
* Validate that the built-in default is a valid hash.
* Document the value, generation guidance, hasher/cost alignment, and the limits of the mitigation.
* Verify behavior with configuration caching enabled.

## Does not include

* New endpoints or changes to login, logout, tokens, registration, or rate limits.
* Changes to book behavior or ownership.
* A timing benchmark, latency guarantee, or claim that enumeration is fully eliminated.
* Password hashing algorithm migration or cost tuning.
* Secret-manager integration, key rotation, or credential lifecycle work.
* UI, OpenAPI, Docker, CI/CD, monitoring, or production deployment.
* Final delivery documentation (`006-delivery-documentation`).

---

# Functional flow

## Unknown email

```text
Client submits login
    │
    ▼
Email is not found
    │
    ▼
Authentication resolves the configured dummy hash
    │
    ▼
One password hash check is performed
    │
    ▼
Client receives the generic 401 response
```

## Known email

```text
Client submits login
    │
    ▼
Email is found
    │
    ▼
The stored user password hash is checked
    │
    ├── mismatch → generic 401
    └── match → success response and token
```

The configured dummy hash never changes the success or failure outcome. It only makes the work on
the unknown-email path explicit and consistent.

---

# Business rules

| ID | Rule | Motive |
| -- | ---- | ------ |
| BR-01 | Wrong password and unknown email keep returning the same generic `401` response. | Prevents user enumeration through the response body. |
| BR-02 | An unknown email still performs one password-hash check against the configured dummy hash. | Reduces timing differences between known and unknown accounts. |
| BR-03 | The dummy hash is configuration, not a secret and not user data. | Makes the value reviewable and replaceable without changing service logic. |
| BR-04 | A missing override uses a valid built-in default. | Keeps the mitigation active in existing installations. |
| BR-05 | The configured value must be compatible with the application's current password hasher and cost. | Keeps the dummy check representative of real password checks. |
| BR-06 | A configured override must not be silently ignored or treated as a valid password hash if it is malformed. | Avoids a false sense of protection and configuration drift. |
| BR-07 | The dummy hash is never returned, logged, or exposed through an API response. | Keeps sensitive response boundaries intact even though the hash itself is non-secret. |
| BR-08 | Login success, token issuance, throttling, and error envelopes remain unchanged. | This is a maintenance and configuration slice, not an auth redesign. |
| BR-09 | Documentation states that the mitigation is partial and does not replace generic errors or rate limiting. | Prevents overstating the security guarantee. |

---

# Acceptance criteria

## Scenario 1 — Unknown email keeps the generic response

Given:

```text
A login request with an unknown email and a configured dummy hash
```

When:

```text
The request is processed
```

Then:

```text
The response is the same generic 401 envelope, no token is issued, and the client cannot tell
whether the email exists
```

## Scenario 2 — Configured value is used

Given:

```text
A valid dummy hash supplied through authentication configuration
```

When:

```text
Login receives an unknown email
```

Then:

```text
The password check uses that configured value, not a value embedded in the service
```

## Scenario 3 — Valid login is unchanged

Given:

```text
The seeded demo user and valid credentials
```

When:

```text
Login is requested
```

Then:

```text
The success response and token behavior are unchanged
```

## Scenario 4 — Default remains safe

Given:

```text
An installation with no environment override
```

When:

```text
Login receives an unknown email
```

Then:

```text
A valid built-in default is used and the generic 401 behavior remains intact
```

## Scenario 5 — Invalid override is not silently accepted

Given:

```text
An invalid dummy-hash override
```

When:

```text
The application is configured or started
```

Then:

```text
The invalid value is surfaced as a configuration error rather than silently weakening the
unknown-email check
```

## Scenario 6 — Configuration cache

Given:

```text
Configuration caching enabled
```

When:

```text
The application serves login requests
```

Then:

```text
The configured default or override is still used and login behavior does not change
```

## Scenario 7 — No secret exposure

Given:

```text
A successful or failed login
```

When:

```text
The response and logs are inspected
```

Then:

```text
The dummy hash is not returned or logged, and no password or token is exposed
```

## Scenario 8 — Contract remains intact

Given:

```text
An unknown email with an invalid payload or a throttled attempt
```

When:

```text
Login is requested
```

Then:

```text
The existing validation and throttling envelopes remain unchanged
```

---

# Alternative cases and errors

| Case | Situation | Expected result |
| ---- | --------- | --------------- |
| Unknown email | No matching user | Generic 401; dummy hash check |
| Wrong password | Matching user, wrong password | Generic 401 |
| Missing override | No environment value | Valid default used |
| Invalid override | Configured value is malformed | Configuration error; no silent fallback |
| Config cache enabled | Production-like config cache | Same configured behavior |
| Valid login | Correct credentials | Unchanged success/token |
| Throttled login | Too many attempts | Existing 429 envelope |
| Invalid payload | Validation failure | Existing 422 envelope |

---

# Data involved

| Data | Description | Required |
| ---- | ----------- | -------- |
| Dummy password hash | Non-secret configuration value used for unknown emails | Yes, default provided |
| User password hash | Existing stored secret hash for known users | Yes |
| Access token | Existing login result | Only on success |
| Configuration cache | Cached configuration used at runtime | Optional |
| User or book data | Existing behavior | Unchanged |

No database schema or persisted data is added.

---

# Permissions

| Role | Action |
| ---- | ------ |
| Public client | Login with the same behavior as before |
| Authenticated user | No change |
| Operator/maintainer | Configure or review the dummy hash |
| Reviewer | Inspect configuration, comments, and tests |

---

# Dependencies

Features required before:

```text
001 · auth-minimum
004 · api-contract-hardening
```

External systems:

```text
None.
```

---

# Expected impact

* Makes an intentional security measure understandable to reviewers and maintainers.
* Reduces the risk of a stale hash after hasher or cost changes.
* Keeps unknown-email behavior and client contract unchanged.
* Adds a focused test for configuration wiring.
* Updates documentation without expanding authentication scope.

---

# Out of scope

* New authentication flows or endpoints.
* Changes to rate limits, token lifetime, logout, registration, or book behavior.
* Timing benchmarks or guarantees.
* Password hasher migration, password reset, roles, or account administration.
* Secret managers, key rotation, or infrastructure changes.
* UI, OpenAPI, Docker, CI/CD, monitoring, or production deployment.
* Final delivery documentation (`006-delivery-documentation`).

---

# Additional notes

The dummy hash is non-secret, but it must still be treated as configuration rather than a user
credential. Its purpose is to make the unknown-email path perform the same class of password hash
work as a known-email path.

The mitigation is partial. The generic `401`, login throttling, and the rest of the API contract
remain the primary protections.

An invalid override is a deployment error. The implementation plan must choose how the application
surfaces it; silently falling back would make the configured protection unreliable.

---

# Validation before implementation

The feature must be approved when:

* [x] The problem is clear.
* [x] The target user is defined.
* [x] The scope is delimited.
* [x] The acceptance criteria are verifiable.
* [x] The business rules are documented.
* [x] The feature is approved by the human (2026-10-26).
