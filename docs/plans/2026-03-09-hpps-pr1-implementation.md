# PR 1: HPPS Grading Bug Fix + Aggregation Service Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers-extended-cc:executing-plans to implement this plan task-by-task.

**Goal:** Fix the fatal SQL error when HPPS tables are enabled by introducing the Progress Aggregation Service and migrating `count_statuses`.

**Architecture:** Create `Progress_Aggregation_Service_Interface` with comments-based and tables-based implementations. Rename existing clause service files. Expand factory. Wire `Sensei_Grading::count_statuses` to delegate to the aggregation service. Fix `Sensei_Temporary_User::filter_count_statuses` to use structured parameters instead of raw SQL.

**Tech Stack:** PHP 7.4+, WordPress, PHPUnit (via `./vendor/bin/phpunit`)

**Test command:** `./vendor/bin/phpunit --filter <TestClass>`

**Lint command:** `npm run lint-php`

---

## Task 1: Rename existing interface to Progress_Clauses_Service_Interface

**Files:**
- Rename: `includes/internal/services/class-progress-query-service-interface.php` -> `class-progress-clauses-service-interface.php`
- Rename: `includes/internal/services/class-comments-based-progress-query-service.php` -> `class-comments-based-progress-clauses-service.php`
- Rename: `includes/internal/services/class-tables-based-progress-query-service.php` -> `class-tables-based-progress-clauses-service.php`
- Modify: `includes/internal/services/class-progress-query-service-factory.php`
- Modify: `includes/reports/overview/data-provider/class-sensei-reports-overview-data-provider-courses.php`

**Step 1: Rename files via git mv**

```bash
cd includes/internal/services
git mv class-progress-query-service-interface.php class-progress-clauses-service-interface.php
git mv class-comments-based-progress-query-service.php class-comments-based-progress-clauses-service.php
git mv class-tables-based-progress-query-service.php class-tables-based-progress-clauses-service.php
```

**Step 2: Update class/interface names in the renamed files**

In `class-progress-clauses-service-interface.php`:
- Rename `Progress_Query_Service_Interface` to `Progress_Clauses_Service_Interface`
- Update docblocks accordingly

In `class-comments-based-progress-clauses-service.php`:
- Rename class to `Comments_Based_Progress_Clauses_Service`
- Update `implements Progress_Query_Service_Interface` to `implements Progress_Clauses_Service_Interface`

In `class-tables-based-progress-clauses-service.php`:
- Rename class to `Tables_Based_Progress_Clauses_Service`
- Update `implements Progress_Query_Service_Interface` to `implements Progress_Clauses_Service_Interface`

**Step 3: Update factory**

In `includes/internal/services/class-progress-query-service-factory.php`:
- Update return type of `create()` from `Progress_Query_Service_Interface` to `Progress_Clauses_Service_Interface`
- Update `new Tables_Based_Progress_Query_Service` to `new Tables_Based_Progress_Clauses_Service`
- Update `new Comments_Based_Progress_Query_Service` to `new Comments_Based_Progress_Clauses_Service`
- Rename `create()` method to `create_clauses_service()`

**Step 4: Update courses data provider**

In `includes/reports/overview/data-provider/class-sensei-reports-overview-data-provider-courses.php`:
- Update `use` statements: `Progress_Query_Service_Interface` -> `Progress_Clauses_Service_Interface`, `Progress_Query_Service_Factory` stays the same
- Update type hints and constructor to use `Progress_Clauses_Service_Interface`
- Update factory call from `create()` to `create_clauses_service()`

**Step 5: Run composer dump-autoload**

```bash
composer dump-autoload
```

**Step 6: Run existing tests to verify nothing broke**

```bash
./vendor/bin/phpunit --filter Sensei_Reports_Overview_Data_Provider_Courses
```

Expected: all passing (or no tests exist for this class, which is fine).

**Step 7: Commit**

```bash
git add -A includes/internal/services/ includes/reports/overview/data-provider/class-sensei-reports-overview-data-provider-courses.php
git commit -m "Rename Progress_Query_Service to Progress_Clauses_Service"
```

---

## Task 2: Create Progress_Aggregation_Service_Interface with count_statuses

**Files:**
- Create: `includes/internal/services/class-progress-aggregation-service-interface.php`

**Step 1: Write the interface**

