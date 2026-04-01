<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Analysis_Listing_Service;
use Sensei\Internal\Services\Analysis_Item;

/**
 * Class Comments_Based_Analysis_Listing_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Analysis_Listing_Service
 */
class Comments_Based_Analysis_Listing_Service_Test extends \WP_UnitTestCase {

	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	/**
	 * Start a lesson for a user via the comments API.
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @param int $user_id   User ID.
	 * @param string $status Status.
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

	public function testGetLessonStudents_WithLessonStatus_ReturnsAnalysisItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->create_lesson_status( $lesson_id, $user_id, 'in-progress' );

		$service = new Comments_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_students(
			[
				'lesson_id' => $lesson_id,
				'per_page'  => 10,
				'offset'    => 0,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertInstanceOf( Analysis_Item::class, $result['items'][0] );
		$this->assertSame( 'in-progress', $result['items'][0]->status );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
	}

	public function testGetCourseStudents_WithCourseStatus_ReturnsAnalysisItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$this->create_course_status( $course_id, $user_id, 'in-progress' );

		$service = new Comments_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_course_students(
			[
				'course_id' => $course_id,
				'per_page'  => 10,
				'offset'    => 0,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertInstanceOf( Analysis_Item::class, $result['items'][0] );
		$this->assertSame( $user_id, $result['items'][0]->user_id );
	}

	public function testGetUserCourses_WithCourseStatus_ReturnsAnalysisItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$this->create_course_status( $course_id, $user_id, 'in-progress' );

		$service = new Comments_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_courses(
			[
				'user_id'  => $user_id,
				'per_page' => 10,
				'offset'   => 0,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( $course_id, $result['items'][0]->post_id );
	}

	public function testGetUserLessonProgress_WithLessonStatus_ReturnsProgressKeyedByLessonId(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->create_lesson_status( $lesson_id, $user_id, 'complete' );

		$service = new Comments_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_lesson_progress( $course_id, $user_id );

		/* Assert. */
		$this->assertArrayHasKey( $lesson_id, $result );
		$this->assertInstanceOf( Analysis_Item::class, $result[ $lesson_id ] );
		$this->assertSame( 'complete', $result[ $lesson_id ]->status );
	}

	public function testGetUserLessonProgress_WithNoProgress_ReturnsNullForLesson(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$service = new Comments_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_user_lesson_progress( $course_id, $user_id );

		/* Assert. */
		$this->assertArrayHasKey( $lesson_id, $result );
		$this->assertNull( $result[ $lesson_id ] );
	}

	public function testGetLessonAggregates_WithStudentProgress_ReturnsAggregateStats(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$this->create_lesson_status( $lesson_id, $user1, 'complete' );
		$this->create_lesson_status( $lesson_id, $user2, 'in-progress' );

		$service = new Comments_Based_Analysis_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_aggregates( $course_id );

		/* Assert. */
		$this->assertNotEmpty( $result );
		$found = false;
		foreach ( $result as $agg ) {
			if ( $agg['lesson_id'] === $lesson_id ) {
				$found = true;
				$this->assertSame( 2, $agg['student_count'] );
				$this->assertSame( 1, $agg['completion_count'] );
				break;
			}
		}
		$this->assertTrue( $found, 'Expected to find aggregates for the lesson.' );
	}
}
