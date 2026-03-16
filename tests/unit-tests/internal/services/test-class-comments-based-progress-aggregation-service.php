<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service;

/**
 * Class Comments_Based_Progress_Aggregation_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service
 */
class Comments_Based_Progress_Aggregation_Service_Test extends \WP_UnitTestCase {

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

	public function testCountStatuses_LessonType_ReturnsStatusCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
	}

	public function testCountStatuses_WithPostIdFilter_RestrictsToPost(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson1   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$lesson2   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson1, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson2, 'complete' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson1,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
		$this->assertArrayNotHasKey( 'complete', $result );
	}

	public function testCountStatuses_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
		/* Arrange. */
		global $wpdb;

		$regular_user = $this->sensei_factory->user->create();
		$guest_user   = $this->sensei_factory->user->create(
			[ 'user_login' => 'sensei_guest_12345' ]
		);
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $regular_user, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $guest_user, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'                        => 'lesson',
				'post_id'                     => $lesson_id,
				'exclude_user_login_prefixes' => [ 'sensei_guest_' ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
	}

	public function testCountStatuses_LessonWithQuizStatus_CountsDirectly(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['passed'] );
	}

	public function testCountStatuses_WithPostInArray_FiltersToSpecifiedPosts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson1   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$lesson2   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson1, 'complete' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson2, 'complete' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'     => 'lesson',
				'post__in' => [ $lesson1 ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'] );
	}

	public function testCountStatuses_WithUserIdArray_FiltersToSpecifiedUsers(): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user1, $lesson_id, 'complete' );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'complete' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
				'user_id' => [ $user1 ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'] );
	}

	public function testCountStatuses_WithIncludeStatusesOverride_KeepsExcludedUsersForOverrideStatuses(): void {
		/* Arrange. */
		global $wpdb;

		$regular_user = $this->sensei_factory->user->create();
		$guest_user   = $this->sensei_factory->user->create(
			[ 'user_login' => 'sensei_guest_12345' ]
		);
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $regular_user, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $guest_user, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'                        => 'lesson',
				'post_id'                     => $lesson_id,
				'exclude_user_login_prefixes' => [ 'sensei_guest_' ],
				'include_statuses_override'   => [ 'ungraded' ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
		$this->assertSame( 1, $result['ungraded'] );
	}
}
