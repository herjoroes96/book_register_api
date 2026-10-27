# Feature Implementation Plan

# 005 · Auth Dummy Hash Configuration

## General information

Related feature:

```text
docs/features/005-auth-dummy-hash-config/spec.md
```

Status:

```text
completed
```

---

# Technical objective

Move the unknown-email dummy password hash out of `AuthService` and into authentication
configuration, while preserving login behavior:

* add a dedicated configuration value with a safe bcrypt default;
* allow an optional environment override;
* reject malformed overrides before login traffic is served;
* document the hasher/cost alignment and the limits of the timing mitigation;
* test the default, configuration wiring, and unchanged login contract.

No new endpoint, dependency, migration, or business rule is introduced.

---

# Architecture analysis

## Current architecture

`AuthService::login` looks up the user and performs `Hash::check` against either the stored password
hash or a private `DUMMY_PASSWORD_HASH` constant. The constant was introduced by the `001` review
remediation to reduce the timing difference between known and unknown emails.

The API contract, login throttling, and error envelope are complete from `004`. Authentication
configuration is in `config/auth.php`; the project already uses `BCRYPT_ROUNDS=12` in the local
environment.

## Architectural impact

* **Backend:** authentication configuration and `AuthService` only.
* **Database:** none.
* **Frontend:** none.
* **Infrastructure:** optional environment variable; no new service.
* **Tests:** service/config tests for the dummy hash and unchanged login behavior.
* **Documentation:** README, auth/tech-stack notes, feature records, and context.

## Architectural decisions

| ID | Decision | Motive | Alternatives | Consequence |
| -- | -------- | ------ | ------------ | ----------- |
| D-001 | Add `auth.dummy_password_hash` with a valid bcrypt default and an optional `AUTH_DUMMY_PASSWORD_HASH` override. | Removes the magic value from service code and makes hasher/cost changes explicit. | Keep the service constant; require a code change for every deployment; use a secret manager for a non-secret value. | Operators can override the value; the default keeps existing installs working. |
| D-002 | Validate the configured value while configuration is loaded and fail fast if it does not match the configured hasher algorithm, cost, and options. | An invalid or misaligned override must not silently weaken the dummy-check path. | Fall back silently; validate only during login; accept any recognized PHP hash regardless of cost. | Misconfiguration is surfaced at boot/config-cache time with a clear configuration error, never the hash value. |
| D-003 | Keep the dummy hash in `AuthService` usage but read it from config; add a comment explaining the timing purpose and hasher/cost alignment. | The service still owns login behavior; configuration owns the deployment-specific value. | Move login logic to a new class; hide the value in a trait; put HTTP concerns in config. | No new abstraction; the service remains the only auth business entry point. |
| D-004 | Test the default hash, the sanitized config wiring, and the unchanged unknown-email contract; do not benchmark timing. | Timing benchmarks are noisy and would not prove the security property. | Assert a latency threshold; add a timing test with sleeps. | Stable tests; documentation states the mitigation is partial. |
| D-005 | Do not change the login success, error envelope, throttling, token, or rate-limit behavior. | This is a configuration/maintenance slice. | Rework authentication or token flow. | Existing clients see no behavioral change. |

---

# Configuration contract

The authentication configuration exposes one non-secret value:

```text
auth.dummy_password_hash
```

Rules:

* Default: a valid bcrypt hash with cost 12, matching the current `BCRYPT_ROUNDS=12` setup.
* Optional environment override: `AUTH_DUMMY_PASSWORD_HASH`.
* The override must be a valid PHP password hash recognized by the current hasher.
* The value is not a secret and is never included in API responses or logs.
* The value must be regenerated if the hashing driver or cost changes.

The config file will validate the value before returning the configuration. An invalid override
must produce a configuration error that names the setting, not the hash value.

---

# Implementation strategy

1. Add the configuration value, default, validation, and explanatory comments.
2. Refactor `AuthService` to read the configured hash instead of the class constant and document the
   timing rationale.
3. Document the optional environment override and safe generation of the hash in `.env.example`
   and README.
4. Add tests for the default validity, config wiring, and unchanged unknown-email behavior.
5. Verify invalid override handling with configuration cache/build.
6. Run lint, tests, and build; close feature documentation.

---

# Components affected

## `config/auth.php`

Path:

```text
config/auth.php
```

Responsibility:

```text
Authentication guards, providers, password reset settings, and new dummy-hash configuration.
```

Expected change:

```text
Add `auth.dummy_password_hash` with a bcrypt default and optional env override; validate the value
while loading configuration; document hasher/cost alignment.
```

## `app/Services/AuthService.php`

Path:

```text
app/Services/AuthService.php
```

Responsibility:

```text
Login credential check, token issuance, profile, and logout.
```

Expected change:

```text
Remove the private hash constant. Read `config('auth.dummy_password_hash')` for unknown emails and
add a comment explaining its purpose and limitations.
```

## `.env.example`

Path:

```text
.env.example
```

Responsibility:

```text
Safe local environment template.
```

Expected change:

```text
Add a commented `AUTH_DUMMY_PASSWORD_HASH` example with quoting guidance and generation context.
```

## `tests/Feature/Services/AuthServiceTest.php`

Path:

```text
tests/Feature/Services/AuthServiceTest.php
```

