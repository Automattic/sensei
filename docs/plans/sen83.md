# SEN-83: Reports Overview Aggregates → Table-Aware (HPPS)

Date: 2026-08-14
Ticket: SEN-83
Depends on: SEN-82 (decision: `sensei_check_for_activity` frozen comments-only; callers migrate per surface)

## Goal

Make the Reports Overview **Students** and **Courses** tabs correct when the HPPS
tables repository is active. Today the header denominators for average grade are
table-aware, but the remaining overview aggregates still read comments directly.
Acceptance: both tabs correct in tables mode; `npm run test-php:wp-env:hpps` green.

## Scope

All 7 comments-backed aggregates in SEN-83:

1. Students header counts: completed + started courses
2. Students per-row course counts (started / completed)
3. Students last-activity user-query join (`LEFT JOIN wp_comments` in data provider)
4. Courses completion counts
5. Courses average progress counts
6. Courses service aggregates: avg days-to-completion, lesson completions, students count
7. Per-row average grade in both list tables

Out of scope: any surface outside these 4 files; the frozen `sensei_check_for_activity`
helper itself (stays as-is per SEN-82).

## Approach

Follow the existing HPPS service convention exactly, the same one already used by
`Reports_Listing_Service`, `Grading_Listing_Service`, `Grading_Stats_Service`, and
`Progress_Aggregation_Service`:

- Add each needed query as a method on an **internal service interface**.
- Implement it in **both** the `Tables_Based_*` and `Comments_Based_*` classes.
- Callers route through `Progress_Query_Service_Factory` and **never branch** on
  `is_tables_repository()`. The factory returns the right implementation.

This serves both storage modes from one caller path: comments mode keeps working
today (sync-required release model), and tables mode is correct — without the caller
knowing which is active.

### List-table mechanic: prime once per page

Per-row columns (items 2, 7) must not run one query per row (N+1). The list table
primes a per-user keyed cache **once** per page, then each `get_row_data()` reads
from the cache:

- Override `prepare_items()` (and the CSV `generate_report()` path): after items load,
  call a `prime_row_aggregates( $this->items )` helper.
- The helper collects the page's IDs and makes **one** factory call per aggregate,
  returning a map keyed by user/course ID.
- `get_row_data()` reads `$this->cache[ $id ]` with a zero fallback for un-primed rows.

The grouped query lives behind the service interface, not in the reports layer. No
raw `$wpdb` in the list table or reports service.

### New service methods

On `Progress_Aggregation_Service_Interface` (both impls):

- `count_statuses_by_user( array $args ): array` — returns `[user_id => [status => count]]`
  for the given `type` + `user_id` set. Powers items 2 (students per-row) and, in
  aggregate form, the existing `count_statuses()` still powers header/course counts
  (items 1, 4).

On `Grading_Stats_Service_Interface` (both impls):

- `get_grade_totals_by_user( array $user_ids ): array` — returns
  `[user_id => ['count' => int, 'sum' => float]]`. Rides the existing
  `get_grade_totals` join (progress → quiz_submissions → `_lesson_quiz` postmeta,
  `status IN (graded,passed,failed) AND final_grade IS NOT NULL`) plus
  `GROUP BY q.user_id`. Powers item 7.

Items 1, 3, 4, 5, 6 reuse existing interface methods (`count_statuses`,
`get_lesson_totals`) — those are aggregate calls, wired the same way the header
denominators already are.

### Deprecations

Batch-priming cannot apply per-row filters to a per-user grouped query. Deprecate:

- `sensei_analysis_user_courses_started`
- `sensei_analysis_user_courses_ended`

Apply the `Deprecation` label; name no replacement (the values now come from a batch
query). Follow the repo deprecation convention (`@deprecated $$next-version$$`,
`_deprecated_hook` where applicable).

## Per-item mapping

