# AGENTS.md

Sensei LMS is an open-source Learning Management System plugin for WordPress, built by Automattic. It enables creating and managing online courses, lessons, quizzes, and student progress tracking. Requires WordPress 6.7+ and PHP 7.4+.

## Common Commands

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

## Coding Conventions

- **PHP**: WordPress Coding Standards (WPCS). Custom capabilities: `manage_sensei`, `edit_course`, `edit_lesson`.
- **JS/TS**: `@wordpress/eslint-plugin/recommended` + Prettier. Text domain: `sensei-lms`.
- **CSS**: SCSS with WordPress Prettier config.
- **Indentation**: Tabs (4-width) for code, spaces (2-width) for JSON/YAML.
- **Testing**: Prefer `assertSame()` over `assertEquals()` (tests type + equality). Only methods prefixed with `test` run. Filters persist between test cases — remove them in `tearDown()`.
- **Changelogs**: Use `npm run changelog` to add entries via Jetpack Changelogger (stored in `changelog/` directory).
- **Translations**: All user-facing strings must be translatable using `__()`, `_e()`, `_n()`, etc. with text domain `sensei-lms`. No string concatenation for translatable text.
