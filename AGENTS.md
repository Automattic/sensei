See `make help` for the full list of available dev commands.

## Read before writing code
- Writing or changing a **test**? Read `docs/conventions/unit-tests.md` first.
- Writing a **class, hook, meta key, block, or stylesheet**? Read `docs/conventions/naming.md` first.

Apply them to code you add. Do not rewrite surrounding code to match.

## Repository layout
- `includes/` — main plugin PHP source.
- `assets/` — JS/CSS source, blocks, and built artifacts under `assets/dist/`.
- `tests/unit-tests/` — PHPUnit suite.
- `tests/e2e-playwright/` — Playwright end-to-end suite (see "End-to-end" below).
- `docs/conventions/` — the naming and unit test conventions this repo is reviewed against.
- `changelog/` — per-PR changelog entries (created from the PR description's changelog checkbox, or via `make changelog`; see Conventions).
- `config/scoper.inc.php` — php-scoper config used during the build.
- `scripts/linter-ci` — the diff-based PHPCS runner used by `make lint` and CI.
- `.github/workflows/` — CI definitions; PR previews are built by `playground-preview.yml`.

## Development environment
- Run all dev commands inside the `make up` (wp-env) sandbox rather than against any host WordPress install — this keeps mistakes off shared/local state.
- Use the Node version pinned in `.nvmrc`.
- Ensure Docker Desktop is running before `make up`; verify with `docker info`. If it isn't running, start it without asking — this is a routine local action, not a user-facing change. Use `open -a Docker` on macOS or `sudo systemctl start docker` on Linux, then wait until `docker info` succeeds.
- `make up` boots the wp-env Docker stack; `make down` stops it; `make destroy` wipes containers and data for a clean slate.
- `make shell` opens a shell inside the WordPress container; `make wp CMD="..."` runs wp-cli commands against it.
- Override the WordPress or PHP version with `make up WP=6.8 PHP=8.3` (writes a transient `.wp-env.override.json`).

## Building
- `make build` produces the plugin zip via wp-env, which sidesteps host PHP version issues.
- On a fresh checkout, run `make install-php` before `make build`. The build runs `composer install --no-dev --no-scripts`, which skips the `post-autoload-dump` hook that generates the scoped vendor tree at `vendor/sensei-lms/third-party-libs/` (consumed at runtime, e.g. `Sensei\ThirdParty\Pelago\Emogrifier\CssInliner`). `make install-php` runs the full composer install with scripts and populates that tree.
- `make build` runs `composer install --no-dev` inside the container, which strips all dev dependencies (`vendor/bin/phpunit`, `vendor/bin/psalm`, etc.). After a build, restore dev tooling before running tests or static analysis with `make install-php`.

## Testing
- **Test-driven development**: Follow a TDD approach for non-trivial new behavior and bug fixes — write a failing test first, then implement until it passes. Skip tests for trivial changes such as copy/string tweaks, config, mechanical renames, one-line passthroughs, styling etc.. If unsure a change needs a test, ask rather than write one by default.
- **PHPUnit**: `make test-php` (runs inside wp-env). Targeted runs: `make test-php-filter FILTER="TestClass"` or `FILTER="TestClass::method"`.
- **PHPUnit with HPPS enabled**: `npm run test-php:wp-env:hpps`.
- **JS unit tests**: `npm run test-js`.
- **End-to-end (Playwright)**: `npm run test:e2e` runs headless against the wp-env stack; `npm run test:e2e:debug` runs headed. Requires `make up` to be running first. The `pretest:e2e` hook runs ESLint + `tsc` before Playwright launches — failures there appear before any test output. See `tests/e2e-playwright/README.md` for details.
- **Ad-hoc UI verification**: After `make up`, the dev site is at `http://localhost:8888` with admin credentials `admin` / `password` (wp-env defaults). For agent-driven verification, use the `/e2e-testing` skill (`.claude/skills/e2e-testing/SKILL.md`) — it scopes from `git diff`, lists Sensei's admin/frontend surfaces, and walks the Chrome DevTools MCP through the relevant flow.

## Linting
- **PHPCS**: Run `make lint` (the same diff-based check CI uses; requires a clean working tree). Whole-codebase scans: `./vendor/bin/phpcs`.
- **Psalm**: Run `make psalm`. This runs Psalm against every PHP version in `.github/workflows/psalm.yml`'s matrix (parsed at runtime) since type narrowing differs between versions. Requires the matching `php@<version>` brew formulae installed.
- **JS / CSS / types**: `npm run lint-js` (ESLint on `assets`), `npm run lint-css` (stylelint on SCSS), `npm run lint-types` (tsc). Each JS/CSS one has a `:fix` variant.
- **Before pushing**: The pre-commit hook only lints PHP files added after 2020-01-01. CI lints all changed lines. Always run PHPCS and the full Psalm matrix on modified files before pushing to avoid CI failures.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry. Prefer ticking **"Automatically create a changelog entry"** in the PR description (fill Significance/Type/Message; CI writes the `changelog/` file). Fall back to `make changelog` only when the checkbox can't be used — forks (CI can't push to a forked branch), or if you'd rather commit the entry yourself; don't do both. For purely internal changes (refactors, test-only), apply the `No Changelog` label instead.
- **PR milestones**: Every PR MUST have a milestone or CI (`pr-validation.yml`) fails. The `pull-request` skill assigns the next shipping milestone when it opens a PR.
- **Version placeholders in docblocks**: Never hardcode a release number in `@since` tags for new code. Use `@since $$next-version$$` — release tooling replaces the placeholder on version bump.
- **Coding standards**: Follow the WordPress coding standards for the language you're touching — [PHP](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), [JavaScript](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/), [CSS](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/), [HTML](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/). See **Linting** above for the enforcing commands; HTML has no linter, so apply that standard by hand.
- **Documenting hooks and functions**: Document new or updated actions/filters and public functions with docblocks (params, return, `@since` per the version-placeholder rule above). When a change adds or updates a hook, describe it and apply the `Hooks` label; when it deprecates code, name the replacement and apply the `Deprecation` label.
- **Minimum supported versions**: The `sensei-lms.php` plugin header is the source of truth — `Requires PHP` is the minimum PHP and `Requires at least` is the minimum WordPress. Test changes against those minimums, not only the latest — use the `make up WP=… PHP=…` override (see Development environment) before pushing anything version-sensitive.
