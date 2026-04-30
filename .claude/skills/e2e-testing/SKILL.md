---
name: e2e-testing
description: Use when verifying a Sensei UI or behavior change end-to-end against the running wp-env site. Boots / checks the env, scopes verification from `git diff`, drives the Sensei admin and frontend surfaces via Chrome DevTools MCP, and captures screenshots. Complements `npm run test:e2e` (full Playwright regression).
---

# Sensei E2E Verification

This skill verifies that a change actually behaves correctly from a user's perspective. Use it after the unit-test loop passes and before relying on Playwright for full regression.

## Prerequisites

- `make up` is running. The wp-env dev site listens on `http://localhost:8888` (admin: `admin` / `password` — wp-env defaults).
- The active theme is `course` (`Automattic/themes/course`, pinned in `.wp-env.json`). All frontend verification should run against it.
- Chrome DevTools MCP tools (`mcp__chrome-devtools__*`) are available.
- For PHP changes that touch built assets, run `npm run build:assets` first. For changes that affect the scoped vendor tree, run `make build`.

## Sensei surface map

The most common surfaces ranked by what a Sensei change typically touches:

| Area | Path | Verify when changing |
|------|------|----------------------|
| Sensei Home | `/wp-admin/admin.php?page=sensei` | Top-level dashboard, quick links, onboarding |
| Setup Wizard | `/wp-admin/admin.php?page=sensei_setup_wizard` | First-run flow |
| Settings | `/wp-admin/admin.php?page=sensei-settings` | Settings save/load, tab routing |
| Tools | `/wp-admin/admin.php?page=sensei-tools` | Diagnostic / repair actions |
| Reports | `/wp-admin/admin.php?page=sensei_reports` | Analytics output, filters |
| Students (Learners) | `/wp-admin/admin.php?page=sensei_learners` | Enrollment management, learner search |
| Grading | `/wp-admin/admin.php?page=sensei_grading` | Manual grading list, filters, status counts |
| Courses CPT | `/wp-admin/edit.php?post_type=course` | Course list table, columns, filters |
| Lessons CPT | `/wp-admin/edit.php?post_type=lesson` | Lesson list table, columns |
| Modules taxonomy | `/wp-admin/edit-tags.php?taxonomy=module&post_type=course` | Module CRUD |
| Course editor | `/wp-admin/post.php?post={id}&action=edit` | Course blocks, structure, settings panel |
| Lesson editor | same with a lesson ID | Lesson blocks, embedded quiz structure |
| Course archive | `/courses/` | Frontend listing, theme integration |
| Single course | `/course/{slug}/` | Take-course button, progress, prerequisites |
| Single lesson | `/lesson/{slug}/` | Complete-lesson button, quiz embed |

## Workflow

### 1. Scope from the diff

```bash
git diff trunk...HEAD --name-only
```

Map changed files to surfaces using the table above. Skip surfaces the diff doesn't touch.

Heuristics:
- `includes/admin/` or files under `assets/admin/` → admin surfaces.
- `includes/blocks/` or `assets/blocks/` → both editor and frontend.
- `includes/internal/quiz-submission/`, `includes/quiz/`, `includes/lesson/` → quiz/lesson flows; verify both editor and frontend.
- `includes/internal/services/class-comments-based-*` or anything HPPS-related → run with HPPS too (see step 5).
- `includes/rest-api/` → check the surface that consumes the endpoint, not just the endpoint.

### 2. Confirm the env is live

```bash
curl -sI http://localhost:8888 | head -1
```

If empty, run `make up` and wait ~30–60s on a warm Docker daemon (longer on a cold start).

### 3. Seed enough test data

wp-env starts empty. For most flows you'll need at least one published course with one lesson. Quick seed:

```bash
make wp CMD="post create --post_type=course --post_title='Sensei E2E' --post_status=publish --porcelain"
# capture COURSE_ID
make wp CMD="post create --post_type=lesson --post_title='Lesson 1' --post_status=publish --post_parent=COURSE_ID --porcelain"
```

For quiz/grading flows you'll also need a quiz attached to the lesson with at least one question. Bare `wp post create` doesn't set up the quiz structure (questions, settings, lesson↔quiz linkage), so for those flows mirror the factories in `tests/e2e-playwright/factories/`.

### 4. Drive the surfaces

Per in-scope surface:

1. Authenticate once: `mcp__chrome-devtools__new_page` to `http://localhost:8888/wp-login.php`, fill `admin` / `password`, click Log In.
2. Navigate to the surface URL.
3. `mcp__chrome-devtools__take_snapshot` for DOM (lets you target by ID/role) and `mcp__chrome-devtools__take_screenshot` for visual.
4. Drive any specific interaction the change requires (click, fill, navigate). After each interaction, take another screenshot.
5. Watch `mcp__chrome-devtools__list_console_messages` for new JS errors introduced by the change.

### 5. HPPS variant where relevant

If the change touches grading, lesson/course progress, comment-meta paths, or analytics, also verify with HPPS enabled (the project's High-Performance Progress Storage). At minimum, run the HPPS PHPUnit variant:

```bash
npm run test-php:wp-env:hpps
```

For browser verification with HPPS, follow the toggle pattern used by `tests/e2e-playwright/specs/` (the existing suite already wires this up).

### 6. Persist artifacts

Save screenshots to `.claude/tmp/screenshots/YYYY-MM-DD-<surface>.png`. The `.claude/tmp/` tree is gitignored but does not exist in a fresh checkout — create it first:

```bash
mkdir -p .claude/tmp/screenshots
```

Reference saved screenshots in the PR description if useful.

## Common Sensei-specific failure modes

- **Action Scheduler-driven side effects don't fire on click.** Sensei queues many flows (course completion emails, lesson progress recalc, enrollment recalculation, retroactive enrolment recalc) via Action Scheduler. The action runs on the next WP-Cron tick, not on the request that scheduled it. To force a sweep:
  ```bash
  make wp CMD="action-scheduler run"
  ```
- **Course theme not active.** If the frontend looks unstyled, confirm:
  ```bash
  make wp CMD="theme list --status=active"
  ```
  The active theme should be `course`. Re-activate with `make wp CMD="theme activate course"`.
- **Stale built assets.** wp-env mounts the plugin live, but JS/CSS in `assets/dist/` need a rebuild after source changes: `npm run build:assets`. The browser may also need a hard reload.
- **Comment-meta vs. tables drift on grading.** When verifying grading flows, check both the listing page (counts/filters) and the per-quiz "Review Grade" detail page; the two query paths can diverge during HPPS migrations.
- **Empty Reports.** Reports require at least one enrolled student with progress. Create a second wp-env user (`make wp CMD="user create student student@example.com --role=subscriber"`) and enroll them programmatically before verifying reports.

## When to skip this skill

- Pure refactor with full unit-test coverage and no behavior change.
- Documentation, changelog, or build-only changes.
- Changes already covered by a targeted Playwright spec — run that spec instead:
  ```bash
  npm run test:e2e -- tests/e2e-playwright/specs/<spec>.spec.ts
  ```
