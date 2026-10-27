# Feature Specification

# 001 · Auth Minimum

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

Provides the minimum authentication flow needed to identify a user before book management is
introduced.

A seeded demo user can log in with an email and password, access an authenticated-only profile
check, and log out. Logging out invalidates the current credential only. No public registration
is added.

---

# Context

## Current situation

The setup foundation (`000-setup-base`) is complete. The application boots, exposes a public
health check, and has an automated test for that response. There is no user identity, login, or
protected resource yet.

## Problem

Book records must belong to a user, and a user must only operate on their own records. Without
authentication, the application cannot establish who is making a request or protect user-specific
data.

## Who it affects

* The demo user who needs authenticated access.
* The technical reviewer who must verify login, protected access, and logout.
* Future book features, which depend on a reliable authenticated identity.

## Impact

Book management cannot be built safely without this feature. Authentication also establishes the
contract that later hardening and ownership rules depend on.

---

# Objective

Allow the demo user to log in, use the returned access credential to reach an authenticated-only
profile check, and log out by invalidating the current credential. Invalid credentials must fail
safely without revealing whether an email exists.

---

# Users involved

## Main user

Name:

```text
Demo user
```

Need:

```text
Log in, access authenticated functionality, and log out safely.
```

---

## Secondary users

* Technical reviewer: uses documented demo credentials to verify the flow.
* Future book owner: relies on the authenticated identity established here.
* Future feature developer: needs a stable authentication contract.

---

# Scope

## Includes

* One seeded demo user with documented credentials.
* Login with email and password.
* Input validation for required and well-formed data.
* An access credential returned after successful login.
* An authenticated-only profile check returning the user's public identity.
* Logout that invalidates only the current credential.
* Credential expiration after 7 days.
* Login throttling: at most 5 attempts per minute per email and IP address.
* Generic authentication errors that do not reveal whether an email exists.
* JSON responses for success and failure.
* Automated tests for login, protected access, logout, expiration, and throttling.
* README documentation for demo credentials and the auth flow.

---

## Does not include

* Public registration or self-service account creation.
* Roles, permissions, administrators, or cross-user access.
* Password reset, password change, email verification, or account recovery.
* Multi-factor authentication, remember-me, refresh credentials, or social login.
* Book management or book data.
* General API rate limiting and the final error envelope (`004-api-contract-hardening`).
* Frontend, UI, Docker, or CI/CD.
* Production deployment, monitoring, or external secret management.

---

# Functional flow

## Login

```text
Demo user
    │
    ▼
Submits email and password
    │
    ▼
System validates input
    │
    ├── invalid input → JSON validation error
    ├── invalid credentials → generic authentication error
    ├── too many attempts → throttled error
    └── valid credentials → access credential + public user identity
```

## Authenticated access

```text
User with access credential
    │
    ▼
Requests the authenticated profile check
    │
    ├── missing, invalid, revoked, or expired credential → authentication error
    └── valid credential → public user identity
```

## Logout

```text
Authenticated user
    │
    ▼
Requests logout
    │
    ├── no valid credential → authentication error
    └── current credential invalidated → success response
```

After logout, the same credential cannot access the profile check. Other active credentials for
the same user remain valid.

---

# Business rules

| ID | Rule | Motive |
| -- | ---- | ------ |
| BR-01 | There is one seeded demo user; there is no public registration. | Keeps the slice focused and avoids account lifecycle concerns. |
| BR-02 | Login requires a well-formed email and a non-empty password. | Establishes the credential pair used by later features. |
| BR-03 | Wrong password and unknown email return the same generic authentication error. | Prevents user enumeration. |
| BR-04 | Missing or malformed login data returns a JSON validation error with field messages. | Separates validation feedback from authentication failure. |
| BR-05 | The profile check requires a valid access credential; missing, malformed, revoked, or expired credentials are rejected. | Protects authenticated functionality and provides a reliable identity boundary. |
| BR-06 | The profile check returns only identifier, name, and email; password material is never returned. | Gives later features the identity they need without exposing secrets. |
| BR-07 | Logout invalidates only the current credential; other active credentials remain valid. | Matches normal API session behavior and avoids unexpected global logout. |
| BR-08 | Access credentials expire 7 days after issue. | Limits the impact of a leaked credential while keeping the demo usable. |
| BR-09 | After 5 login attempts in one minute for the same email and IP, further attempts are throttled until the window passes. | Reduces brute-force risk without adding the general API rate-limit policy here. |
| BR-10 | Passwords are stored only as a secure hash and are never returned or logged. | Protects credentials and satisfies the security constraints. |
| BR-11 | Authentication responses are JSON and expose no stack traces or internal details. | Keeps the contract consistent; the shared error envelope is hardened in 004. |
| BR-12 | All authenticated users have the same access level; no roles exist. | Roles are not requested and would expand the scope. |

---

# Acceptance criteria

