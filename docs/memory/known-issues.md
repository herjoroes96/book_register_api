# Known Issues and Technical Debt

> What is broken, fragile, or pending, and what the AI must know before touching related areas.
> Do not leave problems only in your head or in chat: if it is not here, it does not exist.

---

## None at the close of `000-setup-base`

**Description:** No known defects or technical debt were identified in the setup foundation.

**Impact:** None currently.

**Affected area:** `000-setup-base` delivery.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** Future features must add entries here when they introduce known debt or open issues.

## None at the close of `001-auth-minimum`

**Description:** No known defects or technical debt were identified in the authentication slice.

**Impact:** None currently.

**Affected area:** `001-auth-minimum` delivery.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** General API rate limiting and the shared error envelope were later delivered in `004-api-contract-hardening`. Registration, roles, and book data remain out of scope. ADR-008 introduced the post-close `AUTH-001` retrofit, which is now resolved below.

## AUTH-001 · AuthController service retrofit (resolved)

**Description:** `001-auth-minimum` was completed before ADR-008. `AuthController` initially contained credential checks, token issuance, and token revocation instead of delegating to `AuthService`.

**Impact:** None currently. The retrofit is complete and the API contract is unchanged.

**Affected area:** `app/Http/Controllers/Api/V1/AuthController.php`, `app/Services/AuthService.php`, `001-auth-minimum`.

**Status:** resolved on 2026-10-26

**Resolution:** `AuthService` now owns login, profile, and logout logic; `AuthController` is HTTP-only. Service tests live in `tests/Feature/Services/AuthServiceTest.php`; all HTTP auth tests remain green.

## None at the close of `002-books-create-list-show`

**Description:** No known defects or technical debt were identified in the create/list/detail book slice.

**Impact:** None currently.

**Affected area:** `002-books-create-list-show` delivery.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** Update/delete, search, filters, and public catalog remain intentionally out of scope for later features.

## None at the close of `003-books-update-delete`

**Description:** No known defects or technical debt were identified in the update/delete book slice.

**Impact:** None currently.

**Affected area:** `003-books-update-delete` delivery.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** The shared error envelope, general API rate limiting, and contract hardening were delivered in `004-api-contract-hardening`. Soft deletes, restore, bulk operations, search, and public catalog remain intentionally excluded.

## None at the close of `004-api-contract-hardening`

**Description:** No known defects or technical debt were identified in the API contract-hardening slice.

**Impact:** None currently.

**Affected area:** `004-api-contract-hardening` delivery.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** The dummy-hash configuration remains intentionally deferred to `005-auth-dummy-hash-config`, and final delivery documentation to `006-delivery-documentation`. No new endpoint, migration, package, UI, Docker, or CI/CD work was introduced.

## None at the close of `005-auth-dummy-hash-config`

**Description:** No known defects or technical debt were identified in the dummy-hash configuration slice.

**Impact:** None currently.

**Affected area:** `005-auth-dummy-hash-config` delivery.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** The timing mitigation remains partial by design. Generic authentication errors, login throttling, and API rate limits remain the primary protections. Final delivery documentation is the remaining feature.

## None at the close of `006-delivery-documentation`

**Description:** No known defects or technical debt were identified in the final delivery slice.

**Impact:** None currently.

**Affected area:** `006-delivery-documentation` and the published assessment repository.

**Status:** resolved / no open issue.

**Workaround / proposed solution:** Not applicable.

**Note:** The delivery is published at `https://github.com/herjoroes96/book_register_api` and was verified from a fresh clone. The 10% efficiency statement remains an explicit conjecture, not a benchmark.
