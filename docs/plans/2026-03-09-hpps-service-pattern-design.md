# HPPS Service Pattern Design

## Problem

When HPPS tables are enabled, reports and grading queries still use `wp_comments` column names (e.g., `comment_author`, `comment_approved`) against `wp_sensei_lms_progress`, causing fatal SQL errors.

## Approach

Split progress query abstraction into two focused interfaces by operation type:

- **Progress_Clauses_Service_Interface** — modifies WP_Query/WP_User_Query clauses
- **Progress_Aggregation_Service_Interface** — runs standalone aggregate queries returning results

Each has comments-based and tables-based implementations. A single factory creates both.

## Interfaces

### Progress_Clauses_Service_Interface

Renamed from `Progress_Query_Service_Interface`.

```php
interface Progress_Clauses_Service_Interface {
    // Courses (existing)
    public function add_last_activity_to_courses_clauses( array $clauses ): array;
    public function add_days_to_completion_to_courses_clauses( array $clauses ): array;
    public function filter_courses_by_last_activity( array $clauses, string $from, string $to ): array;

    // Lessons
    public function add_last_activity_to_lessons_clauses( array $clauses ): array;
    public function add_days_to_completion_to_lessons_clauses( array $clauses ): array;

    // Students
    public function add_last_activity_to_students_query( WP_User_Query $query ): void;
}
```

### Progress_Aggregation_Service_Interface

```php
interface Progress_Aggregation_Service_Interface {
    public function count_statuses( array $args ): array;
    public function get_courses_average_grade( array $course_ids ): float;
    public function get_average_days_to_completion( array $course_ids ): float;
    public function get_lessons_completions(): array;
    public function get_students_count_in_courses( array $course_ids ): array;
    public function get_graded_lessons_average_grade( array $user_ids ): float;
    public function get_lesson_report_totals( array $lesson_ids ): object;
}
```

## filter_count_statuses Migration

Replace raw SQL `query` key in `sensei_count_statuses_args` with structured parameters:

```php
$args['exclude_user_login_prefixes'] = [
    Sensei_Guest_User::LOGIN_PREFIX,
    Sensei_Preview_User::LOGIN_PREFIX,
];
$args['include_statuses_override'] = ['ungraded'];
```

Each implementation maps these to storage-specific SQL. The `query` key is deprecated.

## File Layout

All in `includes/internal/services/`:

| File | Status |
|------|--------|
| `class-progress-clauses-service-interface.php` | Rename |
| `class-comments-based-progress-clauses-service.php` | Rename |
| `class-tables-based-progress-clauses-service.php` | Rename |
| `class-progress-aggregation-service-interface.php` | New |
| `class-comments-based-progress-aggregation-service.php` | New |
| `class-tables-based-progress-aggregation-service.php` | New |
| `class-progress-query-service-factory.php` | Expand |

## PR Plan

### PR 1: `add/hpps-full-migration` -> `trunk`

Fix grading bug + foundation:
- Rename existing interface/implementations to `Progress_Clauses_Service_*`
- Create `Progress_Aggregation_Service_Interface` with `count_statuses()`
- Both aggregation implementations
- Expand factory with `create_aggregation_service()`
- Fix `Sensei_Temporary_User::filter_count_statuses` (structured params)
- Wire `Sensei_Grading::count_statuses` to aggregation service

### PR 2: `add/hpps-lessons-clauses` -> `add/hpps-full-migration`

- Add lessons clause methods to interface + both implementations
- Update `Sensei_Reports_Overview_Data_Provider_Lessons` to use service

### PR 3: `add/hpps-students-clauses` -> `add/hpps-full-migration`

- Add students clause method to interface + both implementations
- Update `Sensei_Reports_Overview_Data_Provider_Students` to use service

### PR 4: `add/hpps-report-aggregation` -> `add/hpps-full-migration`

- Add remaining aggregation methods to interface + both implementations
- Migrate `Sensei_Reports_Overview_Service_Courses`
- Migrate `Sensei_Reports_Overview_Service_Students`
- Migrate `Sensei_Reports_Overview_List_Table_Lessons`

PRs 2, 3, and 4 are independent and can be developed in parallel.

## Column Mapping Reference

| wp_comments | wp_sensei_lms_progress |
|-------------|----------------------|
| `comment_approved` | `status` |
| `comment_type` (`sensei_course_status`/`sensei_lesson_status`) | `type` (`course`/`lesson`) |
| `comment_date_gmt` | `updated_at` |
| `comment_post_ID` | `post_id` |
| `comment_author` | N/A (join `wp_users` via `user_id`) |
| `commentmeta.meta_key='start'` | `started_at` |
| `commentmeta.meta_key='grade'` | `wp_sensei_lms_quiz_grades` table |