```php
<?php
namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Progress_Aggregation_Service_Interface {

    /**
     * Count progress records grouped by status.
     *
     * @param array $args {
     *     Arguments for the query.
     *
     *     @type string   $type                         'course' or 'lesson'.
     *     @type array    $post__in                     Restrict to specific post IDs.
     *     @type int      $post_id                      Restrict to a single post ID.
     *     @type int|array $user_id                     Restrict to specific user IDs.
     *     @type string[] $exclude_user_login_prefixes  User login prefixes to exclude.
     *     @type string[] $include_statuses_override    Statuses that bypass user exclusion.
     * }
     * @return array Associative array of status => count.
     */
    public function count_statuses( array $args ): array;
}
```

**Step 2: Commit**

```bash
git add includes/internal/services/class-progress-aggregation-service-interface.php
git commit -m "Add Progress_Aggregation_Service_Interface with count_statuses"
```

---

## Task 3: Implement Comments_Based_Progress_Aggregation_Service

**Files:**
- Create: `includes/internal/services/class-comments-based-progress-aggregation-service.php`
- Test: `tests/unit-tests/internal/services/test-class-comments-based-progress-aggregation-service.php`

**Step 1: Write the failing test**

Create `tests/unit-tests/internal/services/test-class-comments-based-progress-aggregation-service.php`:

```php
<?php
namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service;

class Comments_Based_Progress_Aggregation_Service_Test extends \WP_UnitTestCase {
    protected $factory;

    public function setUp(): void {
        parent::setUp();
        $this->factory = new \Sensei_Factory();
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->factory->tearDown();
    }

    public function testCountStatuses_LessonType_ReturnsStatusCounts(): void {
        // Arrange.
        $user_id   = $this->factory->user->create();
        $course_id = $this->factory->course->create();
        $lesson_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );

        // Create a lesson progress comment.
        \Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

        $service = new Comments_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );

        // Act.
        $counts = $service->count_statuses( [ 'type' => 'lesson' ] );

        // Assert.
        $this->assertIsArray( $counts );
        $this->assertArrayHasKey( 'in-progress', $counts );
        $this->assertEquals( 1, $counts['in-progress'] );
    }

    public function testCountStatuses_WithPostIdFilter_RestrictsToPost(): void {
        // Arrange.
        $user_id    = $this->factory->user->create();
        $course_id  = $this->factory->course->create();
        $lesson_id1 = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
        $lesson_id2 = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );

        \Sensei_Utils::update_lesson_status( $user_id, $lesson_id1, 'in-progress' );
        \Sensei_Utils::update_lesson_status( $user_id, $lesson_id2, 'complete' );

        $service = new Comments_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );

        // Act.
        $counts = $service->count_statuses( [ 'type' => 'lesson', 'post_id' => $lesson_id1 ] );

        // Assert.
        $this->assertEquals( 1, $counts['in-progress'] );
        $this->assertEquals( 0, $counts['complete'] );
    }

    public function testCountStatuses_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
        // Arrange.
        $regular_user = $this->factory->user->create( [ 'user_login' => 'regularuser' ] );
        $guest_user   = $this->factory->user->create( [ 'user_login' => 'sensei_guest_12345' ] );
        $course_id    = $this->factory->course->create();
        $lesson_id    = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );

        \Sensei_Utils::update_lesson_status( $regular_user, $lesson_id, 'in-progress' );
        \Sensei_Utils::update_lesson_status( $guest_user, $lesson_id, 'in-progress' );

        $service = new Comments_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );

        // Act.
        $counts = $service->count_statuses( [
            'type'                         => 'lesson',
            'exclude_user_login_prefixes'  => [ 'sensei_guest_' ],
            'include_statuses_override'    => [ 'ungraded' ],
        ] );

        // Assert — guest user's in-progress should be excluded.
        $this->assertEquals( 1, $counts['in-progress'] );
    }
}
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit --filter Comments_Based_Progress_Aggregation_Service_Test
```

Expected: FAIL — class not found.

**Step 3: Write the implementation**

Create `includes/internal/services/class-comments-based-progress-aggregation-service.php`:

