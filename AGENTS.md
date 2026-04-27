## Linting
- **PHPCS**: Run `npm run lint-php`.
- **Psalm**: Run `vendor/bin/psalm --no-cache --diff`. CI runs Psalm against every PHP version in `.github/workflows/psalm.yml`'s matrix; type narrowing differs between versions, so run Psalm under each of those versions before pushing (e.g. `PATH="/opt/homebrew/opt/php@<version>/bin:$PATH" vendor/bin/psalm --no-cache --diff`).
- **Before pushing**: The pre-commit hook only lints new files. CI lints all changed lines. Always run PHPCS and the full Psalm matrix on modified files before pushing to avoid CI failures.

## Conventions
- **Changelogs**: Every user-facing change MUST have a changelog entry before opening a PR. Run `npm run changelog` (entries stored in `changelog/`).
- **Version placeholders in docblocks**: Never hardcode a release number in `@since` tags for new code. Use `@since $$next-version$$` — release tooling replaces the placeholder on version bump.
- **Array syntax**: Use the long form `array( ... )` in new PHP code, per the [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#declaring-arrays). Sensei's `phpcs.xml.dist` excludes the short-array disallow sniff, so `[ ... ]` won't fail lint, but new code should follow the upstream standard anyway. Do not mass-convert existing code.
