<?php
/**
 * File containing tests for the Tables_Based_Analysis_Listing_Service class.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Analysis_Listing_Service;
use Sensei\Internal\Services\Analysis_Item;

/**
 * Class Tables_Based_Analysis_Listing_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Analysis_Listing_Service
 */
class Tables_Based_Analysis_Listing_Service_Test extends \WP_UnitTestCase {

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
	 * @param int|null $parent_post_id The parent post ID.
	 */
	private function insert_progress( int $post_id, int $user_id, string $type, string $status, ?int $parent_post_id = null ): void {
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
		if ( null !== $parent_post_id ) {
			$data['parent_post_id'] = $parent_post_id;
			$format[]               = '%d';
		}
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
		$now    = current_time( 'mysql' );
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
	 * Tests that get_lesson_students returns analysis items for lesson progress.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Analysis_Listing_Service::get_lesson_students
	 */
	public function testGetLessonStudents_WithLessonProgress_ReturnsAnalysisItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_students(
			array(
				'lesson_id' => $lesson_id,
				'per_page'  => 10,
				'offset'    => 0,
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertInstanceOf( Analysis_Item::class, $result['items'][0] );
		$this->assertSame( 'in-progress', $result['items'][0]->status );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
	}

	/**
	 * Tests that get_lesson_students uses the coalesced quiz status when available.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Analysis_Listing_Service::get_lesson_students
	 */
	public function testGetLessonStudents_WithQuizStatus_UsesCoalescedStatus(): void {
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
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'passed', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user_id, 90 );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_students(
			array(
				'lesson_id' => $lesson_id,
				'per_page'  => 10,
				'offset'    => 0,
			)
		);

		/* Assert. */
		$this->assertSame( 'passed', $result['items'][0]->status );
		$this->assertSame( 90.0, $result['items'][0]->grade );
	}

	/**
	 * Test testGetCourseStudents_WithCourseProgress_ReturnsAnalysisItems.
	 */
	public function testGetCourseStudents_WithCourseProgress_ReturnsAnalysisItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $course_id, $user_id, 'course', 'in-progress' );
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_course_students(
			array(
				'course_id' => $course_id,
				'per_page'  => 10,
				'offset'    => 0,
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
		$this->assertNotNull( $result['items'][0]->percent );
	}

	/**
	 * Test testGetUserLessonProgress_ReturnsProgressKeyedByLessonId.
	 */
	public function testGetUserLessonProgress_ReturnsProgressKeyedByLessonId(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_lesson_progress( $course_id, $user_id );

		/* Assert. */
		$this->assertArrayHasKey( $lesson_id, $result );
		$this->assertInstanceOf( Analysis_Item::class, $result[ $lesson_id ] );
		$this->assertSame( 'complete', $result[ $lesson_id ]->status );
	}

	/**
	 * Test testGetUserLessonProgress_WithNoProgress_ReturnsNullForLesson.
	 */
	public function testGetUserLessonProgress_WithNoProgress_ReturnsNullForLesson(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_lesson_progress( $course_id, $user_id );

		/* Assert. */
		$this->assertArrayHasKey( $lesson_id, $result );
		$this->assertNull( $result[ $lesson_id ] );
	}

	/**
	 * Test testGetUserCourses_WithCourseProgress_ReturnsAnalysisItems.
	 */
	public function testGetUserCourses_WithCourseProgress_ReturnsAnalysisItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$this->insert_progress( $course_id, $user_id, 'course', 'in-progress' );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_courses(
			array(
				'user_id'  => $user_id,
				'per_page' => 10,
				'offset'   => 0,
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( $course_id, $result['items'][0]->post_id );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
	}

	/**
	 * Test testGetLessonAggregates_ReturnsAggregateStats.
	 */
	public function testGetLessonAggregates_ReturnsAggregateStats(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
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

		$this->insert_progress( $lesson_id, $user1, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user1, 'quiz', 'passed', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user1, 80 );

		$this->insert_progress( $lesson_id, $user2, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_aggregates( $course_id );

		/* Assert. */
		$this->assertArrayHasKey( $lesson_id, $result );
		$this->assertSame( 2, $result[ $lesson_id ]['student_count'] );
		$this->assertSame( 1, $result[ $lesson_id ]['completion_count'] );
		$this->assertSame( 80.0, $result[ $lesson_id ]['average_grade'] );
	}

	/**
	 * Test testGetLessonStudents_WithOffsetBeyondTotal_CorrectsPagination.
	 */
	public function testGetLessonStudents_WithOffsetBeyondTotal_CorrectsPagination(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_students(
			array(
				'lesson_id' => $lesson_id,
				'per_page'  => 10,
				'offset'    => 100,
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
	}
}
