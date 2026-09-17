<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service;

/**
 * Class Tables_Based_Progress_Aggregation_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service
 */
class Tables_Based_Course_Completion_Days_Test extends \WP_UnitTestCase {

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

	public function testGetCoursesAverageDaysToCompletion_ManyStudentsAndMissingCompletionDatesGiven_PreservesCourseAverages(): void {
		/* Arrange. */
		global $wpdb;

		$course_id       = $this->sensei_factory->course->create();
		$other_course_id = $this->sensei_factory->course->create();
		$rows            = array();
		// Repeated timestamp pairs span multiple batches; missing completions still count as starts.
		for ( $student = 1; $student <= 6001; $student++ ) {
			$completed_at = $student <= 5000 ? '2024-01-01 12:00:00' : ( 6001 === $student ? '2024-01-04 12:00:00' : null );
			$rows[]       = $wpdb->prepare(
				"(%d, %d, 'course', 'complete', '2024-01-01 10:00:00', NULLIF(%s, ''), '2024-01-01 10:00:00', '2024-01-01 10:00:00')",
				$course_id,
				$student,
				$completed_at
			);
		}
		$table = $wpdb->prefix . 'sensei_lms_progress';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted table name. Bulk progress fixture; each row is prepared above.
		$wpdb->query( "INSERT INTO $table (post_id, user_id, type, status, started_at, completed_at, created_at, updated_at) VALUES " . implode( ',', $rows ) );
		$this->insert_progress_with_dates( $other_course_id, 1, 'course', 'complete', '2024-01-01 10:00:00', '2024-01-06 12:00:00' );
		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$actual = $service->get_courses_average_days_to_completion( array( $course_id, $other_course_id ) );

		/* Assert. */
		// Course 1: ceil((5000 + 4) / 6001) = 1. Course 2: 6. Average: 3.5.
		self::assertSame( 3.5, $actual );
	}

	public function testGetCoursesAverageDaysToCompletion_MigratedMissingStartDateGiven_ExcludesItFromDenominator(): void {
		/* Arrange. */
		global $wpdb;

		$course_id  = $this->sensei_factory->course->create();
		$user_id    = $this->sensei_factory->user->create();
		$other_user = $this->sensei_factory->user->create();
		$this->insert_progress_with_dates( $course_id, $user_id, 'course', 'complete', '2022-01-01 00:00:00', '2022-01-04 00:00:00' );
		// The existing migration writes 0 when start metadata is absent.
		$this->insert_progress_with_dates( $course_id, $other_user, 'course', 'complete', '0000-00-00 00:00:00', '2022-01-04 00:00:00' );
		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_courses_average_days_to_completion( array( $course_id ) );

		/* Assert. */
		$this->assertSame( 4.0, $result );
	}

	/**
	 * Insert a progress row directly into the HPPS progress table.
	 *
	 * @param int         $post_id        The post ID.
	 * @param int         $user_id        The user ID.
	 * @param string      $type           The progress type ('course' or 'lesson').
	 * @param string      $status         The progress status.
	 */
	private function insert_progress_with_dates( int $post_id, int $user_id, string $type, string $status, string $started_at, string $completed_at ): void {
		$wpdb  = $GLOBALS['wpdb'];
		$table = $wpdb->prefix . 'sensei_lms_progress';
		$now   = current_time( 'mysql' );
		$data  = array(
			'post_id'      => $post_id,
			'user_id'      => $user_id,
			'type'         => $type,
			'status'       => $status,
			'started_at'   => $started_at,
			'completed_at' => $completed_at,
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		$format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper inserting directly into custom table.
		$wpdb->insert( $table, $data, $format );
	}
}
