See `make help` for the full list of available dev commands.

## Read before writing code
- Writing or changing a **test**? Read `docs/conventions/unit-tests.md` first.
- Writing a **class, hook, meta key, block, or stylesheet**? Read `docs/conventions/naming.md` first.

Apply them to code you add. Do not rewrite surrounding code to match.

## Code comments
- Keep comments concise and use plain, concrete terms.
- Add a comment only when it explains a non-obvious reason, constraint, or behavior. Do not restate what the code clearly does.
- Write for future readers who have not seen the current PR or discussion. Explain the lasting reason for the code; omit PR narration, review replies, and comparisons with earlier revisions.
- Keep required docblocks for hooks and public functions, following the conventions below.

## Repository skills
- Shared skills live in `.agents/skills/`; `.claude/skills/` contains symlinks to the same directories for Claude Code.
- When creating, opening, drafting, or updating a PR, follow `.agents/skills/pull-request/SKILL.md` for the PR workflow, including CI monitoring and fixes.
- For agent-driven UI verification, read `.agents/skills/e2e-testing/SKILL.md`.

## Repository layout
- `includes/` — main plugin PHP source.
- `assets/` — JS/CSS source, blocks, and built artifacts under `assets/dist/`.
- `tests/unit-tests/` — PHPUnit suite.
- `tests/e2e-playwright/` — Playwright end-to-end suite (see "End-to-end" below).
- `docs/conventions/` — the naming and unit test conventions this repo is reviewed against.
- `changelog/` — per-PR changelog entries (created via `make changelog`; see Conventions).
- `config/scoper.inc.php` — php-scoper config used during the build.
- `scripts/linter-ci` — the diff-based PHPCS runner used by `make lint` and CI.
- `.github/workflows/` — CI definitions; PR previews are built by `playground-preview.yml`.

## Development environment
- Run WordPress development commands inside the `make up` (wp-env) sandbox rather than against any host WordPress install — this keeps mistakes off shared/local state.
- Use the Node version pinned in `.nvmrc`.
- Ensure Docker Desktop is running before `make up`; verify with `docker info`. If it isn't running, start it without asking — this is a routine local action, not a user-facing change. Use `open -a Docker` on macOS or `sudo systemctl start docker` on Linux, then wait until `docker info` succeeds.
- `make up` boots the wp-env Docker stack; `make down` stops it; `make destroy` wipes containers and data for a clean slate.
- `make shell` opens a shell inside the WordPress container; `make wp CMD="..."` runs wp-cli commands against it.
- The primary checkout uses `sensei-lms`, preserving its existing database, uploads, plugins, and ports on first adoption. Linked worktrees have isolated WordPress data. New linked-worktree environments copy data from `sensei-lms` when present, otherwise from their repository's managed primary checkout. Run `make up` in the primary checkout first to provide a baseline for linked worktrees. If no source exists, start fresh; a selected source must be initialized and running. Use `NO_CLONE=1 make up` to opt out. Cloning leaves source data unchanged. New linked-worktree Docker names use readable branch/worktree labels plus stable IDs; optionally choose `NAME=label` on first startup. See `docs/development/worktree-environments.md` for `--clone-from` source selection and cleanup. Install additional plugins only in worktrees that need them.
- Override WordPress or PHP with `make up WP=6.8 PHP=8.3`; selections persist for that environment.
- Read actual URLs with `make env-status`; do not assume ports 8888/8889. Destroy environments before retiring a worktree, or preview orphans with `make env-prune`.

## Building
- `make build` produces the plugin zip via wp-env, which sidesteps host PHP version issues.
- On a fresh checkout, run `make install-php` before `make build`. The build runs `composer install --no-dev --no-scripts`, which skips the `post-autoload-dump` hook that generates the scoped vendor tree at `vendor/sensei-lms/third-party-libs/` (consumed at runtime, e.g. `Sensei\ThirdParty\Pelago\Emogrifier\CssInliner`). `make install-php` runs the full composer install with scripts and populates that tree.
- `make build` runs `composer install --no-dev` inside the container, which strips all dev dependencies (`vendor/bin/phpunit`, `vendor/bin/psalm`, etc.). After a build, restore dev tooling before running tests with `make install-php`.