```php
<?php
namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Comments_Based_Progress_Aggregation_Service implements Progress_Aggregation_Service_Interface {

    private \wpdb $wpdb;

    public function __construct( \wpdb $wpdb ) {
        $this->wpdb = $wpdb;
    }

    public function count_statuses( array $args ): array {
        $wpdb = $this->wpdb;

        if ( 'course' === $args['type'] ) {
            $type = 'sensei_course_status';
        } else {
            $type = 'sensei_lesson_status';
        }

        $query = $wpdb->prepare(
            "SELECT comment_approved, COUNT( * ) AS total FROM {$wpdb->comments} WHERE comment_type = %s ",
            $type
        );

        // Restrict to specific posts.
        if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
            $placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
            $query .= $wpdb->prepare( " AND comment_post_ID IN ( $placeholders )", $args['post__in'] );
        } elseif ( ! empty( $args['post_id'] ) ) {
            $query .= $wpdb->prepare( ' AND comment_post_ID = %d', $args['post_id'] );
        }

        // Restrict to specific users.
        if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
            $placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
            $query .= $wpdb->prepare( " AND user_id IN ( $placeholders )", $args['user_id'] );
        } elseif ( ! empty( $args['user_id'] ) ) {
            $query .= $wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
        }

        // Exclude users by login prefix (e.g., guest/preview users).
        if ( ! empty( $args['exclude_user_login_prefixes'] ) ) {
            $conditions = [];
            foreach ( $args['exclude_user_login_prefixes'] as $prefix ) {
                $conditions[] = $wpdb->prepare( "comment_author NOT LIKE %s", $wpdb->esc_like( $prefix ) . '%' );
            }
            $exclusion = implode( ' AND ', $conditions );

            if ( ! empty( $args['include_statuses_override'] ) ) {
                $status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
                $override = $wpdb->prepare( "comment_approved IN ( $status_placeholders )", $args['include_statuses_override'] );
                $query .= " AND ( ( $exclusion ) OR $override )";
            } else {
                $query .= " AND $exclusion";
            }
        }

        // Legacy: support raw query string for backward compatibility.
        if ( isset( $args['query'] ) ) {
            $query .= $args['query'];
        }

        $query .= ' GROUP BY comment_approved';

        $results = (array) $wpdb->get_results( $query, ARRAY_A );
        $counts  = [];

        foreach ( $results as $row ) {
            $counts[ $row['comment_approved'] ] = (int) $row['total'];
        }

        return $counts;
    }
}
```

**Step 4: Run composer dump-autoload and test**

```bash
composer dump-autoload
./vendor/bin/phpunit --filter Comments_Based_Progress_Aggregation_Service_Test
```

Expected: PASS

**Step 5: Commit**

```bash
git add includes/internal/services/class-comments-based-progress-aggregation-service.php tests/unit-tests/internal/services/test-class-comments-based-progress-aggregation-service.php
git commit -m "Add Comments_Based_Progress_Aggregation_Service with count_statuses"
```

---

## Task 4: Implement Tables_Based_Progress_Aggregation_Service

**Files:**
- Create: `includes/internal/services/class-tables-based-progress-aggregation-service.php`
- Test: `tests/unit-tests/internal/services/test-class-tables-based-progress-aggregation-service.php`

**Step 1: Write the failing test**

Create `tests/unit-tests/internal/services/test-class-tables-based-progress-aggregation-service.php`:

```php
<?php
namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service;

class Tables_Based_Progress_Aggregation_Service_Test extends \WP_UnitTestCase {
    protected $factory;

    public function setUp(): void {
        parent::setUp();
        $this->factory = new \Sensei_Factory();
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->factory->tearDown();
    }

    private function insert_progress( int $post_id, int $user_id, string $type, string $status, ?int $parent_post_id = null ): void {
        $wpdb  = $GLOBALS['wpdb'];
        $table = $wpdb->prefix . 'sensei_lms_progress';
        $now   = current_time( 'mysql' );
        $wpdb->insert(
            $table,
            [
                'post_id'        => $post_id,
                'user_id'        => $user_id,
                'parent_post_id' => $parent_post_id,
                'type'           => $type,
                'status'         => $status,
                'started_at'     => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    public function testCountStatuses_LessonType_ReturnsStatusCounts(): void {
        // Arrange.
        $user_id   = $this->factory->user->create();
        $course_id = $this->factory->course->create();
        $lesson_id = $this->factory->lesson->create();

        $this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

        $service = new Tables_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );

        // Act.
        $counts = $service->count_statuses( [ 'type' => 'lesson' ] );

        // Assert.
        $this->assertIsArray( $counts );
        $this->assertArrayHasKey( 'in-progress', $counts );
        $this->assertEquals( 1, $counts['in-progress'] );
    }

    public function testCountStatuses_WithPostIdFilter_RestrictsToPost(): void {
        // Arrange.
        $user_id    = $this->factory->user->create();
        $course_id  = $this->factory->course->create();
        $lesson_id1 = $this->factory->lesson->create();
        $lesson_id2 = $this->factory->lesson->create();

        $this->insert_progress( $lesson_id1, $user_id, 'lesson', 'in-progress', $course_id );
        $this->insert_progress( $lesson_id2, $user_id, 'lesson', 'complete', $course_id );

        $service = new Tables_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );

        // Act.
        $counts = $service->count_statuses( [ 'type' => 'lesson', 'post_id' => $lesson_id1 ] );

        // Assert.
        $this->assertEquals( 1, $counts['in-progress'] );
        $this->assertEmpty( $counts['complete'] ?? 0 );
    }

    public function testCountStatuses_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
        // Arrange.
        $regular_user = $this->factory->user->create( [ 'user_login' => 'regularuser' ] );
        $guest_user   = $this->factory->user->create( [ 'user_login' => 'sensei_guest_12345' ] );
        $course_id    = $this->factory->course->create();
        $lesson_id    = $this->factory->lesson->create();

        $this->insert_progress( $lesson_id, $regular_user, 'lesson', 'in-progress', $course_id );
        $this->insert_progress( $lesson_id, $guest_user, 'lesson', 'in-progress', $course_id );

        $service = new Tables_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );

        // Act.
        $counts = $service->count_statuses( [
            'type'                         => 'lesson',
            'exclude_user_login_prefixes'  => [ 'sensei_guest_' ],
            'include_statuses_override'    => [ 'ungraded' ],
        ] );

        // Assert — guest user's in-progress should be excluded.
        $this->assertEquals( 1, $counts['in-progress'] );
    }
}
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit --filter Tables_Based_Progress_Aggregation_Service_Test
```

Expected: FAIL — class not found.

**Step 3: Write the implementation**

Create `includes/internal/services/class-tables-based-progress-aggregation-service.php`:

```php
<?php
namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tables_Based_Progress_Aggregation_Service implements Progress_Aggregation_Service_Interface {

    private \wpdb $wpdb;

    public function __construct( \wpdb $wpdb ) {
        $this->wpdb = $wpdb;
    }

    private function get_progress_table_name(): string {
        return $this->wpdb->prefix . 'sensei_lms_progress';
    }

    public function count_statuses( array $args ): array {
        $wpdb  = $this->wpdb;
        $table = $this->get_progress_table_name();
        $type  = $args['type']; // 'course' or 'lesson' — matches HPPS type column directly.

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is constructed from wpdb prefix.
        $query = $wpdb->prepare( "SELECT p.status, COUNT( * ) AS total FROM {$table} p WHERE p.type = %s ", $type );

        // Restrict to specific posts.
        if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
            $placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $query .= $wpdb->prepare( " AND p.post_id IN ( $placeholders )", $args['post__in'] );
        } elseif ( ! empty( $args['post_id'] ) ) {
            $query .= $wpdb->prepare( ' AND p.post_id = %d', $args['post_id'] );
        }

        // Restrict to specific users.
        if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
            $placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $query .= $wpdb->prepare( " AND p.user_id IN ( $placeholders )", $args['user_id'] );
        } elseif ( ! empty( $args['user_id'] ) ) {
            $query .= $wpdb->prepare( ' AND p.user_id = %d', $args['user_id'] );
        }

        // Exclude users by login prefix (e.g., guest/preview users).
        // Requires a JOIN to wp_users since the progress table stores user_id, not login.
        if ( ! empty( $args['exclude_user_login_prefixes'] ) ) {
            $query .= " INNER JOIN {$wpdb->users} u ON p.user_id = u.ID";
            $conditions = [];
            foreach ( $args['exclude_user_login_prefixes'] as $prefix ) {
                $conditions[] = $wpdb->prepare( "u.user_login NOT LIKE %s", $wpdb->esc_like( $prefix ) . '%' );
            }
            $exclusion = implode( ' AND ', $conditions );

            if ( ! empty( $args['include_statuses_override'] ) ) {
                $status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $override = $wpdb->prepare( "p.status IN ( $status_placeholders )", $args['include_statuses_override'] );
                $query .= " AND ( ( $exclusion ) OR $override )";
            } else {
                $query .= " AND $exclusion";
            }
        }

        $query .= ' GROUP BY p.status';

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL prepared in parts above.
        $results = (array) $wpdb->get_results( $query, ARRAY_A );
        $counts  = [];

        foreach ( $results as $row ) {
            $counts[ $row['status'] ] = (int) $row['total'];
        }

        return $counts;
    }
}
```