## Scenario 1 — Successful login and profile access

Given:

```text
The seeded demo user and valid credentials
```

When:

```text
The user logs in and uses the returned access credential on the profile check
```

Then:

```text
Login returns the credential and public identity, and the profile check returns identifier, name,
and email
```

## Scenario 2 — Generic invalid credentials

Given:

```text
A known email with a wrong password, or an unknown email
```

When:

```text
The user attempts to log in
```

Then:

```text
Both cases return the same generic authentication error, with no credential and no indication of
whether the email exists
```

## Scenario 3 — Invalid input

Given:

```text
A missing or malformed email, or a missing password
```

When:

```text
The user attempts to log in
```

Then:

```text
The response is a JSON validation error with field-level messages and no credential
```

## Scenario 4 — Protected access rejects bad credentials

Given:

```text
A profile request with no credential, a malformed credential, a revoked credential, or a credential
older than 7 days
```

When:

```text
The profile check is requested
```

Then:

```text
Every case is rejected as unauthenticated and returns no profile data
```

## Scenario 5 — Logout affects only the current credential

Given:

```text
Two active access credentials for the same user
```

When:

```text
The user logs out using one credential and then requests the profile check with both
```

Then:

```text
The logged-out credential is rejected and the other credential still works
```

## Scenario 6 — Login throttling

Given:

```text
Five login attempts for the same email and IP within one minute
```

When:

```text
A sixth attempt occurs in the same window
```

Then:

```text
The request is throttled, no credential is issued, and the response is safe
```

## Scenario 7 — Secrets never exposed

Given:

```text
A successful login or profile response
```

When:

```text
The response and application logs are inspected
```

Then:

```text
The password and stored credential secrets are not present
```

## Scenario 8 — No public registration

Given:

```text
The available authentication behavior
```

When:

```text
A client looks for account creation
```

Then:

```text
No public registration capability exists
```

---

# Alternative cases and errors

| Case | Situation | Expected result |
| ---- | --------- | --------------- |
| Missing email | Login has no email | JSON validation error; no credential |
| Malformed email | Email is not a valid address | JSON validation error; no credential |
| Missing password | Login has no password | JSON validation error; no credential |
| Wrong password / unknown email | Credentials do not match | Same generic authentication error; no credential |
| Missing credential | Profile check without a credential | Authentication error; no profile data |
| Malformed credential | Credential cannot be parsed or is unknown | Authentication error; no internal details |
| Expired credential | Credential is older than 7 days | Authentication error; login again |
| Revoked credential | Credential was invalidated by logout | Authentication error; no profile data |
| Throttled login | Too many attempts in one minute | Throttled response; no credential |
| Logout without authentication | Logout without a valid credential | Authentication error |

---

# Data involved

| Data | Description | Required |
| ---- | ----------- | -------- |
| User identifier | Public identifier of the authenticated user | Yes |
| Name | Public display name | Yes |
| Email | Login identity and public profile data | Yes |
| Password | Secret stored only as a hash | Yes |
| Access credential | Returned after login and required for protected access | Yes |
| Credential expiration | Issue time and 7-day lifetime | Yes |
| Book data | None; books belong to later features | No |

---

# Permissions

| Role | Action |
| ---- | ------ |
| Unauthenticated visitor | Attempt login only |
| Demo user | Login, access own profile check, logout |
| Authenticated user | Access the protected profile check and logout |
| Administrator | Does not exist in this feature |

No roles or cross-user access exist yet.

---

# Dependencies

Features required before:

```text
000 · setup-base
```

External systems:

```text
None.
```

---

# Expected impact

* Establishes the authenticated identity required by book ownership.
* Unblocks the book CRUD features.
* Provides documented demo credentials for the reviewer.
* Adds the first protected API behavior and its security tests.
* Defines the authentication contract that later hardening can build on.

---

# Out of scope

* Public registration and account creation.
* Roles, permissions, administration, and cross-user access.
* Password reset, password change, email verification, and account recovery.
* Multi-factor authentication, remember-me, refresh credentials, and social login.
* Book management or book data.
* General API rate limiting and the final shared error envelope.
* Frontend, UI, Docker, CI/CD, production deployment, and monitoring.

---

# Additional notes

This is the authentication walking skeleton, not a complete identity module. It must remain small
and explainable.

Demo credentials are test credentials documented in the README, not real user data.

The access credential is opaque to API clients; this specification does not prescribe its storage
format. The technical plan will define the implementation.

Login throttling is limited to the login flow. The general API rate limit and shared error envelope
are handled by `004-api-contract-hardening`.

---

# Validation before implementation

The feature must be approved when:

* [x] The problem is clear.
* [x] The target user is defined.
* [x] The scope is delimited.
* [x] The acceptance criteria are verifiable.
* [x] The business rules are documented.
* [x] The feature is approved by the human (2026-10-26).
