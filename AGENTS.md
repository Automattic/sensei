## Linting
- **PHPCS**: Run `npm run lint-php`.
- **Psalm**: Run `vendor/bin/psalm --no-cache --diff`.
- **Before pushing**: The pre-commit hook only lints new files. CI lints all changed lines. Always run both PHPCS and Psalm on modified files before pushing to avoid CI failures.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry before opening a PR. Run `npm run changelog` (entries stored in `changelog/`).
- **Test assertions**: When a single test has multiple assertions, pass a message as the third argument to each assertion (e.g., `self::assertSame( $expected, $actual, 'Foo should be updated.' );`) so a failure points to the specific check that failed.
