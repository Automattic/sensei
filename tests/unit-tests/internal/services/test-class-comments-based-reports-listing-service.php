<?php
/**
 * File containing tests for the Comments_Based_Reports_Listing_Service class.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Reports_Listing_Service;
use Sensei\Internal\Services\Reports_Item;

/**
 * Class Comments_Based_Reports_Listing_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service
 */
class Comments_Based_Reports_Listing_Service_Test extends \WP_UnitTestCase {

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
	 * Start a lesson for a user via the comments API.
	 *
	 * @param int    $lesson_id Lesson post ID.
	 * @param int    $user_id   User ID.
	 * @param string $status    Status.
	 * @return int Comment ID.
	 */
	private function create_lesson_status( int $lesson_id, int $user_id, string $status = 'in-progress' ): int {
		$comment_id = \Sensei_Utils::update_lesson_status( $user_id, $lesson_id, $status );
		return (int) $comment_id;
	}

	/**
	 * Start a course for a user via the comments API.
	 *
	 * @param int    $course_id Course post ID.
	 * @param int    $user_id   User ID.
	 * @param string $status    Status.
	 * @return int Comment ID.
	 */
	private function create_course_status( int $course_id, int $user_id, string $status = 'in-progress' ): int {
		$comment_id = \Sensei_Utils::update_course_status( $user_id, $course_id, $status );
		return (int) $comment_id;
	}

	/**
	 * Tests that get_lesson_students returns reports items for a lesson with status.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_lesson_students
	 */
	public function testGetLessonStudents_WithLessonStatus_ReturnsReportsItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->create_lesson_status( $lesson_id, $user_id, 'in-progress' );

		$service = new Comments_Based_Reports_Listing_Service();

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
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertInstanceOf( Reports_Item::class, $result['items'][0] );
		$this->assertSame( 'in-progress', $result['items'][0]->status );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
	}

	/**
	 * Tests that get_course_students returns reports items for a course with status.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_course_students
	 */
	public function testGetCourseStudents_WithCourseStatus_ReturnsReportsItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$this->create_course_status( $course_id, $user_id, 'in-progress' );

		$service = new Comments_Based_Reports_Listing_Service();

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
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertInstanceOf( Reports_Item::class, $result['items'][0] );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
	}

	/**
	 * Tests that get_user_courses returns reports items for a user with course status.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_user_courses
	 */
	public function testGetUserCourses_WithCourseStatus_ReturnsReportsItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$this->create_course_status( $course_id, $user_id, 'in-progress' );

		$service = new Comments_Based_Reports_Listing_Service();

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
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( $course_id, $result['items'][0]->post_id );
	}

	/**
	 * Tests that get_user_lesson_progress returns a Reports_Item for a lesson with status.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_user_lesson_progress
	 */
	public function testGetUserLessonProgress_WithLessonStatus_ReturnsReportsItem(): void {
		/* Arrange. */
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->create_lesson_status( $lesson_id, $user_id, 'complete' );

		$service = new Comments_Based_Reports_Listing_Service();

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
		$this->assertInstanceOf( Reports_Item::class, $result );
		$this->assertSame( 'complete', $result->status );
	}

	/**
	 * Tests that get_user_lesson_progress returns null for a lesson with no progress.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_user_lesson_progress
	 */
	public function testGetUserLessonProgress_WithNoProgress_ReturnsNull(): void {
		/* Arrange. */
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$service = new Comments_Based_Reports_Listing_Service();

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
	 * Tests that get_lesson_student_count returns count of all students.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_lesson_student_count
	 */
	public function testGetLessonStudentCount_WithStudentProgress_ReturnsCount(): void {
		/* Arrange. */
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$this->create_lesson_status( $lesson_id, $user1, 'complete' );
		$this->create_lesson_status( $lesson_id, $user2, 'in-progress' );

		$service = new Comments_Based_Reports_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_student_count(
			array(
				'post_id' => $lesson_id,
				'type'    => 'sensei_lesson_status',
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertSame( 2, $result, 'Student count should include all statuses.' );
	}

	/**
	 * Tests that get_lesson_completion_count returns count of completed students only.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_lesson_completion_count
	 */
	public function testGetLessonCompletionCount_WithStudentProgress_ReturnsCompletedOnly(): void {
		/* Arrange. */
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$this->create_lesson_status( $lesson_id, $user1, 'complete' );
		$this->create_lesson_status( $lesson_id, $user2, 'in-progress' );

		$service = new Comments_Based_Reports_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_completion_count(
			array(
				'post_id' => $lesson_id,
				'type'    => 'sensei_lesson_status',
				'status'  => array( 'complete', 'graded', 'passed', 'failed' ),
				'count'   => true,
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result, 'Completion count should only include completed statuses.' );
	}
}
