## Linting
- **PHPCS**: Run `npm run lint-php`.
- **Psalm**: Run `vendor/bin/psalm --no-cache --diff`.
- **Before pushing**: The pre-commit hook only lints new files. CI lints all changed lines. Always run both PHPCS and Psalm on modified files before pushing to avoid CI failures.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry before opening a PR. Run `npm run changelog` (entries stored in `changelog/`).
- **Version placeholders in docblocks**: Never hardcode a release number in `@since` tags for new code. Use `@since $$next-version$$` — release tooling replaces the placeholder on version bump.