Note: The `INNER JOIN` for user exclusion is appended after the `WHERE` clause. This works because MySQL evaluates the full query — the JOIN just needs to appear before `GROUP BY`. However, for cleaner SQL, the implementation should build the JOIN separately. The code above is simplified for clarity; during implementation, verify the SQL is correct by running it against test data.

**Step 4: Run composer dump-autoload and test**

```bash
composer dump-autoload
./vendor/bin/phpunit --filter Tables_Based_Progress_Aggregation_Service_Test
```

Expected: PASS

**Step 5: Commit**

```bash
git add includes/internal/services/class-tables-based-progress-aggregation-service.php tests/unit-tests/internal/services/test-class-tables-based-progress-aggregation-service.php
git commit -m "Add Tables_Based_Progress_Aggregation_Service with count_statuses"
```

---

## Task 5: Expand factory with create_aggregation_service()

**Files:**
- Modify: `includes/internal/services/class-progress-query-service-factory.php`

**Step 1: Add the new factory method**

Add to `Progress_Query_Service_Factory`:

```php
/**
 * Create a Progress_Aggregation_Service_Interface instance.
 *
 * @since $$next-version$$
 *
 * @return Progress_Aggregation_Service_Interface The progress aggregation service.
 */
public function create_aggregation_service(): Progress_Aggregation_Service_Interface {
    if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
        return new Tables_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );
    }

    return new Comments_Based_Progress_Aggregation_Service( $GLOBALS['wpdb'] );
}
```

**Step 2: Commit**

```bash
git add includes/internal/services/class-progress-query-service-factory.php
git commit -m "Add create_aggregation_service() to factory"
```

---

## Task 6: Wire Sensei_Grading::count_statuses to aggregation service

**Files:**
- Modify: `includes/class-sensei-grading.php`

**Step 1: Update count_statuses to delegate to the service**

Replace the body of `count_statuses()` (lines 545-640) to:
1. Apply the `sensei_count_statuses_args` filter (unchanged).
2. Delegate the query to `Progress_Aggregation_Service_Interface::count_statuses()`.
3. Ensure all default status keys exist in the result.
4. Apply the `sensei_count_statuses` output filter (unchanged).

The method should create the aggregation service via the factory (lazy-instantiated) and pass `$args` through. The service returns `['status' => count]` pairs. The grading method ensures all expected statuses are present with 0 defaults, same as before.

Key changes:
- Remove all raw SQL from `count_statuses()`.
- Keep the cache logic (`wp_cache_get`/`wp_cache_set`) in `count_statuses()` — the service does not cache.
- The `get_stati()` method stays in `Sensei_Grading` — it is used for default keys.

```php
public function count_statuses( $args = array() ) {
    $args = apply_filters( 'sensei_count_statuses_args', $args );

    $type = $args['type'] ?? 'lesson';

    $cache_key = 'sensei-statuses-' . md5( wp_json_encode( $args ) );
    $counts    = wp_cache_get( $cache_key, 'counts' );

    if ( false === $counts ) {
        $factory = new \Sensei\Internal\Services\Progress_Query_Service_Factory();
        $service = $factory->create_aggregation_service();
        $counts  = $service->count_statuses( $args );
        wp_cache_set( $cache_key, $counts, 'counts' );
    }

    // Ensure all expected statuses exist with 0 defaults.
    $defaults = array_fill_keys( $this->get_stati( $type ), 0 );
    $counts   = array_merge( $defaults, $counts );

    // Also ensure these specific keys always exist.
    foreach ( [ 'graded', 'ungraded', 'passed', 'failed', 'in-progress', 'complete' ] as $status ) {
        if ( ! isset( $counts[ $status ] ) ) {
            $counts[ $status ] = 0;
        }
    }

    if ( 'course' === $type ) {
        $comment_type = 'sensei_course_status';
    } else {
        $comment_type = 'sensei_lesson_status';
    }

    return apply_filters( 'sensei_count_statuses', $counts, $comment_type );
}
```

