# AGENTS.md

Sensei LMS is a WordPress plugin for creating and managing online courses, lessons, quizzes, and student progress. Built with PHP + React/Gutenberg blocks. Source in `includes/` (PHP) and `assets/` (JS/CSS/blocks), output in `assets/dist/`.

## Commands

- `npm start` — Dev mode with watch
- `npm run build:assets` — Production build of JS/CSS
- `npm run wp-env start` — Start local WordPress dev environment
- `npm run test-php:wp-env` — PHPUnit tests (runs inside wp-env container)
- `npm run test-php:wp-env -- --filter TestClassName` — Single test class
- `npm run test-js` — Jest tests
- `npm run test-js -- --testPathPattern=path/to/test` — Single Jest test
- `npm run test:e2e` — Playwright E2E tests (requires wp-env running)
- `npm run lint-php` — PHP CodeSniffer
- `npm run lint-js` — ESLint + Prettier
- `npm run lint-js:fix` — Auto-fix JS/CSS lint errors
- `npm run lint-css:fix` — Auto-fix SCSS lint errors
- `npm run changelog` — Add changelog entry (Jetpack Changelogger)

Always run linters and tests before committing to catch issues early.

## Third-Party Dependency Scoping

All Composer production dependencies are scoped with `Sensei\ThirdParty` namespace prefix via php-scoper to avoid conflicts with other plugins. **Never reference an unscoped third-party namespace directly** — e.g. use `Sensei\ThirdParty\Action_Scheduler`, not `Action_Scheduler`. When adding a new Composer package: add it as a dev dependency, configure it in `config/scoper.inc.php`, then run `composer dump-autoload`.

## Key Architectural Decisions

- **Enrolment is separate from progress**: A student can have progress on a course but not be enrolled (and vice versa). The enrolment system in `includes/enrolment/` handles this. Don't conflate the two — always check enrolment status via the enrolment provider system, not by looking at progress tables.
- **Blocks over shortcodes**: New UI features should use Gutenberg blocks (`assets/blocks/`), not shortcodes. Existing shortcodes are maintained for backward compatibility only.
- **Action Scheduler over wp-cron**: Background/async processing uses Action Scheduler (`includes/background-jobs/`), not raw wp-cron.

## Common Pitfalls

- **CRITICAL: WordPress filters persist between test cases.** Always remove filters added during a test in `tearDown()` or the test will leak state into other tests.
- **Use `assertSame()` over `assertEquals()`** — strict type + equality comparison.
- **Text domain is `sensei-lms`** (not `sensei`). All user-facing strings MUST use this.
- **Never concatenate translatable strings** — use `sprintf()` with placeholders.
- **Never use `extract()`, `eval()`, or `create_function()`.**
- **Never skip the `Sensei\ThirdParty\` namespace prefix** for vendor classes.

## Conventions

- **Branch naming**: `type/description` — e.g. `fix/course-average-query`, `add/show-tailored-course-outline`, `feature/ai-make-quiz`
- **PRs**: Must reference an issue (`Resolves #123`), include testing instructions, and follow `.github/PULL_REQUEST_TEMPLATE.md`.
- **Changelogs**: Every user-facing change MUST have a changelog entry before opening a PR. Run `npm run changelog` (entries stored in `changelog/`).

## Boundaries

### Ask First
- Database schema changes or direct database queries
- Adding npm or Composer dependencies
- Changes to webpack configuration or build process
- Modifying the enrolment provider system
- Changes affecting the REST API contract
