## Repository layout
- `includes/` — main plugin PHP source.
- `assets/` — JS/CSS source, blocks, and built artifacts under `assets/dist/`.
- `tests/unit-tests/` — PHPUnit suite.
- `tests/e2e-playwright/` — Playwright end-to-end suite (see "End-to-end" below).
- `changelog/` — per-PR changelog entries (added via `npm run changelog`).
- `config/scoper.inc.php` — php-scoper config used during the build.
- `scripts/linter-ci` — the diff-based PHPCS runner used by the lint command and CI.
- `.github/workflows/` — CI definitions; PR previews are built by `playground-preview.yml`.

## Development environment
- Run all dev commands inside the wp-env Docker sandbox rather than against any host WordPress install — this keeps mistakes off shared/local state.
- Use the Node version pinned in `.nvmrc`.
- Ensure Docker Desktop (or Colima) is running before starting wp-env; verify with `docker info`.
- Start the env: `npx wp-env start --update`.
- Stop the env: `npx wp-env stop`. Wipe and recreate from scratch: `npx wp-env destroy`.
- Open a shell in the WordPress container: `npx wp-env run cli bash`.
- Run WP-CLI: `npx wp-env run cli wp <command>` (e.g. `npx wp-env run cli wp plugin list`).
- WordPress / PHP version overrides: write a `.wp-env.override.json` such as `{ "core": "WordPress/WordPress#6.8-branch", "phpVersion": "8.3" }` before `npx wp-env start --update`.

## Building
- Avoid `npm run build` directly on the host: `humbug/php-scoper@0.15.0` ships an old `symfony/console` whose `HelperSet::getIterator()` is incompatible with PHP 8.1+ return-type checks. Build via wp-env instead:
  ```
  npm run build:assets
  rm -f assets/dist/css/jquery-ui.js
  npx wp-env run cli --env-cwd=wp-content/plugins/sensei composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts
  npm run archive
  ```
- The `--no-dev` step strips dev dependencies (including `vendor/bin/phpunit`). After a build, restore dev tooling before running PHPUnit:
  ```
  npx wp-env run cli --env-cwd=wp-content/plugins/sensei composer install
  ```

## Testing
- **Test-driven development**: Follow a TDD approach for new behavior and bug fixes — write a failing test that captures the desired behavior first, then implement until it passes. This applies to PHPUnit, JS unit, and (where practical) Playwright suites.
- **PHPUnit**: `npm run test-php:wp-env` (runs inside wp-env). Targeted runs: `npm run test-php:wp-env -- --filter "TestClass"` or `--filter "TestClass::method"`.
- **PHPUnit with HPPS enabled**: `npm run test-php:wp-env:hpps`.
- **JS unit tests**: `npm run test-js`.
- **End-to-end (Playwright)**: `npm run test:e2e` runs headless against the wp-env stack; `npm run test:e2e:debug` runs headed. Requires wp-env to be running first. The `pretest:e2e` hook runs ESLint + `tsc` before Playwright launches — failures there appear before any test output. See `tests/e2e-playwright/README.md` for details.
- **Ad-hoc UI verification**: Once wp-env is up, the dev site is at `http://localhost:8888` with admin credentials `admin` / `password` (wp-env defaults). For agent-driven verification, use the `/e2e-testing` skill (`.claude/skills/e2e-testing/SKILL.md`) — it scopes from `git diff`, lists Sensei's admin/frontend surfaces, and walks the Chrome DevTools MCP through the relevant flow.

## Linting
- **PHPCS**: Run `./scripts/linter-ci` (the same diff-based check CI uses; requires a clean working tree). Whole-codebase scans: `./vendor/bin/phpcs`.
- **Psalm**: Run `vendor/bin/psalm --no-cache --diff`. CI runs the full PHP-version matrix from `.github/workflows/psalm.yml`; type narrowing differs between versions, so for changes touching type-sensitive code, run Psalm against each matrix PHP version locally (each requires the matching `php@<version>` brew formula on `PATH`).
- **Before pushing**: The pre-commit hook only lints PHP files added after 2020-01-01. CI lints all changed lines. Always run PHPCS and Psalm on modified files before pushing to avoid CI failures.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry before opening a PR. Run `npm run changelog` (entries stored in `changelog/`). For purely internal changes (refactors, test-only changes), apply the `No Changelog` label to the PR instead.
- **Version placeholders in docblocks**: Never hardcode a release number in `@since` tags for new code. Use `@since $$next-version$$` — release tooling replaces the placeholder on version bump.
- **Array syntax**: Use the long form `array( ... )` in new PHP code, per the [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#declaring-arrays). Sensei's `phpcs.xml.dist` excludes the short-array disallow sniff, so `[ ... ]` won't fail lint, but new code should follow the upstream standard anyway. Do not mass-convert existing code.
- **Test assertions**: When a single test has multiple assertions, pass a message as the third argument to each assertion (e.g., `self::assertSame( $expected, $actual, 'Foo should be updated.' );`) so a failure points to the specific check that failed.
