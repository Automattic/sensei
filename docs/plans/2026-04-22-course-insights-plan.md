# Course Insights Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers-extended-cc:executing-plans to implement this plan task-by-task.

**Goal:** Ship an instructor-facing course-insights feature (course / lesson / quiz drill-down, Home card tile grid) in Sensei core, fully behind a feature flag, using TDD throughout.

**Architecture:** Insights service layer sits on top of HPPS + comments-based insights repositories (shared contract, parity-tested). New REST controller at `sensei-internal/v1/course-insights/*` plus a new Home provider extending the existing `/home` endpoint. React UI under Reports IA. Transient cache, legacy path optimized.

**Tech stack:** PHP 7.4+, WordPress REST, React (existing Sensei admin React tooling), `@wordpress/scripts`, PHPUnit, Jest / React Testing Library, chart library TBD (shortlist: `Automattic/charts`).

**Design doc:** `docs/plans/2026-04-22-course-insights-design.md`

**Discipline:** TDD (red → green → refactor), commit after every green. DRY, YAGNI. Every task guarded by the `course_insights` feature flag in anything the end user can reach.

---

## Phase 1 — Foundation

### Task 1: Add `course_insights` feature flag

**Files:**
- Modify: `includes/class-sensei-feature-flags.php:33-51`
- Test: `tests/unit-tests/test-class-sensei-feature-flags.php`

**Step 1 — Write failing test.** Assert `is_enabled( 'course_insights' )` returns the environment-aware default (false in production, true in development) and can be overridden by constant and filter.

```php
public function testCourseInsightsFlagDefaultsOffInProduction() {
    // Arrange environment via filter.
    add_filter( 'sensei_default_feature_flag_settings', function() {
        return [ 'course_insights' => false ];
    });
    $flags = new Sensei_Feature_Flags();
    $this->assertFalse( $flags->is_enabled( 'course_insights' ) );
}
```

**Step 2 — Run, expect fail:** `vendor/bin/phpunit --filter testCourseInsightsFlagDefaultsOffInProduction`

**Step 3 — Add key to both `production` and `development` arrays:** `production => false`, `development => true`.

**Step 4 — Run, expect pass.**

**Step 5 — Commit:** `feat(insights): add course_insights feature flag`.

---

### Task 2: Create insights repository interface

**Files:**
- Create: `includes/internal/insights/repositories/class-insights-repository-interface.php`
- Test: will be exercised via shared contract tests in Task 5.

**Step 1 — Define the interface with these methods (all aggregate, all return arrays of scalars):**

```php
interface Insights_Repository_Interface {
    // Overview list: top courses by $sort with $limit, filtered by $window.
    public function get_course_summaries( string $sort, int $limit, string $window, array $course_ids = [] ): array;

    // Single course funnel + summary.
    public function get_course_detail( int $course_id, string $window ): array;

    // Single lesson breakdown.
    public function get_lesson_detail( int $course_id, int $lesson_id, string $window ): array;

    // Single quiz detail: summary + per-question + score distribution.
    public function get_quiz_detail( int $course_id, int $quiz_id, string $window ): array;

    // Cohort trend (always 12 months, not windowed).
    public function get_course_cohort_trend( int $course_id ): array;
}
```

**Step 2 — Commit:** `feat(insights): add repository interface`.

(No test yet — interface only. Contract tests come in Task 5.)

---

### Task 3: Scaffold the Insights namespace

