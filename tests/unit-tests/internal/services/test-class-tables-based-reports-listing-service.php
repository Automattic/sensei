<?php
/**
 * File containing tests for the Tables_Based_Reports_Listing_Service class.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Reports_Listing_Service;
use Sensei\Internal\Services\Reports_Item;

require_once __DIR__ . '/test-class-reports-listing-service.php';

/**
 * Class Tables_Based_Reports_Listing_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service
 */
class Tables_Based_Reports_Listing_Service_Test extends \Reports_Listing_Service_Test {

	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	private $sensei_factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	/**
	 * Insert a progress row directly into the HPPS progress table.
	 *
	 * @param int      $post_id        The post ID.
	 * @param int      $user_id        The user ID.
	 * @param string   $type           The progress type.
	 * @param string   $status         The progress status.
	 */
	private function insert_progress( int $post_id, int $user_id, string $type, string $status ): void {
		$wpdb   = $GLOBALS['wpdb'];
		$table  = $wpdb->prefix . 'sensei_lms_progress';
		$now    = current_time( 'mysql', true );
		$data   = array(
			'post_id'      => $post_id,
			'user_id'      => $user_id,
			'type'         => $type,
			'status'       => $status,
			'started_at'   => $now,
			'completed_at' => in_array( $status, array( 'complete', 'graded', 'passed' ), true ) ? $now : null,
			'created_at'   => $now,
			'updated_at'   => $now,
		);
		$format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Insert a quiz submission row.
	 *
	 * @param int      $quiz_id     The quiz post ID.
	 * @param int      $user_id     The user ID.
	 * @param int|null $final_grade The final grade.
	 */
	private function insert_quiz_submission( int $quiz_id, int $user_id, ?int $final_grade = null ): void {
		$wpdb   = $GLOBALS['wpdb'];
		$table  = $wpdb->prefix . 'sensei_lms_quiz_submissions';
		$now    = current_time( 'mysql', true );
		$data   = array(
			'quiz_id'    => $quiz_id,
			'user_id'    => $user_id,
			'created_at' => $now,
			'updated_at' => $now,
		);
		$format = array( '%d', '%d', '%s', '%s' );
		if ( null !== $final_grade ) {
			$data['final_grade'] = $final_grade;
			$format[]            = '%d';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Tests that get_lesson_students returns reports items for lesson progress.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service::get_lesson_students
	 */
	public function testGetLessonStudents_LessonProgressCreated_ReturnsReportsItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );

		$service = new Tables_Based_Reports_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_students(
			array(
				'post_id' => $lesson_id,
				'type'    => 'sensei_lesson_status',
				'number'  => 10,
				'offset'  => 0,
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Total count should be 1.' );
		$this->assertCount( 1, $result['items'], 'Should return exactly one item.' );
		$this->assertInstanceOf( Reports_Item::class, $result['items'][0], 'Item should be a Reports_Item.' );
		$this->assertSame( 'in-progress', $result['items'][0]->status, 'Status should be in-progress.' );
		$this->assertSame( $user_id, $result['items'][0]->user_id, 'User ID should match.' );
	}

	/**
	 * Tests that get_lesson_students uses the coalesced quiz status when available.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service::get_lesson_students
	 */
	public function testGetLessonStudents_QuizProgressAndSubmissionCreated_ReturnsQuizStatusAndGrade(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array( '_quiz_lesson' => $lesson_id ),
			)
		);
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'passed' );
		$this->insert_quiz_submission( $quiz_id, $user_id, 90 );

		$service = new Tables_Based_Reports_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_students(
			array(
				'post_id' => $lesson_id,
				'type'    => 'sensei_lesson_status',
				'number'  => 10,
				'offset'  => 0,
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertSame( 'passed', $result['items'][0]->status, 'Status should be the coalesced quiz status.' );
		$this->assertSame( 90.0, $result['items'][0]->grade, 'Grade should come from quiz submission.' );
	}

	/**
	 * Tests that get_course_students returns reports items for course progress.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service::get_course_students
	 */
	public function testGetCourseStudents_CourseProgressCreated_ReturnsReportsItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $course_id, $user_id, 'course', 'in-progress' );
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete' );

		$service = new Tables_Based_Reports_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_course_students(
			array(
				'post_id' => $course_id,
				'type'    => 'sensei_course_status',
				'number'  => 10,
				'offset'  => 0,
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Total count should be 1.' );
		$this->assertCount( 1, $result['items'], 'Should return exactly one item.' );
		$this->assertSame( $user_id, $result['items'][0]->user_id, 'User ID should match.' );
		$this->assertNotNull( $result['items'][0]->percent, 'Percent should be computed.' );
	}

	public function testGetCourseStudents_TranslatedCourseQueried_UsesOriginalCourseLessonsForPercent(): void {
		global $wpdb;
		$created    = $this->sensei_factory->get_course_with_lessons( array( 'lesson_count' => 1 ) );
		$translated = $this->sensei_factory->course->create();
		$user_id    = $this->sensei_factory->user->create();
		$this->insert_progress( $created['course_id'], $user_id, 'course', 'in-progress' );
		$this->insert_progress( $created['lesson_ids'][0], $user_id, 'lesson', 'complete' );
		$map_progress = static function ( int $course_id ) use ( $created, $translated ): int {
			return $translated === $course_id ? $created['course_id'] : $course_id;
		};
		add_filter( 'sensei_course_progress_get_course_id', $map_progress );

		try {
			$actual = ( new Tables_Based_Reports_Listing_Service( $wpdb ) )->get_course_students(
				array(
					'post_id' => $translated,
					'status'  => 'any',
				)
			);
		} finally {
			remove_filter( 'sensei_course_progress_get_course_id', $map_progress );
		}

		$this->assertSame( 1, $actual['total_count'] );
		$this->assertSame( 100.0, $actual['items'][0]->percent );
	}

	/**
	 * Tests that get_user_lesson_progress returns a Reports_Item for lesson progress.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service::get_user_lesson_progress
	 */
	public function testGetUserLessonProgress_LessonProgressCreated_ReturnsReportsItem(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete' );

		$service = new Tables_Based_Reports_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_lesson_progress(
			array(
				'post_id' => $lesson_id,
				'user_id' => $user_id,
				'type'    => 'sensei_lesson_status',
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertInstanceOf( Reports_Item::class, $result, 'Should return a Reports_Item.' );
		$this->assertSame( 'complete', $result->status, 'Status should be complete.' );
	}

	/**
	 * Tests that get_user_lesson_progress returns null for a lesson with no progress.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service::get_user_lesson_progress
	 */
	public function testGetUserLessonProgress_NoLessonProgressCreated_ReturnsNull(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$service = new Tables_Based_Reports_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_lesson_progress(
			array(
				'post_id' => $lesson_id,
				'user_id' => $user_id,
				'type'    => 'sensei_lesson_status',
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertNull( $result );
	}

	/**
	 * Tests that get_user_courses returns reports items for course progress.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Reports_Listing_Service::get_user_courses
	 */
	public function testGetUserCourses_CourseProgressCreated_ReturnsReportsItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$this->insert_progress( $course_id, $user_id, 'course', 'in-progress' );

		$service = new Tables_Based_Reports_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_courses(
			array(
				'user_id' => $user_id,
				'type'    => 'sensei_course_status',
				'number'  => 10,
				'offset'  => 0,
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Total count should be 1.' );
		$this->assertCount( 1, $result['items'], 'Should return exactly one item.' );
		$this->assertSame( $course_id, $result['items'][0]->post_id, 'Post ID should match the course.' );
		$this->assertSame( $user_id, $result['items'][0]->user_id, 'User ID should match.' );
	}

	protected function get_report_service(): \Sensei\Internal\Services\Reports_Listing_Service_Interface {
		return new Tables_Based_Reports_Listing_Service( $GLOBALS['wpdb'] );
	}

	protected function seed_report_progress( int $post, int $user, string $type, string $status, string $date ): void {
		$GLOBALS['wpdb']->insert(
			$GLOBALS['wpdb']->prefix . 'sensei_lms_progress',
			array(
				'post_id'      => $post,
				'user_id'      => $user,
				'type'         => $type,
				'status'       => $status,
				'started_at'   => $date,
				'completed_at' => $date,
				'created_at'   => $date,
				'updated_at'   => $date,
			)
		);
	}
}
