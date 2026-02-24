# AGENTS.md

Sensei LMS is an open-source Learning Management System plugin for WordPress, built by Automattic. It enables creating and managing online courses, lessons, quizzes, and student progress tracking. Requires WordPress 6.7+ and PHP 7.4+.

## Commands

### Build & Development
- `npm run build:assets` — Production build of JS/CSS assets via webpack
- `npm start` — Dev mode with watch and source maps
- `npm run build` — Full production build (assets + PHP deps + archive)

### Testing
- `npm run test-php` — Run all PHPUnit tests
- `npm run test-php tests/unit-tests/test-class-admin` — Run a single PHP test file
- `npm run test-js` — Run all Jest tests
- `npm run test-js -- --testPathPattern=path/to/test` — Run a single Jest test
- `npm run test-js:watch` — Jest in watch mode
- `npm run test:e2e` — Playwright E2E tests (requires `npm run wp-env start`)

### Linting
- `npm run lint-php` — PHP CodeSniffer (WordPress coding standards)
- `npm run lint-js` — ESLint (WordPress ESLint plugin + Prettier)
- `npm run lint-css` — SCSS linting
- `npm run format` — Auto-format all code

### Other
- `npm run wp-env start` — Start local WordPress dev environment
- `npm run changelog` — Add a changelog entry (Jetpack Changelogger)
- `npm run i18n:build` — Generate translation POT file

## Architecture

### Entry Point & Singleton
`sensei-lms.php` is the main plugin file. It defines constants (`SENSEI_LMS_VERSION`, `SENSEI_LMS_PLUGIN_FILE`, `SENSEI_LMS_PLUGIN_PATH`), loads Composer autoloading, and returns the `Sensei_Main` singleton via the global `Sensei()` function. `Sensei_Main` lives in `includes/class-sensei.php`.

### Custom Post Types
Registered in `includes/class-sensei-posttypes.php`:
- **course** — Courses
- **lesson** — Lessons (belong to a course)
- **quiz** — Quizzes (attached to lessons)
- **question** — Quiz questions
- **multiple_question** — Question groups
- **sensei_message** — Student/teacher messaging

### Key Subsystems in `includes/`
- `internal/` — Core domain: student progress (course/lesson/quiz), quiz submissions (answers, grades), email customization, installer/migrations, Action Scheduler integration
- `enrolment/` — Course enrolment system
- `blocks/` — 40+ Gutenberg blocks for course/lesson/quiz editing
- `rest-api/` — WordPress REST API endpoints
- `admin/` — Admin interface, setup wizard
- `data-port/` — Course import/export
- `reports/` — Analytics and reporting
- `emails/` — Email notification system with custom post type (`sensei_email`)
- `background-jobs/` — Async job processing via Action Scheduler
- `course-theme/` — Course-specific theme (Learning Mode)
- `course-video/` — Video integration features

### Frontend Assets
- Source: `assets/js/`, `assets/css/`, `assets/blocks/`
- Output: `assets/dist/`
- ~147 webpack entry points (see `webpack.config.js`)
- SVG icons in `assets/icons/` are compiled into a sprite
- Block editor components live in `assets/blocks/` (43 block directories)
- Shared React hooks and components in `assets/shared/` and `assets/react-hooks/`

### Third-Party Dependency Scoping
All Composer production dependencies are scoped with `Sensei\ThirdParty` namespace prefix via php-scoper to avoid conflicts with other plugins. Configuration is in `config/scoper.inc.php`. When adding a new Composer package: add it as a dev dependency, configure it in `config/scoper.inc.php`, then run `composer dump-autoload`.

### Test Structure
- PHP tests: `tests/unit-tests/` mirrors `includes/` directory structure
- Test framework helpers: `tests/framework/` (factories, traits for login, REST API, enrolment, cron, scheduler)
- JS tests: Co-located `*.test.js` files alongside source
- E2E tests: `tests/e2e-playwright/`
- Bootstrap (`tests/bootstrap.php`) loads WP test suite from `/tmp/wordpress-tests-lib`

## Conventions

### Code Style
- **PHP**: WordPress Coding Standards (WPCS). See `phpcs.xml.dist` for full ruleset.
- **JS/TS**: `@wordpress/eslint-plugin/recommended` + Prettier.
- **CSS**: SCSS with WordPress Prettier config.
- **Indentation**: Tabs (4-width) for code, spaces (2-width) for JSON/YAML.

### PHP Inline Documentation
Follow the [WordPress PHP Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/).

### Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| PHP functions | `snake_case` with prefix | `sensei_get_course()` |
| PHP classes | `PascalCase_With_Underscores` with prefix | `Sensei_Course` |
| Global prefixes | `sensei` or `woothemes` (legacy) | `sensei_register_post_types()` |
| Custom capabilities | `snake_case` | `manage_sensei`, `edit_course`, `edit_lesson` |
| Text domain | Always `sensei-lms` | `__( 'Courses', 'sensei-lms' )` |
| React components | `PascalCase` | `CourseList` |
| Block directories | `lowercase-hyphens` | `assets/blocks/course-list/` |

### Translations
All user-facing strings MUST be translatable using `__()`, `_e()`, `_n()`, etc. with text domain `sensei-lms`. Never concatenate translatable strings — use `sprintf()` with placeholders instead.

### Testing
- Prefer `assertSame()` over `assertEquals()` (tests type + equality).
- Only methods prefixed with `test` run as test cases.
- **CRITICAL**: WordPress filters persist between test cases. Always remove filters added during a test in `tearDown()` or the test will leak state into other tests.

### Changelogs
Use `npm run changelog` to add entries via Jetpack Changelogger (stored in `changelog/` directory). Every user-facing change needs a changelog entry.

### Git Workflow
- **Branch naming**: `type/description` — e.g. `fix/course-average-query`, `add/show-tailored-course-outline`, `feature/ai-make-quiz`, `change/add-rest-api-params`
- **Commit messages**: Imperative mood, sentence case — e.g. "Fix XSS vulnerability in Contact Teacher block"
- **PRs**: Must reference an issue (`Resolves #123`), include testing instructions, and follow the template in `.github/PULL_REQUEST_TEMPLATE.md`

## Architectural Decisions

- **Singleton pattern**: `Sensei_Main` is accessed via `Sensei()` globally. Don't instantiate it directly.
- **Enrolment is separate from progress**: A student can have progress on a course but not be enrolled (and vice versa). The enrolment system in `includes/enrolment/` handles this distinction. Don't conflate the two.
- **Blocks over shortcodes**: New UI features should use Gutenberg blocks (`assets/blocks/`), not shortcodes. Existing shortcodes are maintained for backward compatibility only.
- **Action Scheduler over wp-cron**: Background/async processing uses Action Scheduler (`includes/background-jobs/`), not raw wp-cron.
- **Scoped dependencies**: All Composer packages go through php-scoper with the `Sensei\ThirdParty` prefix. Never reference an unscoped third-party namespace directly.

## Common Pitfalls

- **CRITICAL: Do NOT modify WordPress core files.** This is a plugin — all changes must live within the plugin directory.
- **Do NOT edit files in `assets/dist/`.** These are generated by webpack. Edit source files in `assets/js/`, `assets/css/`, or `assets/blocks/` and run `npm run build:assets`.
- **Do NOT edit files in `vendor/`.** These are managed by Composer. Run `composer install` or `composer update` to modify dependencies.
- **Do NOT reference third-party classes without the `Sensei\ThirdParty\` prefix.** The scoper renames all vendor namespaces. For example, use `Sensei\ThirdParty\Action_Scheduler` not `Action_Scheduler` directly.
- **Filters leaking between tests.** If you add a WordPress filter in a test, remove it in `tearDown()`. This is the most common cause of flaky tests.
- **Forgetting the text domain.** All user-facing strings need `'sensei-lms'` as the text domain. Lint will catch this, but save a round-trip by getting it right the first time.
- **Confusing enrolment with progress.** Students can have course progress without being enrolled (e.g. after manual enrolment removal). Always check enrolment status via the enrolment provider system, not by looking at progress tables.
- **Using `assertEquals()` instead of `assertSame()`.** PHPUnit's `assertEquals()` does loose comparison. We use strict `assertSame()` to catch type mismatches.

## Boundaries

### Always Do
- Run `npm run lint-php` and `npm run lint-js` before committing
- Sanitize input, escape output (`esc_html`, `esc_attr`, `wp_kses_post`)
- Use nonce verification for forms and AJAX requests
- Wrap user-facing strings in translation functions with `sensei-lms` text domain
- Add a changelog entry for user-facing changes (`npm run changelog`)
- Write tests for new functionality

### Ask First
- Database schema changes or direct database queries
- Adding npm or Composer dependencies
- Changes to webpack configuration or build process
- Modifying the enrolment provider system
- Changes affecting the REST API contract

### Never Do
- Modify WordPress core files
- Commit secrets, API keys, or credentials
- Edit generated files (`assets/dist/`, `vendor/`)
- Use `extract()`, `eval()`, or `create_function()`
- Concatenate translatable strings (use `sprintf()` with placeholders)
- Skip the `Sensei\ThirdParty\` namespace prefix for vendor classes