## Testing
- **Unit vs. integration tests**: Use unit tests for logic that can be exercised independently of WordPress. Use integration tests when correctness depends on WordPress APIs, hooks, permissions, database queries, or persistence. Exercise the real dependency whose behavior is being verified; do not mock it away. Keep each test focused on a distinct behavior.
- **Focused local tests**: Run the smallest relevant subset of tests for the affected behavior, including new or changed tests. Leave full suites and broader regression coverage to CI; do not run the full suite locally.
- **Regression coverage**: Write regression tests for non-trivial new behavior and bug fixes, following `docs/conventions/unit-tests.md`, and run them locally. For bug fixes, confirm the regression test fails before the fix and passes afterward where practical. Skip tests for trivial changes such as copy/string tweaks, config, mechanical renames, one-line passthroughs, styling etc.. If unsure a change needs a test, ask rather than write one by default.
- **Refinements to tested changes**: For small refinements to an already-tested change, reuse and rerun existing tests. Apply this rule throughout the task, including subsequent refinement and review rounds. Adding or changing production code does not by itself justify adding or changing tests. Add coverage only for a distinct, previously uncovered behavior or failure mode. Do not add tests solely to assert an internal call or implementation detail. Verify performance refinements with focused measurements where appropriate.
- **Manual verification**: Perform relevant manual checks CI does not cover, such as browser workflows, visual inspection, or performance measurements. Report what was checked and what awaits CI; do not claim unexecuted tests passed.
- **Verifying CI fixes**: When diagnosing or verifying a CI failure, run the smallest meaningful local check where practical. If verification requires a full suite, broad analysis, or complete build, leave it to CI and report that local verification was limited. If local reproduction is unavailable, explain the limitation and use CI to validate the fix.
- **PHPUnit**: `make test-php` (runs inside wp-env). Targeted runs: `make test-php-filter FILTER="TestClass"` or `FILTER="TestClass::method"`.
- **PHPUnit with HPPS enabled**: `npm run test-php:wp-env:hpps`.
- **JS unit tests**: `npm run test-js`.
- **End-to-end (Playwright)**: `npm run test:e2e` runs headless against the wp-env stack; `npm run test:e2e:debug` runs headed. Requires `make up` to be running first. The `pretest:e2e` hook runs ESLint + `tsc` before Playwright launches — failures there appear before any test output. See `tests/e2e-playwright/README.md` for details.
- **Ad-hoc UI verification**: After `make up`, get the dev URL from `make env-status`; newly initialized development sites use `admin` / `password` unless a private password is set. An adopted primary retains its existing login details. For agent-driven verification, use the `e2e-testing` skill (`.agents/skills/e2e-testing/SKILL.md`) — it scopes from `git diff`, lists Sensei's admin/frontend surfaces, and uses the available browser tools to walk through the relevant flow.

## Linting
Leave routine CI-covered linting, static analysis, validation builds, and compatibility matrices to CI. Do not run Psalm locally; CI handles it. For other CI failures, follow the local-verification guidance above. Command reference:

- **PHPCS**: `make lint` is the diff-based check CI uses; requires a clean working tree. Whole-codebase scans: `./vendor/bin/phpcs`.
- **JS / CSS / types**: `npm run lint-js` (ESLint on `assets`), `npm run lint-css` (stylelint on SCSS), `npm run lint-types` (tsc). Each JS/CSS one has a `:fix` variant.
- **Before pushing**: Review the diff, run focused tests for affected behavior, and complete relevant manual checks CI does not cover. Leave routine checks to CI; when fixing a CI failure, follow the local-verification guidance above.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry. Run `make changelog` and commit the generated `changelog/` file with the PR. For purely internal changes (refactors, test-only), apply the `No Changelog` label instead.
- **PR milestones**: Every PR MUST have a milestone or CI (`pr-validation.yml`) fails. The `pull-request` skill assigns the next shipping milestone when it opens a PR.
- **Version placeholders in docblocks**: Never hardcode a release number in `@since` tags for new code. Use `@since $$next-version$$` — release tooling replaces the placeholder on version bump.
- **Coding standards**: Follow the WordPress coding standards for the language you're touching — [PHP](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), [JavaScript](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/), [CSS](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/), [HTML](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/). See **Linting** above for the enforcing commands; HTML has no linter, so apply that standard by hand.
- **Documenting hooks and functions**: Document new or updated actions/filters and public functions with docblocks (params, return, `@since` per the version-placeholder rule above). When a change adds or updates a hook, describe it and apply the `Hooks` label; when it deprecates code, name the replacement and apply the `Deprecation` label.
- **Removing public code**: Do not remove public functions, methods, hooks or classes, even when nothing in Sensei or its extensions uses them; third-party code may. Deprecate them instead, following the [deprecation policy](https://senseilms.com/documentation/deprecation-policy/). Code in the `Sensei\Internal` namespace, code marked `@internal` or `@experimental`, and PHP `private` methods are exempt.
- **Removing deprecated code**: In a major release, remove the deprecated code that is due. Public code is due two majors after the version it was deprecated in (deprecated in 4.x, removed in 6.0.0); `@access private` code is due one major after.
- **Minimum supported versions**: The `sensei-lms.php` plugin header is the source of truth — `Requires PHP` is the minimum PHP and `Requires at least` is the minimum WordPress. Check CI coverage for version-sensitive changes and leave covered compatibility checks to CI. Use the `make up WP=… PHP=…` override (see Development environment) for relevant manual checks on supported versions that CI does not cover.
