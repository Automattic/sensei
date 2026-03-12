<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Progress_Clauses_Service;

/**
 * Class Tables_Based_Progress_Clauses_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Clauses_Service
 */
class Tables_Based_Progress_Clauses_Service_Test extends \WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	/**
	 * Insert a progress row directly into the HPPS progress table.
	 *
	 * @param int         $post_id        The post ID.
	 * @param int         $user_id        The user ID.
	 * @param string      $type           The progress type.
	 * @param string      $status         The progress status.
	 * @param int|null    $parent_post_id The parent post ID.
	 * @param string|null $started_at     The started at date.
	 * @param string|null $completed_at   The completed at date.
	 * @param string|null $updated_at     The updated at date.
	 */
	private function insert_progress( int $post_id, int $user_id, string $type, string $status, ?int $parent_post_id = null, ?string $started_at = null, ?string $completed_at = null, ?string $updated_at = null ): void {
		$wpdb  = $GLOBALS['wpdb'];
		$table = $wpdb->prefix . 'sensei_lms_progress';
		$now   = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper inserting directly into custom table.
		$wpdb->insert(
			$table,
			[
				'post_id'        => $post_id,
				'user_id'        => $user_id,
				'parent_post_id' => $parent_post_id,
				'type'           => $type,
				'status'         => $status,
				'started_at'     => $started_at ?? $now,
				'completed_at'   => $completed_at,
				'created_at'     => $now,
				'updated_at'     => $updated_at ?? $now,
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Get empty clauses array for testing.
	 *
	 * @return array
	 */
	private function get_empty_clauses(): array {
		return [
			'fields'  => '',
			'join'    => '',
			'where'   => '',
			'groupby' => '',
			'orderby' => '',
			'limits'  => '',
		];
	}

	public function testAddLastActivityToCourseClauses_WithCompletedLesson_AddsLastActivityDate(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id, null, null, '2026-01-15 10:00:00' );

		$service = new Tables_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->add_last_activity_to_courses_clauses( $clauses );

		/* Assert. */
		$this->assertStringContainsString( 'last_activity_date', $clauses['fields'] );
		$this->assertStringContainsString( 'LEFT JOIN', $clauses['join'] );
	}

	public function testAddDaysToCompletionToCourseClauses_WithCompletedCourse_AddsDaysAndCount(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		$this->insert_progress( $course_id, $user_id, 'course', 'complete', null, '2026-01-01 00:00:00', '2026-01-03 00:00:00' );

		$service = new Tables_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->add_days_to_completion_to_courses_clauses( $clauses );

		/* Assert. */
		$this->assertStringContainsString( 'days_to_completion', $clauses['fields'] );
		$this->assertStringContainsString( 'count_of_completions', $clauses['fields'] );
		$this->assertStringContainsString( 'LEFT JOIN', $clauses['join'] );
		$this->assertNotEmpty( $clauses['groupby'] );
	}

	public function testFilterCoursesByLastActivity_WithFromDate_AddsWhereClause(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Tables_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '2026-01-01', '' );

		/* Assert. */
		$this->assertStringContainsString( 'last_activity_date >=', $clauses['where'] );
	}

	public function testFilterCoursesByLastActivity_WithToDate_AddsWhereClause(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Tables_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '', '2026-12-31' );

		/* Assert. */
		$this->assertStringContainsString( 'last_activity_date <=', $clauses['where'] );
	}

	public function testFilterCoursesByLastActivity_WithNoDate_LeavesWhereUnchanged(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Tables_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '', '' );

		/* Assert. */
		$this->assertSame( '', $clauses['where'] );
	}
}
