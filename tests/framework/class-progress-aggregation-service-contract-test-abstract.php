<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;

/**
 * Shared behavioral coverage for the Courses Overview aggregation APIs.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service
 */
abstract class Progress_Aggregation_Service_Contract_Test_Abstract extends \WP_UnitTestCase {
	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		$this->sensei_factory->tearDown();
		parent::tearDown();
	}

	public function testCountStatusesByPost_WPMLTranslationsGiven_ReturnsCountsUnderRequestedIds(): void {
		/* Arrange. */
		$original    = $this->sensei_factory->course->create();
		$translation = $this->sensei_factory->course->create();
		$user_id     = $this->sensei_factory->user->create();
		$this->seed_progress( $original, $user_id, 'course', 'complete' );
		$this->add_translation_filters( array( $translation => $original ) );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( $original, $translation ) );

		/* Assert. */
		self::assertSame(
			array(
				$original    => array( 'complete' => 1 ),
				$translation => array( 'complete' => 1 ),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testCountStatusesByPost_PostRestrictionsGiven_ReturnsOnlyRequestedPosts(): void {
		/* Arrange. */
		$course       = $this->sensei_factory->course->create();
		$other_course = $this->sensei_factory->course->create();
		$user_id      = $this->sensei_factory->user->create();
		$other_user   = $this->sensei_factory->user->create();
		$this->seed_progress( $course, $user_id, 'course', 'complete' );
		$this->seed_progress( $course, $other_user, 'course', 'in-progress' );
		$this->seed_progress( $other_course, $user_id, 'course', 'complete' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( $course ) );

		/* Assert. */
		self::assertSame(
			array(
				$course => array(
					'complete'    => 1,
					'in-progress' => 1,
				),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testCountStatusesByPost_MultipleCoursesGiven_ReturnsCountsGroupedByPost(): void {
		/* Arrange. */
		$user1      = $this->sensei_factory->user->create();
		$user2      = $this->sensei_factory->user->create();
		$course_id1 = $this->sensei_factory->course->create();
		$course_id2 = $this->sensei_factory->course->create();
		$this->seed_progress( $course_id1, $user1, 'course', 'complete' );
		$this->seed_progress( $course_id1, $user2, 'course', 'in-progress' );
		$this->seed_progress( $course_id2, $user1, 'course', 'in-progress' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( $course_id1, $course_id2 ) );

		/* Assert. */
		self::assertSame(
			array(
				$course_id1 => array(
					'complete'    => 1,
					'in-progress' => 1,
				),
				$course_id2 => array( 'in-progress' => 1 ),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testCountStatusesByPost_MixedPostStatusesGiven_CountsRequestedPostsRegardlessOfVisibility(): void {
		/* Arrange. */
		$user_id  = $this->sensei_factory->user->create();
		$expected = array();
		foreach ( array( 'publish', 'private', 'draft', 'trash', 'future' ) as $status ) {
			$course_id = $this->sensei_factory->course->create(
				array(
					'post_status' => $status,
					'post_date'   => 'future' === $status ? '2036-01-01 00:00:00' : '2024-01-01 00:00:00',
				)
			);
			$this->seed_progress( $course_id, $user_id, 'course', 'complete' );
			$expected[ $course_id ] = array( 'complete' => 1 );
		}
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array_keys( $expected ) );

		/* Assert. */
		self::assertSame( $expected, $this->sort_counts( $actual ) );
	}

	/**
	 * Create the backend under test.
	 *
	 * @return Progress_Aggregation_Service_Interface
	 */
	abstract protected function get_service(): Progress_Aggregation_Service_Interface;

	/**
	 * Store progress with local start and completion dates in the selected backend.
	 */
	abstract protected function seed_progress( int $post_id, int $user_id, string $type, string $status, ?string $started_at = '2022-01-01 00:00:00', ?string $completed_at = '2022-01-02 00:00:00' ): void;

	private function add_translation_filters( array $map ): void {
		add_filter(
			'wpml_element_language_details',
			static function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'es',
				);
			}
		);
		add_filter(
			'wpml_object_id',
			static function ( $post_id ) use ( $map ) {
				return $map[ $post_id ] ?? $post_id;
			}
		);
		add_filter( 'sensei_course_progress_get_course_id', array( new \Sensei\WPML\Course_Progress(), 'translate_course_id' ) );
	}

	/**
	 * Keep count comparisons strict without depending on database row order.
	 *
	 * @param array $counts Counts grouped by post and status.
	 * @return array Counts sorted by post ID and status.
	 */
	private function sort_counts( array $counts ): array {
		foreach ( $counts as &$statuses ) {
			ksort( $statuses );
		}
		unset( $statuses );
		ksort( $counts );

		return $counts;
	}
}
