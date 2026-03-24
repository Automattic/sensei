<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Grading_Listing_Service;
use Sensei\Internal\Services\Grading_Item;

/**
 * Class Comments_Based_Grading_Listing_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Grading_Listing_Service
 */
class Comments_Based_Grading_Listing_Service_Test extends \WP_UnitTestCase {

	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	/**
	 * Build default query args for get_lesson_progress_items.
	 *
	 * @param array $overrides Args to override.
	 * @return array
	 */
	private function get_default_args( array $overrides = [] ): array {
		return array_merge(
			[
				'type'    => 'sensei_lesson_status',
				'number'  => 10,
				'offset'  => 0,
				'orderby' => '',
				'order'   => 'DESC',
				'status'  => 'any',
			],
			$overrides
		);
	}

	public function testGetLessonProgressItems_WithLessonStatus_ReturnsGradingItems(): void {
		/* Arrange. */
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args( [ 'post_id' => $lesson_id ] )
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected exactly one result.' );
		$this->assertCount( 1, $result['items'], 'Expected exactly one item.' );
		$this->assertInstanceOf( Grading_Item::class, $result['items'][0], 'Expected a Grading_Item instance.' );
		$this->assertSame( 'in-progress', $result['items'][0]->status, 'Expected in-progress status.' );
		$this->assertSame( $user_id, $result['items'][0]->user_id, 'Expected matching user ID.' );
		$this->assertSame( $lesson_id, $result['items'][0]->lesson_id, 'Expected matching lesson ID.' );
		$this->assertNull( $result['items'][0]->grade, 'Expected null grade for non-graded item.' );
	}

	public function testGetLessonProgressItems_WithGradedStatus_ReturnsGradeValue(): void {
		/* Arrange. */
		$user_id    = $this->sensei_factory->user->create();
		$course_id  = $this->sensei_factory->course->create();
		$lesson_id  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$comment_id = \Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'graded' );
		update_comment_meta( $comment_id, 'grade', 85 );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args( [ 'post_id' => $lesson_id ] )
		);

		/* Assert. */
		$this->assertSame( 85.0, $result['items'][0]->grade );
	}

	public function testGetLessonProgressItems_WithOffsetBeyondTotal_CorrectsPagination(): void {
		/* Arrange. */
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'offset'  => 100,
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected one total item.' );
		$this->assertCount( 1, $result['items'], 'Expected offset correction to return items.' );
	}
}
