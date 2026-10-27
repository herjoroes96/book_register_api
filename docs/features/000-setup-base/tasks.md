# 000 · Setup Base — Tasks

Reference: `spec.md` · `plan.md`

Feature status: Completed

> Converted from the approved plan into small, ordered, verifiable steps.
> **One task format:** `TASK-NNN`. Implement one task at a time (prompt 06).
> At the end of each task, pass the Definition of Done (lint/test/build).
> Commit only when the human explicitly asks; if asked, use `TASK-NNN: <what changed>`.

---

## Implementation objective

A clean checkout can be installed and started with documented steps, the public health check
reports a healthy service, and lint, tests, and build pass. The feature contains no business
logic, authentication, users, books, UI, Docker, or CI/CD.

---

## Phase order

1. Scaffolding and local configuration → 2. Cleanup → 3. Backend/API → 4. Testing →
   5. Database verification → 6. Documentation and final verification

Never start a task before its dependencies are complete.

> The default web route is removed in TASK-001, before the first build gate, because the
> framework's welcome route uses a closure and route caching fails on closures. This keeps the
> Definition of Done green from the first task.

---

## Tasks

### TASK-001 · Scaffold Laravel and configure the local application

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Create a Laravel 12.x project in a temporary directory outside the repository.
  Copy the application skeleton into the repository root, excluding `.git`, `.env`, `vendor/`, and
  `node_modules/`; preserve `docs/`, `aipf/`, `AGENTS.md`, `.aipf.json`, and `.opencode/`. Merge
  `.gitignore`, run `composer install`, create `.env` from `.env.example`, generate the application
  key, and create the SQLite database file. Remove the default web route and the framework `/up`
  health route so route caching can pass. Remove the default feature example test that requests
  `/`; keep the trivial unit example until the health test replaces it.
- **Files / location:** Laravel skeleton (`app/`, `bootstrap/`, `config/`, `database/`, `public/`,
  `resources/`, `routes/`, `storage/`, `tests/`, `artisan`, `composer.json`, `composer.lock`,
  `phpunit.xml`, `.gitignore`, `.editorconfig`, `.gitattributes`), `.env.example`,
  `.env` (local only), `database/database.sqlite` (local only), `bootstrap/app.php`,
  `routes/web.php`, `tests/Feature/ExampleTest.php`.