**Step 2: Run lint**

```bash
npm run lint-php
```

Fix any violations.

**Step 3: Commit**

```bash
git add includes/class-sensei-grading.php
git commit -m "Wire Sensei_Grading::count_statuses to aggregation service"
```

---

## Task 7: Fix Sensei_Temporary_User::filter_count_statuses

**Files:**
- Modify: `includes/class-sensei-temporary-user.php`
- Test: `tests/unit-tests/test-class-sensei-temporary-user.php`

**Step 1: Check existing tests**

Read `tests/unit-tests/test-class-sensei-temporary-user.php` to understand the test structure and check if `filter_count_statuses` has tests.

**Step 2: Write a failing test**

Add to the existing test class (or create a new test method):

```php
public function testFilterCountStatuses_SetsStructuredExclusionParams(): void {
    $args   = [ 'type' => 'lesson' ];
    $result = \Sensei_Temporary_User::filter_count_statuses( $args );

    $this->assertArrayHasKey( 'exclude_user_login_prefixes', $result );
    $this->assertContains( \Sensei_Guest_User::LOGIN_PREFIX, $result['exclude_user_login_prefixes'] );
    $this->assertContains( \Sensei_Preview_User::LOGIN_PREFIX, $result['exclude_user_login_prefixes'] );
    $this->assertArrayHasKey( 'include_statuses_override', $result );
    $this->assertContains( 'ungraded', $result['include_statuses_override'] );
    // Should NOT set the legacy 'query' key.
    $this->assertArrayNotHasKey( 'query', $result );
}
```

**Step 3: Run test to verify it fails**

```bash
./vendor/bin/phpunit --filter testFilterCountStatuses_SetsStructuredExclusionParams
```

Expected: FAIL — `query` key is still set, structured keys missing.

**Step 4: Update filter_count_statuses**

In `includes/class-sensei-temporary-user.php`, replace `filter_count_statuses` (around line 189):

```php
public static function filter_count_statuses( array $args ) {
    $args['exclude_user_login_prefixes'] = array_merge(
        $args['exclude_user_login_prefixes'] ?? [],
        [
            \Sensei_Guest_User::LOGIN_PREFIX,
            \Sensei_Preview_User::LOGIN_PREFIX,
        ]
    );

    $args['include_statuses_override'] = array_merge(
        $args['include_statuses_override'] ?? [],
        [ 'ungraded' ]
    );

    return $args;
}
```

**Step 5: Run tests**

```bash
./vendor/bin/phpunit --filter testFilterCountStatuses_SetsStructuredExclusionParams
```

Expected: PASS

**Step 6: Commit**

```bash
git add includes/class-sensei-temporary-user.php tests/unit-tests/test-class-sensei-temporary-user.php
git commit -m "Migrate filter_count_statuses to structured params"
```

---

## Task 8: Update Sensei_Teacher::limit_grading_totals

**Files:**
- Modify: `includes/class-sensei-teacher.php` (check if it hooks `sensei_count_statuses_args` with raw SQL)

**Step 1: Check the teacher filter**

Read `includes/class-sensei-teacher.php` around line 937 where `limit_grading_totals` is defined. If it appends raw SQL via the `query` key, update it to use structured params or post filters (`post__in`). If it only uses `post__in`/`user_id`, no change needed.

**Step 2: Update if needed and commit**

```bash
git add includes/class-sensei-teacher.php
git commit -m "Update teacher grading filter for aggregation service compatibility"
```

---

## Task 9: Run full test suite and lint

**Step 1: Run lint**

```bash
npm run lint-php
```

Fix any violations.

**Step 2: Run full PHP test suite**

```bash
./vendor/bin/phpunit
```

Fix any failures.

**Step 3: Add changelog entry**

```bash
npm run changelog
```

Select: "Fixed" — "Fix fatal SQL error on Reports > Courses when HPPS tables are enabled."

**Step 4: Commit any fixes**

```bash
git add -A
git commit -m "Fix lint and test issues"
```

**Step 5: Push and update PR**

```bash
git push
```
