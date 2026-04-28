See `make help` for the full list of available dev commands.

## Linting
- **PHPCS**: Run `make lint` (the same diff-based check CI uses; requires a clean working tree). Whole-codebase scans: `./vendor/bin/phpcs`.
- **Psalm**: Run `make psalm`. CI runs Psalm against every PHP version in `.github/workflows/psalm.yml`'s matrix; type narrowing differs between versions, so run Psalm under each of those versions before pushing — `make psalm` uses the active PHP, so for the matrix run the underlying command directly: `PATH="/opt/homebrew/opt/php@<version>/bin:$PATH" vendor/bin/psalm --no-cache --diff`.
- **Before pushing**: The pre-commit hook only lints PHP files added after 2020-01-01. CI lints all changed lines. Always run PHPCS and the full Psalm matrix on modified files before pushing to avoid CI failures.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry before opening a PR. Run `make changelog` (entries stored in `changelog/`). For purely internal changes (refactors, test-only changes), apply the `No Changelog` label to the PR instead.
- **Version placeholders in docblocks**: Never hardcode a release number in `@since` tags for new code. Use `@since $$next-version$$` — release tooling replaces the placeholder on version bump.
- **Array syntax**: Use the long form `array( ... )` in new PHP code, per the [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#declaring-arrays). Sensei's `phpcs.xml.dist` excludes the short-array disallow sniff, so `[ ... ]` won't fail lint, but new code should follow the upstream standard anyway. Do not mass-convert existing code.
- **Test assertions**: When a single test has multiple assertions, pass a message as the third argument to each assertion (e.g., `self::assertSame( $expected, $actual, 'Foo should be updated.' );`) so a failure points to the specific check that failed.