- **Dependencies:** None.
- **Validation:** `php artisan --version` reports Laravel 12.x; existing project docs still exist;
  `composer validate --strict` passes; `php artisan about` boots; `php artisan db:show` connects;
  `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass;
  `php artisan optimize:clear` restores local development.
- **Completion:** 2026-10-26 — Laravel 12.70.1 scaffolded; Pint passed; 1 test passed; `optimize`
  and `optimize:clear` passed; project docs preserved.

### TASK-002 · Remove unused frontend scaffolding

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Remove the default welcome view and unused frontend scaffolding. Keep the
  `resources/views` directory tracked with `.gitkeep`. Remove any npm/Vite references from
  `composer.json` scripts if present. Search for leftover references.
- **Files / location:** `resources/views/welcome.blade.php`, `resources/views/.gitkeep`,
  `resources/css/`, `resources/js/`, `package.json`, `vite.config.js`, `composer.json` (only if it
  references npm/Vite).
- **Dependencies:** TASK-001.
- **Validation:** A precise reference search over `app`, `config`, `routes`, `resources`,
  `tests`, `composer.json`, and `README.md` (excluding `vendor`, `docs`, `aipf`, and
  `node_modules`) finds no `welcome.blade`, `laravel-vite-plugin`, `@vite`, `resources/css`,
  `resources/js`, `npm run`, or `vite build` references; `php artisan route:list`,
  `composer validate --strict`, `./vendor/bin/pint --test`, `php artisan test`, and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — removed the welcome view, CSS/JS assets, `package.json`, and
  `vite.config.js`; removed npm/Vite scripts from `composer.json`; kept `resources/views` with
  `.gitkeep`; DoD passed.

### TASK-003 · Register API routing and add the health endpoint

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Create `routes/api.php` with the versioned health route. Add an invokable
  `HealthController` that checks the database connection and returns
  `200 {"status":"ok"}` or a safe `503 {"status":"unavailable"}`. Register API routing in
  `bootstrap/app.php`. Do not install Sanctum or add authentication.
- **Files / location:** `routes/api.php`,
  `app/Http/Controllers/Api/V1/HealthController.php`, `bootstrap/app.php`.
- **Dependencies:** TASK-001.
- **Validation:** `php artisan route:list --path=api/v1/health` shows the GET route;
  `php artisan serve` followed by `curl http://127.0.0.1:8000/api/v1/health` returns `200` with
  `{"status":"ok"}`; the response contains no sensitive data and requires no auth;
  `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass.
- **Completion:** 2026-10-26 — registered `/api/v1/health`, added the invokable controller with
  database check and safe 503, verified `200` over HTTP and `503` with an invalid database path;
  Pint, tests, build, and cleanup passed.

### TASK-004 · Configure tests and add health coverage

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Configure `phpunit.xml` to use in-memory SQLite for tests. Remove the trivial
  unit example. Add `HealthCheckTest` with `RefreshDatabase`, asserting a public `200` response,
  exact JSON `{"status":"ok"}`, and a JSON content type.
- **Files / location:** `phpunit.xml`, `tests/Feature/HealthCheckTest.php`,
  `tests/Unit/ExampleTest.php` (remove).
- **Dependencies:** TASK-003.
- **Validation:** `php artisan test --filter=HealthCheckTest` passes; `php artisan test` passes;
  the test does not require authentication or an external database; `./vendor/bin/pint --test` and
  `php artisan optimize` pass.
- **Completion:** 2026-10-26 — `phpunit.xml` already used in-memory SQLite; removed the unit
  example and added `HealthCheckTest` with 4 assertions; Pint, tests, build, and cleanup passed.

### TASK-005 · Apply migrations and verify database readiness

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Apply the framework's default migrations to verify the SQLite connection.
  Confirm that no demo user or domain data is seeded and that `DatabaseSeeder` remains empty.
  Exercise the health endpoint against the migrated database.
- **Files / location:** `database/database.sqlite` (local only), `database/migrations/` (no
  changes), `database/seeders/DatabaseSeeder.php` (corrected to empty; default Test User removed).
- **Dependencies:** TASK-001, TASK-003, TASK-004.
- **Validation:** `php artisan migrate` exits successfully; `php artisan migrate:status` shows the
  framework migrations as applied; `php artisan db:show` connects; the user count is `0` (Tinker
  or a direct query); the health check returns `200`; `./vendor/bin/pint --test`,
  `php artisan test`, and `php artisan optimize` pass. No auth routes, controllers, seeders, or
  user records exist.
- **Completion:** 2026-10-26 — applied the three framework migrations; verified 9 tables,
  `User::count()` = 0, health `200`; corrected the default seeder so it no longer creates a Test
  User; Pint, tests, build, and cleanup passed.

### TASK-006 · Document setup and run the final quality gate

- **Status:** [ ] Pending / [ ] In progress / [x] Completed (2026-10-26)
- **Description:** Replace the framework README with project-specific documentation: prerequisites,
  setup, environment, database initialization, running, health check, lint/test/build commands,
  troubleshooting, assumptions, and current scope. Verify `.env.example` and `.gitignore`. Run
  the full quality gate and the manual setup checks, then clear build caches.
- **Files / location:** `README.md`, `.env.example`, `.gitignore`, `composer.json` (setup
  script).
- **Dependencies:** TASK-001 through TASK-005.
- **Validation:** A fresh checkout can follow the README without Node/npm; the health check returns
  `200`; a missing required environment value fails with a clear error without leaking secrets;
  an invalid database connection returns a safe `503`; an occupied port has troubleshooting
  guidance; `./vendor/bin/pint --test`, `php artisan test`, and `php artisan optimize` pass;
  `php artisan optimize:clear` restores local development.
- **Completion:** 2026-10-26 — README written in English; `VITE_APP_NAME` removed from env files;
  `composer setup` now creates the SQLite file before migrating; health `200` verified over HTTP;
  `composer setup` verified; Pint, tests, build, and cleanup passed.

---

## Feature Definition of Done

Before marking the feature complete:

- [x] All TASK items are Completed.
- [x] The acceptance criteria in `spec.md` are satisfied.
- [x] `architecture.md` and `conventions.md` are respected.
- [x] Lint, tests, and build pass using the commands in `tech-stack.md`.
- [x] No authentication, users, books, UI, Docker, or CI/CD work was added.
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

## Agent rules

1. Implement one task at a time. Mark real progress, not optimistic progress.
2. Report modified files and executed validations with actual results.
3. Do not skip tasks or expand the scope silently.
4. If the plan proves wrong, stop, explain, and propose an update. Do not patch around it.
5. Commit or push only when the human explicitly asks; use `TASK-NNN: <what changed>` when asked.
