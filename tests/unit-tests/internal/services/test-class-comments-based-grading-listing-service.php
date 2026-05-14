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

	public function testGetLessonProgressItems_WithStatusFilter_FiltersResults(): void {
		/* Arrange. */
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		\Sensei_Utils::update_lesson_status( $user1, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'complete' );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'status'  => 'in-progress',
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected one in-progress result.' );
		$this->assertSame( 'in-progress', $result['items'][0]->status, 'Expected in-progress status.' );
	}

	public function testGetLessonProgressItems_WithUserIdFilter_RestrictsToUser(): void {
		/* Arrange. */
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		\Sensei_Utils::update_lesson_status( $user1, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'user_id' => $user1,
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected one result for filtered user.' );
		$this->assertSame( $user1, $result['items'][0]->user_id, 'Expected matching user ID.' );
	}

	public function testGetStatusCounts_ReturnsNull(): void {
		/* Arrange. */
		$service = new Comments_Based_Grading_Listing_Service();

		/* Act & Assert. */
		$this->assertNull( $service->get_status_counts(), 'Comments-based service should always return null.' );
	}

	/**
	 * Verifies lessons with an "included" post_status appear in the listing.
	 *
	 * @dataProvider includedLessonStatusesProvider
	 */
	public function testGetLessonProgressItems_WithIncludedLessonStatus_IncludesInResults( string $included_status ): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create();

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bypass WP filters to test SQL post_status filter directly.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $included_status ), array( 'ID' => $lesson_id ) );
		clean_post_cache( $lesson_id );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items( $this->get_default_args() );

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], "Lesson with post_status '{$included_status}' should be included in total count." );
		$returned_lesson_ids = array_map(
			function ( $item ) {
				return $item->lesson_id;
			},
			$result['items']
		);
		$this->assertContains( $lesson_id, $returned_lesson_ids, "Lesson with post_status '{$included_status}' should appear in items." );
	}

	/**
	 * Verifies lessons with an "excluded" post_status are omitted from the listing.
	 *
	 * @dataProvider excludedLessonStatusesProvider
	 */
	public function testGetLessonProgressItems_WithExcludedLessonStatus_ExcludesFromResults( string $excluded_status ): void {
		/* Arrange. */
		global $wpdb;

		$user_id     = $this->sensei_factory->user->create();
		$excluded_id = $this->sensei_factory->lesson->create();

		\Sensei_Utils::update_lesson_status( $user_id, $excluded_id, 'in-progress' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bypass WP filters to test SQL post_status filter directly.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $excluded_status ), array( 'ID' => $excluded_id ) );
		clean_post_cache( $excluded_id );

		$service = new Comments_Based_Grading_Listing_Service();

		/* Act. */
		$result = $service->get_lesson_progress_items( $this->get_default_args() );

		/* Assert. */
		$this->assertSame( 0, $result['total_count'], "Lesson with post_status '{$excluded_status}' should be excluded from total count." );
	}

	public function includedLessonStatusesProvider(): array {
		return array(
			'publish' => array( 'publish' ),
			'private' => array( 'private' ),
		);
	}

	public function excludedLessonStatusesProvider(): array {
		return array(
			'draft'      => array( 'draft' ),
			'pending'    => array( 'pending' ),
			'future'     => array( 'future' ),
			'auto-draft' => array( 'auto-draft' ),
			'trash'      => array( 'trash' ),
		);
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