Responsibility:

```text
Authentication service behavior tests.
```

Expected change:

```text
Add default-hash validity and configured-value wiring tests; keep invalid-credential coverage.
```

## `README.md`

Path:

```text
README.md
```

Responsibility:

```text
Public setup, configuration, API, and troubleshooting documentation.
```

Expected change:

```text
Document the configuration value, default, optional override, generation guidance, hasher/cost
alignment, and partial-mitigation limit.
```

## `docs/constitution/tech-stack.md`

Path:

```text
docs/constitution/tech-stack.md
```

Responsibility:

```text
Technical stack and authentication implementation notes.
```

Expected change:

```text
Note the configurable dummy hash and its hasher/cost alignment.
```

---

# New components

None. This is a configuration refactor and documentation slice.

---

# Data model

## New entities

None.

## Existing changes

* No tables, columns, indexes, or migrations.
* No changes to users, books, tokens, or stored password hashes.
* The dummy hash is configuration, not persisted application data.

---

# API / Contracts

No endpoint changes.

Login behavior remains:

* valid credentials → `200` with token and public user;
* wrong password or unknown email → same generic `401`;
* invalid payload → existing `422`;
* throttled attempt → existing `429`;
* no new response fields or headers.

The dummy hash is an internal authentication detail and never appears in an API response or log.

---

# External integrations

None.

---

# Security

* The dummy hash is non-secret but is still kept out of API responses and logs.
* The unknown-email path performs one password hash check against the configured value.
* Generic `401`, login throttling, and the API contract remain the primary protections.
* Invalid overrides fail fast rather than silently weakening the mitigation.
* The configured value must align with the current password hasher and cost.
* No credentials or secrets are added to source code, `.env.example`, or logs.
* The default hash is safe for existing installations but must be rotated when hashing parameters
  change.

---

# Performance

* One configuration lookup per login request.
* No extra hash operation beyond the existing unknown-email check.
* No database or network overhead.
* Configuration caching keeps the value available at runtime; changes require rebuilding the cache.
* No timing benchmark is added because network and runtime noise make it unreliable.

---

# Tests required

## Unit / service tests

* The configured dummy hash is non-empty, valid, and aligned with the configured hasher/cost.
* Malformed, wrong-cost, and wrong-algorithm overrides are rejected at configuration load.
* An unknown-email login performs `Hash::check` with the configured value.
* An unknown email still returns `null`/generic failure and issues no token.
* A valid stored-password login remains unchanged.
* Existing invalid credential tests remain green.

## Configuration tests

* `php artisan config:cache` accepts a valid override.
* An invalid `AUTH_DUMMY_PASSWORD_HASH` causes configuration loading/build to fail with a clear
  configuration error.
* The error does not include the supplied hash value.

## Manual tests

* Login with the documented demo credentials succeeds.
* Login with an unknown email returns generic `401` and no token.
* The API remains JSON and throttling behavior is unchanged.
* Configuration cache can be cleared and rebuilt before continuing development.

---

# Migrations required

None.

---

# Risks

## Risk — Dummy hash drifts from the current hasher or cost

Description:

```text
A future hasher change makes the dummy check perform different work from real password checks.
```

Impact:

```text
Medium
```

Mitigation:

```text
Document the alignment requirement, keep the value configurable, and include generation guidance.
```

## Risk — Invalid override weakens or breaks login

Description:

```text
A malformed environment value could make the unknown-email path ineffective or cause login errors.
```

Impact:

```text
High
```

Mitigation:

```text
Validate the value at configuration load and fail fast with a clear message that names the setting.
```

## Risk — `$` interpolation in `.env`

Description:

```text
A bcrypt hash contains `$` characters that may be interpreted by dotenv interpolation.
```

Impact:

```text
Medium
```

Mitigation:

```text
Document single-quote usage in `.env` and verify with configuration caching.
```

## Risk — Configuration cache is stale

Description:

```text
The running application may use an old cached hash after an environment change.
```

Impact:

```text
Medium
```

Mitigation:

```text
Document the required configuration-cache rebuild and include it in verification.
```

## Risk — Overstating timing protection

Description:

```text
The mitigation reduces but does not eliminate timing differences.
```

Impact:

```text
Low
```

Mitigation:

```text
Document it as partial; keep generic errors and rate limiting as primary protections; do not add a
timing benchmark.
```

## Risk — Custom hasher compatibility

Description:

```text
Validation based on `password_get_info` recognizes PHP password hashes, not arbitrary custom
hashers.
```

Impact:

```text
Low
```

Mitigation:

```text
The project uses Laravel's supported password hashers; document that a future custom hasher requires
updating the validator.
```

---

# Technical dependencies

* Existing Laravel configuration and hashing infrastructure.
* Current bcrypt cost 12.
* Sanctum and existing auth flow.
* No new Composer packages, services, or database changes.
* Features `001-auth-minimum` and `004-api-contract-hardening` are complete.

---

# Implementation order

1. Add and validate the config value.
2. Refactor `AuthService`.
3. Update `.env.example`.
4. Add service/config tests.
5. Verify configuration cache and invalid-override handling.
6. Update README and technical notes.
7. Run Pint, tests, optimize/clear, and manual login checks.
8. Close documentation and context.

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