| # | Item | File | Method / seam |
|---|------|------|---------------|
| 1 | Students header counts | list-table-students `get_columns()` | `create_aggregation_service()->count_statuses()` (aggregate) |
| 2 | Students per-row counts | list-table-students `get_row_data()` | prime `count_statuses_by_user()`, read cache |
| 3 | Students last-activity join | data-provider-students | replace `LEFT JOIN wp_comments` with `sensei_lms_progress` join (or aggregation-service last-activity accessor) |
| 4 | Courses completion counts | list-table-courses | `count_statuses()` (aggregate) |
| 5 | Courses avg progress | list-table-courses / service-courses | `count_statuses()` / existing progress aggregate |
| 6 | Courses service aggregates | service-courses `get_average_days_to_completion` / `get_lessons_completions` / `get_students_count_in_courses` | `get_lesson_totals()` + status counts |
| 7 | Per-row avg grade (both tables) | list-table-students `:223`, list-table-courses `:239` | prime `get_grade_totals_by_user()`, row divides |

## Correctness constraints (must not change displayed numbers)

- **Grade rounding stays row-level.** `get_grade_totals_by_user` returns `{count, sum}`;
  the row applies `quotient_as_absolute_rounded_number( sum, count, 2 )` exactly as
  today. Do not return a pre-divided average (header uses `ceil`, rows do not).
- **Graded-set parity.** The comments impl of `get_grade_totals_by_user` must count the
  same lesson set as today's `sensei_check_for_activity( meta_key=grade )` +
  `get_user_graded_lessons_sum`. The tables impl uses
  `status IN (graded,passed,failed) AND final_grade IS NOT NULL`. A parity test pins
  both to the same fixture.
- **Active courses = started − completed** stays computed in the list table, unchanged.

## Testing (TDD)

Per repo convention (`docs/conventions/unit-tests.md`), write failing tests first.

- New service methods: unit tests on **both** impls (tables + comments) with seeded
  progress rows (no `parent_post_id`) and quiz submissions.
- Grade parity test: same fixture, assert `get_grade_totals_by_user` (comments) matches
  today's per-row grade computation, and (tables) matches it too.
- List-table tests: assert per-page priming issues one query per aggregate (no N+1) and
  that row values match the pre-change output for a seeded page.
- Full run: `make test-php` and `npm run test-php:wp-env:hpps` both green.

## Sequencing

1. Aggregate wiring (items 1, 4, 5, 6) — reuse existing interface methods; lowest risk.
2. Per-user batch methods + list-table prime mechanic (items 2, 7) — new interface
   methods, both impls, prime cache, deprecate filters.
3. Last-activity join swap (item 3) — data-provider SQL.

Each step independently testable and shippable.

## Commit strategy

Logical, disparate commits — not one lump. Each compiles and passes tests on its own;
a new service method (with its tests) lands before the commit that consumes it.

1. Aggregate wiring (items 1, 4, 5, 6) — reports layer reuses existing interface methods.
2. `count_statuses_by_user` + both impls + tests.
3. Students per-row prime mechanic + filter deprecation (item 2) — consumes commit 2.
4. `get_grade_totals_by_user` + both impls + parity test.
5. Per-row grade prime, both tables (item 7) — consumes commit 4.
6. Last-activity join swap (item 3).

Changelog entry on the user-facing commits; `No Changelog` label for internal-only ones.

## Files touched

Reports layer:
- `includes/reports/overview/list-table/class-sensei-reports-overview-list-table-students.php`
- `includes/reports/overview/data-provider/class-sensei-reports-overview-data-provider-students.php`
- `includes/reports/overview/list-table/class-sensei-reports-overview-list-table-courses.php`
- `includes/reports/overview/services/class-sensei-reports-overview-service-students.php`
- `includes/reports/overview/services/class-sensei-reports-overview-service-courses.php`

Internal services:
- `includes/internal/services/class-progress-aggregation-service-interface.php`
- `includes/internal/services/class-tables-based-progress-aggregation-service.php`
- `includes/internal/services/class-comments-based-progress-aggregation-service.php`
- `includes/internal/services/class-grading-stats-service-interface.php`
- `includes/internal/services/class-tables-based-grading-stats-service.php`
- `includes/internal/services/class-comments-based-grading-stats-service.php`

## Rejected alternatives

- **Raw `$wpdb` grouped query in the reports service (prototype).** Works only in tables
  mode, bypasses the factory/interface, no comments impl — breaks the sync-required
  release model and diverges from every other HPPS conversion.
- **Per-row service call (no priming).** Simplest, but N queries per page — regresses
  perf vs today.
- **List table assembles the keyed map from an aggregate method (option A/C).** Still
  needs a new grouped method anyway; putting the grouped query behind the interface
  (option D) is the same new code in the right layer.
