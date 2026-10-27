# Project Engineering Standards

> This document calibrates **how strict or flexible** development will be. It is filled by
> the `/setup` command (prompt `00-project-setup.md`) through questions, and you validate it.
> It complements `requirements.md`: that document says *what* is built; this one says *how
> rigorously* it is built. The agent treats it as a project rule (constitution level).

**Status:** Approved by the human on 2026-10-26.

Mark the chosen option with `[x]`. Anything left undecided uses the stated **default**.

---

## 1. Overall development rigor

- [ ] **Strict** — production with real data; tests, review, and validation always.
- [x] **Balanced** *(default)* — good practices without excessive ceremony.
- [ ] **Agile/prototype** — speed above all; conscious, documented debt is accepted.

> Even under Balanced, these are mandatory for this project: automated tests, input
> validation, ownership authorization, and the JSON error contract.

---

## 2. Feature size and shape

- **Granularity:**
  - [x] Thin vertical slices *(default — recommended)*: each feature delivers a useful
    end-to-end portion (HTTP → logic → data).
  - [ ] By complete modules: large features by functional area. *(Requires justification;
    contradicts the scope control in `AGENTS.md`.)*
- **Task cap per feature:** `8` *(default)* — if the plan exceeds the cap, the agent splits
  the feature and waits for approval. **Never** a feature that "builds half a system".
- **First feature:**
  - [x] Walking skeleton *(default)*: the thinnest flow that crosses the whole system and
    works (e.g., minimal real login, not a complete auth module).
  - [ ] Other: `<which>`

> `000-setup-base` is the bounded scaffolding feature described in section 3. The first
> **business** feature after it is the walking skeleton (`auth-minimum`).

---

## 3. Initial project setup

How is the initial scaffolding done (framework, dependencies, base structure)?

- [x] **Bounded setup feature** *(default)*: a short, verifiable `000-setup-base` feature
  (installs, runs, health endpoint responds). It **does not** mix business logic.
- [ ] **Manual by the developer**: you do the scaffolding; the agent starts at feature 001.
- [ ] **No setup**: the project already exists (brownfield).

> Rule: setup is **not** an excuse for a mega-feature. If it grows, it is split.

---

## 4. Testing

- **Policy:**
  - [ ] TDD (test first)
  - [x] Tests alongside code *(default)*: every business rule and fixed bug gets a test.
  - [ ] Critical tests only
  - [ ] No tests *(disposable prototypes only)*
- **Types:** unit `yes` · integration `yes` · E2E `no`
  - **Unit:** extracted validation rules and value objects (e.g., ISBN/value rules).
  - **Integration:** HTTP feature tests for auth, CRUD, ownership, pagination, and error
    contract.
  - **E2E:** not applicable: the deliverable is an API with no browser UI.
- **Part of the Definition of Done:** `yes` *(default)*
  - Commands are defined in `docs/constitution/tech-stack.md`: `./vendor/bin/pint --test`,
    `php artisan test`, and `php artisan optimize`.

---

## 5. Deploy feature

- [ ] **Yes, as an explicit feature** — CI/CD, environments, variables, and rollback documented.
- [x] **Yes, manual/simple** *(default)*: deploy documented but without a pipeline yet.
- [ ] **Not yet** — decided later.

Details (if applicable): target environment is local development (XAMPP/PHP 8.2 + SQLite);
no hosting or CI/CD is in scope. The README documents a clean local setup.

---

## 6. Frontend / UI

- **Visual direction (vibe):** `N/A — API-only; no UI in scope`
- **Styling framework:** `N/A`
- **Component library:** `N/A`
- **Dark mode:** `N/A`

> The UI answers are copied to the *Project Override* section of
> `docs/constitution/ui-ux.md`. If a UI is added later, the baseline applies and the override
> must be revisited before any UI work.

---

## 7. Error handling and observability

- **Error rigor:**
  - [x] Strict *(default)*: no empty `catch` blocks, clear messages, typed errors.
  - [ ] Basic: minimum reasonable handling.
- **Logging:** `file` — Laravel file logging (stack/single); never log passwords, tokens, or
  other sensitive values.
- **Monitoring/alerts:** `no` — no APM or external monitoring for this assessment; a health
  endpoint is used only to verify setup.

> API errors follow the JSON envelope defined in `requirements.md`; stack traces are never
> exposed in responses.

---

## 8. Performance

- **Requirement:**
  - [ ] High (high volume/traffic expected): careful queries, pagination, caching.
  - [x] Normal *(default)*: no premature optimization; measure before optimizing.
- Notes: expected volume is low (technical assessment). Pagination at 15 items/page and the
  `user_id` index are already required; no caching, queues, or read replicas are introduced.

---

## 9. Decisions recorded here

| Date | Standard defined | Reason |
|---|---|---|
| 2026-10-26 | Overall rigor: **Balanced** (default) | Proportionate to a small assessment; tests, validation, ownership security, and JSON errors remain mandatory. |
| 2026-10-26 | Thin vertical slices, **8-task cap**, walking skeleton first (default) | `AGENTS.md` scope control; keeps every feature reviewable and explainable. |
| 2026-10-26 | Bounded `000-setup-base` feature (default) | Separates scaffolding from business logic and keeps the first feature small. |
| 2026-10-26 | Tests alongside code; unit + integration `yes`, E2E `no`; tests in DoD | API-only deliverable; HTTP feature tests cover flows, unit tests cover extracted rules. |
| 2026-10-26 | Manual/simple deploy, no pipeline (default) | No hosting or CI/CD requested; local setup is the deliverable. |
| 2026-10-26 | Frontend/UI: **N/A** (override) | No UI in scope; avoids inventing a design system. |
| 2026-10-26 | Strict error handling; file logging; no monitoring (default + decision) | Clear, typed API errors and persistent logs without operational infrastructure. |
| 2026-10-26 | Performance: Normal (default) | Low expected volume; pagination and indexes are sufficient; no premature optimization. |
| 2026-10-26 | Documentation and code in English | Project language convention approved by the human; working discussions stay in Spanish. |

---

> When a standard changes, update this file and, if it affects the interface, reflect the
> change in `docs/constitution/ui-ux.md`. These standards apply to every `spec`, `plan`, and
> `tasks` document, and are part of each feature's closure criteria.