**Files:**
- Create: `includes/internal/insights/` (directory tree with empty `.gitkeep`s only in folders we'll populate)
- Create: `includes/internal/insights/repositories/`
- Create: `includes/internal/insights/services/`

**Step 1 — Create directories.** No PHP yet beyond Task 2's interface.

**Step 2 — Commit:** `chore(insights): scaffold namespace`.

---

### Task 4: Feature-flag the insights bootstrapper (stub)

**Files:**
- Create: `includes/internal/insights/class-sensei-insights.php`
- Modify: `includes/class-sensei.php` (add instantiation guarded by the flag)
- Test: `tests/unit-tests/internal/insights/test-class-sensei-insights.php`

**Step 1 — Write failing test.** `Sensei_Insights::init()` must be a no-op when flag is off and register nothing on REST/enqueue.

```php
public function testDoesNothingWhenFlagDisabled() {
    add_filter( 'sensei_feature_flag_course_insights', '__return_false' );
    $insights = new Sensei_Insights();
    $insights->init();
    $this->assertEquals( 10, has_action( 'rest_api_init', [ $insights, 'register_rest_controller' ] ) === false ? 10 : -1 );
    // Stronger: use the WP hooks snapshot pattern used elsewhere in the suite.
}
```

**Step 2 — Run, expect fail.**

**Step 3 — Implement `Sensei_Insights::init()` that returns early when flag is off.**

**Step 4 — Run, expect pass.**

**Step 5 — Commit:** `feat(insights): feature-flagged bootstrapper`.

---

## Phase 2 — Repository parity (TDD contract first)

### Task 5: Shared contract test suite (trait)

**Files:**
- Create: `tests/unit-tests/internal/insights/repositories/trait-insights-repository-contract.php`

Shared trait containing all behavioral tests. Both implementations' test classes will `use` it.

**Step 1 — Write contract tests for `get_course_summaries`:**
- Returns empty array when no enrollments.
- Sorts correctly by `enrollment_desc`, `completion_asc`, `completion_desc`, `title_asc`.
- Respects `limit`.
- `window='30d'` excludes enrollments older than 30 days.
- Response rows include `id`, `title`, `enrollment_count`, `completion_pct`, `biggest_drop_off_lesson_id` (nullable), `mini_funnel` (array of `{lesson_id, pct_reached}`).

**Step 2 — Repeat for `get_course_detail`, `get_lesson_detail`, `get_quiz_detail`, `get_course_cohort_trend`.** One test per behavior, small and specific. Aim for ~30-40 test methods in the trait.

**Step 3 — Commit:** `test(insights): shared repository contract`.

(Both impls will now fail this contract until built.)

---

### Task 6: Comments-based repository — overview (summaries) query

**Files:**
- Create: `includes/internal/insights/repositories/class-comments-based-insights-repository.php`
- Create: `tests/unit-tests/internal/insights/repositories/test-class-comments-based-insights-repository.php` that `uses Insights_Repository_Contract`.

**Step 1 — Run the contract test class, confirm ALL contract tests fail.**

**Step 2 — Implement `get_course_summaries` using `wp_comments` + `wp_commentmeta`:**
- Filter: `comment_type = 'sensei_course_status'`, `comment_post_ID IN (…)`.
- Enrollment count: `COUNT(*)` grouped by `comment_post_ID`.
- Completion %: `SUM(comment_approved = 'complete') / COUNT(*) * 100`.
- Window filter: JOIN `commentmeta` for `meta_key = 'start'`, compare `meta_value` with cutoff.
- Biggest drop-off: subquery on lesson statuses (or compute in PHP after fetching per-course lesson counts if the joined query is slow — benchmark).
- Mini-funnel: separate `get_mini_funnel( int $course_id )` helper, called per course in the result (N+1 acceptable for 5 rows; guard with cache).

**Step 3 — Run contract tests for `get_course_summaries`, expect pass.**

**Step 4 — Commit:** `feat(insights): comments-based summaries query`.

---

### Task 7: Comments-based repository — course detail query

**Files:**
- Modify: `includes/internal/insights/repositories/class-comments-based-insights-repository.php`

**Step 1 — Implement `get_course_detail`:**
- Summary numbers (enrolled/active/completed/% complete).
- Funnel: one query over `comment_type = 'sensei_lesson_status'` grouped by `comment_post_ID` for all lessons in the course, plus lesson order from post metadata.
- Biggest drop-off = lesson with largest `pct_reached - next_pct_reached`.

**Step 2 — Run contract tests for `get_course_detail`, expect pass.**

**Step 3 — Commit:** `feat(insights): comments-based course detail query`.

---

### Task 8: Comments-based repository — lesson detail query

**Step 1 — Implement `get_lesson_detail`:** state counts (not started / in progress / completed) for the lesson, delta-vs-average, and if lesson has a quiz, the quiz summary tile data.

**Step 2 — Run contract tests, pass.**

**Step 3 — Commit:** `feat(insights): comments-based lesson detail query`.

---

### Task 9: Comments-based repository — quiz detail query (summary + distribution)

**Step 1 — Implement quiz summary** (first-attempt pass rate, avg attempts, avg score) and **score distribution** (GROUP BY bucketed `grade` commentmeta).

**Step 2 — Implement per-question table** (the heavy query — aggregate over quiz answers commentmeta).

**Step 3 — Run contract tests, pass.**

**Step 4 — Commit:** `feat(insights): comments-based quiz detail query`.

---

### Task 10: Comments-based repository — cohort trend

**Step 1 — Implement `get_course_cohort_trend`:** 12 monthly buckets of enrollments, each with `completed_pct`. Enrollment month comes from `start` commentmeta; fall back to `comment_date` when missing.

**Step 2 — Run contract tests, pass.**

**Step 3 — Commit:** `feat(insights): comments-based cohort trend query`.

---

### Task 11: Tables-based (HPPS) repository

**Files:**
- Create: `includes/internal/insights/repositories/class-tables-based-insights-repository.php`
- Create: `tests/unit-tests/internal/insights/repositories/test-class-tables-based-insights-repository.php` that `uses Insights_Repository_Contract`.

**Step 1 — Run contract tests on HPPS impl, confirm all fail.**

**Step 2 — Implement each method** using `sensei_lms_progress` table with indexed columns. Queries are structurally simpler than legacy (single-table, no `commentmeta` JOINs).

**Step 3 — Run contract tests, all pass.** (This is the parity moment.)

**Step 4 — Commit:** `feat(insights): tables-based repository`.

---

### Task 12: Repository factory

**Files:**
- Create: `includes/internal/insights/repositories/class-insights-repository-factory.php`
- Test: `tests/unit-tests/internal/insights/repositories/test-class-insights-repository-factory.php`

**Step 1 — Write test:** factory returns `Tables_Based_Insights_Repository` when HPPS-enabled flag is on, `Comments_Based_Insights_Repository` otherwise. Mirror `Course_Progress_Repository_Factory`.

**Step 2 — Run, fail.**

**Step 3 — Implement.** Constructor takes `bool $tables_enabled`.

**Step 4 — Run, pass.**

**Step 5 — Commit:** `feat(insights): repository factory`.

---

### Task 13: Transient caching decorator

**Files:**
- Create: `includes/internal/insights/repositories/class-cached-insights-repository.php`
- Test: `tests/unit-tests/internal/insights/repositories/test-class-cached-insights-repository.php`

**Step 1 — Write tests:**
- First call delegates to inner repo; second call within TTL returns cached value without hitting inner.
- Key shape: `sensei_insights_v1_{method}_{arg-hash}_{window}`.
- `invalidate_course( int $course_id )` deletes matching transients.

**Step 2 — Run, fail.**

**Step 3 — Implement decorator:** wraps any `Insights_Repository_Interface`, uses `get_transient` / `set_transient` with 15-min TTL.

**Step 4 — Run, pass.**

**Step 5 — Commit:** `feat(insights): cached repository decorator`.

---

### Task 14: Wire cache invalidation on post save

**Files:**
- Modify: `includes/internal/insights/class-sensei-insights.php`
- Test: `tests/unit-tests/internal/insights/test-class-sensei-insights.php`

**Step 1 — Write test:** saving a course / lesson / quiz post calls `invalidate_course` for the relevant course.

**Step 2 — Run, fail.**

**Step 3 — Implement** using `save_post_course`, `save_post_lesson`, `save_post_quiz` hooks.

**Step 4 — Run, pass.**

**Step 5 — Commit:** `feat(insights): invalidate cache on content edits`.

---

## Phase 3 — Services

### Task 15: `Course_Insights_Service`

**Files:**
- Create: `includes/internal/insights/services/class-course-insights-service.php`
- Test: `tests/unit-tests/internal/insights/services/test-class-course-insights-service.php`

**Step 1 — Write tests** for the service (with a mocked repository):
- Applies the 5-learner threshold — returns a `low_data` flag when enrollments < 5.
- Wraps repo output with UI-ready fields (e.g. adds a `biggest_drop_off` object that includes the lesson title, not just ID).
- Delta-vs-course-average computed in the service, not the repo.

**Step 2 — Run, fail.**

**Step 3 — Implement.**

**Step 4 — Pass.**

**Step 5 — Commit:** `feat(insights): course insights service`.

---

### Task 16: `Lesson_Insights_Service`

Same shape as Task 15 — lesson-level low-data, adjacency (prev/next lesson IDs), delta-vs-course-average.

**Commit:** `feat(insights): lesson insights service`.

---

### Task 17: `Quiz_Insights_Service`

Same shape — low-data threshold on attempts, per-question sort ascending by pct_correct, bucketing correctness.

**Commit:** `feat(insights): quiz insights service`.

---

## Phase 4 — REST

### Task 18: REST controller skeleton + permission callback

**Files:**
- Create: `includes/rest-api/class-sensei-rest-api-course-insights-controller.php`
- Modify: `includes/rest-api/class-sensei-rest-api-internal.php:46-60` (register when flag on)
- Test: `tests/unit-tests/rest-api/test-class-sensei-rest-api-course-insights-controller.php`

**Step 1 — Write tests:**
- Flag off → routes not registered.
- Flag on → routes registered at `sensei-internal/v1/course-insights`.
- User without `manage_sensei_grades` → 403.

**Step 2 — Fail.**

**Step 3 — Implement skeleton** extending `WP_REST_Controller`, with `rest_base = 'course-insights'` and `can_user_access_rest_api()` checking `manage_sensei_grades` and course ownership (reuse existing helper if one exists).

**Step 4 — Modify `Sensei_REST_API_Internal::register`** to add the controller only when `Sensei_Feature_Flags::is_enabled( 'course_insights' )`.

**Step 5 — Pass.**

**Step 6 — Commit:** `feat(insights): REST controller skeleton, feature-flagged`.

---

### Task 19: `GET /course-insights` overview endpoint

**Files:**
- Modify: controller + test.

**Step 1 — Write tests** for: happy path, empty state, sort param validation, limit param validation, window param validation, cache hit on second call.

**Step 2 — Fail.**

**Step 3 — Implement:** accept `limit`, `sort`, `window` query params; delegate to `Course_Insights_Service` via repo; return JSON.

**Step 4 — Pass.**

**Step 5 — Commit:** `feat(insights): overview endpoint`.

---

### Task 20: `GET /course-insights/{course_id}` endpoint

Mirror Task 19 — params: `window`. Returns course detail + cohort trend (always 12m).

**Commit:** `feat(insights): course detail endpoint`.

---

### Task 21: `GET /course-insights/{course_id}/lessons/{lesson_id}` endpoint

**Commit:** `feat(insights): lesson detail endpoint`.

---

### Task 22: `GET /course-insights/{course_id}/quizzes/{quiz_id}` endpoint

**Commit:** `feat(insights): quiz detail endpoint`.

---

### Task 23: Home provider for course insights

**Files:**
- Create: `includes/admin/home/course-insights/class-sensei-home-course-insights-provider.php`
- Modify: `includes/admin/class-sensei-home.php:91-101` (instantiate only when flag on)
- Modify: `includes/rest-api/class-sensei-rest-api-home-controller.php` (accept + expose the provider in the `/home` response)
- Modify: `includes/admin/class-sensei-home.php:110-121` (pass the new provider to the controller)
- Test: `tests/unit-tests/admin/home/test-class-sensei-home-course-insights-provider.php`
- Test: `tests/unit-tests/rest-api/test-class-sensei-rest-api-home-controller.php`

**Step 1 — Write tests:**
- Provider returns the top-5-by-enrollment shape.
- `/home` payload contains `course_insights` key when flag on.
- `/home` payload omits `course_insights` key when flag off (no breaking change).

**Step 2 — Fail.**

**Step 3 — Implement.** Provider wraps the same `Course_Insights_Service` used by the REST controller. Keep serialization logic in the provider; don't duplicate service logic.

**Step 4 — Pass.**

**Step 5 — Commit:** `feat(insights): Sensei Home provider for course insights`.

---

## Phase 5 — React scaffolding

### Task 24: Chart library prototype spike

**Files:**
- Create: `assets/admin/course-insights/prototype/funnel.stories.js` (or equivalent, depending on whether we use Storybook).

**Step 1 — Time-boxed (~4h)** — evaluate `Automattic/charts` and one alternative for:
- Horizontal funnel with paired bars.
- Monthly-cohort line chart.
- Histogram.
- Inline mini-funnel (tiny, ~120px wide).

**Step 2 — Document** the decision in `docs/plans/2026-04-22-chart-library-decision.md` (not committed; delete after plan closes).

**Step 3 — Commit:** `chore(insights): chart library chosen: <name>`.

---

### Task 25: React admin route + feature-flag guard

**Files:**
- Create: `assets/admin/course-insights/index.js`
- Create: `assets/admin/course-insights/App.jsx`
- Modify: `includes/internal/insights/class-sensei-insights.php` (enqueue bundle only when flag on + on insights screens).
- Test: `assets/admin/course-insights/App.test.jsx`

**Step 1 — Jest test:** `App` renders an `Overview` route at `/` and error boundary wraps children.

**Step 2 — Fail.**

**Step 3 — Implement** minimal React Router scaffolding with four routes: Overview, Course, Lesson, Quiz.

**Step 4 — Pass.**

**Step 5 — Commit:** `feat(insights): React admin scaffolding`.

---

### Task 26: API client

**Files:**
- Create: `assets/admin/course-insights/api/client.js`
- Test: `assets/admin/course-insights/api/client.test.js`

**Step 1 — Write tests** for `fetchOverview`, `fetchCourse`, `fetchLesson`, `fetchQuiz`, using `@wordpress/api-fetch` mock.

**Step 2 — Implement.**

**Step 3 — Commit:** `feat(insights): REST client`.

---

### Task 27: Home card component (tile grid + mini-funnel)

**Files:**
- Modify: `assets/home/` — new tile grid variant for course insights.
- Create: `assets/home/course-insights-card/` with `index.js`, `card.jsx`, `mini-funnel.jsx`, tests.

**Step 1 — Jest tests:**
- Tile grid renders when `course_insights` payload present.
- Nothing renders when payload absent (flag off).
- Mini-funnel shows N bars for N lessons, colored by drop-off.

**Step 2 — Fail.**

**Step 3 — Implement.** Connect to the existing Home data hook; render a new section.

**Step 4 — Pass.**

**Step 5 — Commit:** `feat(insights): Home card tile grid`.

---

## Phase 6 — Deep views

### Task 28: Overview view (cross-course list)

**Files:**
- Create: `assets/admin/course-insights/views/Overview.jsx` + tests.

**Step 1 — Jest tests:** loading / empty / error / happy-path. Sort toggle changes the sort param.

**Step 2 — Fail → implement → pass → commit.**

**Commit:** `feat(insights): overview view`.

---

### Task 29: Course Insights view — funnel chart

**Files:**
- Create: `assets/admin/course-insights/views/Course.jsx`
- Create: `assets/admin/course-insights/components/Funnel.jsx` + tests.

**Step 1 — Tests:** `Funnel` renders N bars, each has `pct_reached` and `pct_completed`, click handler receives lesson id.

**Step 2 → 5 — Fail → implement → pass → commit.**

**Commit:** `feat(insights): course funnel chart`.

---

### Task 30: Course Insights view — cohort trend line chart

**Commit:** `feat(insights): cohort trend line chart`.

---

### Task 31: Course Insights view — lesson table (sortable)

**Commit:** `feat(insights): course lesson table`.

---

### Task 32: Course Insights view — empty / low-data states

**Commit:** `feat(insights): course view low-data states`.

---

### Task 33: Lesson Insights view — state donut

**Commit:** `feat(insights): lesson view state breakdown`.

---

### Task 34: Lesson Insights view — delta pill + nav + quiz tile

**Commit:** `feat(insights): lesson view summary + nav`.

---

### Task 35: Quiz Insights view — per-question bar chart (color-coded)

**Files:** `assets/admin/course-insights/components/PerQuestionBars.jsx`

**Step 1 — Tests:** sorted ascending by pct_correct; red/amber/green thresholds at 50% / 70%; non-color differentiator (icon or pattern).

**Step 2 → 5.**

**Commit:** `feat(insights): per-question bars`.

---

### Task 36: Quiz Insights view — score distribution histogram

**Commit:** `feat(insights): score distribution histogram`.

---

### Task 37: Quiz Insights view — assemble + low-data states

**Commit:** `feat(insights): quiz view assembly`.

---

## Phase 7 — Integration under Reports

### Task 38: Reports tab integration

**Files:**
- Modify: `includes/admin/class-sensei-analysis.php` (or wherever the Reports admin page is registered; to verify during task).
- Add the Insights view as a drill-down from the Courses list; keep the existing per-course report reachable for one release.

**Step 1 — Write integration test** that visiting `?page=sensei_reports_course&course=X&view=insights` loads the React mount point.

**Step 2 → 5.**

**Commit:** `feat(insights): Reports tab integration`.

---

## Phase 8 — Performance, a11y, polish

### Task 39: Seed-data script for benchmarking

**Files:**
- Create: `tests/benchmarks/seed-insights-data.php`

Creates small/medium/large datasets (100 / 1k / 10k enrollments) on both storage paths.

**Commit:** `chore(insights): benchmark seed script`.

---

### Task 40: Run benchmarks and tune

**Step 1 — Benchmark every endpoint on large dataset, both paths.**

**Step 2 — If per-question query > 500ms on legacy:** switch quiz endpoint to bundle summary + distribution, lazy-load per-question table via a separate `GET /course-insights/{course}/quizzes/{quiz}/questions` route. (Test-first if adopted.)

**Step 3 — Document results** in a throwaway file; commit only the code changes (if any).

**Commit (if changes):** `perf(insights): <specific change>`.

---

### Task 41: Accessibility pass

**Files:** all view components.

- Every chart has a keyboard-reachable "View as table" toggle (tests first).
- Color + shape/pattern for per-question bars.
- Focus order and ARIA labels on interactive elements.

**Commit:** `a11y(insights): chart accessibility`.

---

### Task 42: Error boundaries

Every view wrapped in an error boundary that shows "Couldn't load insights" + retry. Tests first.

**Commit:** `feat(insights): error boundaries`.

---

### Task 43: Manual "Refresh data" action (debug-only by default)

**Files:** controller + UI.

Expose as a button only when `WP_DEBUG` is true, or hide behind a filter. Tests first.

**Commit:** `feat(insights): manual refresh action`.

---

## Phase 9 — Release

### Task 44: Changelog entry

**Step 1 — Run `npm run changelog`** and write a user-facing entry describing the feature (flag-gated, so frame it as "behind a flag for early testers").

**Step 2 — Commit:** `changelog: course insights (flagged)`.

---

### Task 45: PHPCS + Psalm pass on modified files

**Step 1 — `npm run lint-php` on changed files.**
**Step 2 — `vendor/bin/psalm --no-cache --diff`.**
**Step 3 — Fix any issues; commit fixes.**

**Commit:** `fix(insights): lint and type cleanup`.

---

### Task 46: PR

**Step 1 — Push branch.**
**Step 2 — Open PR to trunk.** Title: `feat: Course Insights (feature-flagged)`. Body:
- Summary of feature, scoped to what ships.
- Explicit "Behind `course_insights` feature flag — default off" callout.
- Test plan (manual steps, not unit tests).
- Link to design doc (note: design doc is local only and not in the PR).
- Screenshots of each view.

---

## Risks surfaced during plan writing

1. **Task 40** is the highest-risk task. If legacy per-question performance fails and we need the lazy-load split, that's 2–3 extra sub-tasks across REST + UI. Budget a buffer day.
2. **Task 38 (Reports integration)** depends on how the current Reports admin page is structured; a look at `includes/admin/class-sensei-analysis.php` may reveal constraints that split this into two tasks. Verify at task start.
3. **Task 24 (chart library spike)** is time-boxed; if neither candidate cleanly handles mini-funnels, we fall back to a custom SVG component — add a task for that and cut a non-critical visualization if the month is tight.
