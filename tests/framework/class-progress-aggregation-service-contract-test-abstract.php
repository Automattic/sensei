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
		$actual = $service->count_statuses_by_post(
			array(
				'type'     => 'course',
				'post__in' => array( $original, $translation ),
			)
		);

		/* Assert. */
		self::assertSame(
			array(
				$original    => array( 'complete' => 1 ),
				$translation => array( 'complete' => 1 ),
			),
			$actual
		);
	}

	public function testCountStatusesByPost_UserAndPostRestrictionsGiven_ReturnsOnlyMatchingProgress(): void {
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
		$actual = $service->count_statuses_by_post(
			array(
				'type'     => 'course',
				'post_id'  => $course,
				'post__in' => array( $other_course ),
				'user_id'  => $user_id,
			)
		);

		/* Assert. */
		self::assertSame( array( $course => array( 'complete' => 1 ) ), $actual );
	}

	public function testCountStatusesByPost_CourseTypeGiven_ReturnsCountsGroupedByPost(): void {
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
		$actual = $service->count_statuses_by_post(
			array(
				'type'     => 'course',
				'post__in' => array( $course_id1, $course_id2 ),
			)
		);

		/* Assert. */
		self::assertSame(
			array(
				$course_id1 => array(
					'complete'    => 1,
					'in-progress' => 1,
				),
				$course_id2 => array( 'in-progress' => 1 ),
			),
			$actual
		);
	}

	public function testCountStatusesByPost_LessonsWithAndWithoutQuizzesGiven_GroupsQuizStatusesAndLessonStatuses(): void {
		/* Arrange. */
		$course_id    = $this->sensei_factory->course->create();
		$plain_lesson = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$quiz_lesson  = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$quiz_id      = $this->sensei_factory->quiz->create( array( 'post_parent' => $quiz_lesson ) );
		$first_user   = $this->sensei_factory->user->create();
		$second_user  = $this->sensei_factory->user->create();
		$third_user   = $this->sensei_factory->user->create();
		update_post_meta( $quiz_lesson, '_lesson_quiz', $quiz_id );
		$this->seed_progress( $plain_lesson, $first_user, 'lesson', 'complete' );
		$this->seed_progress( $plain_lesson, $second_user, 'lesson', 'in-progress' );
		// Quiz status takes precedence even when lesson progress has a different status.
		$this->seed_lesson_with_quiz_status( $quiz_lesson, $quiz_id, $first_user, 'in-progress', 'passed' );
		$this->seed_lesson_with_quiz_status( $quiz_lesson, $quiz_id, $second_user, 'in-progress', 'passed' );
		$this->seed_lesson_with_quiz_status( $quiz_lesson, $quiz_id, $third_user, 'complete', 'failed' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post(
			array(
				'type'     => 'lesson',
				'post__in' => array( $plain_lesson, $quiz_lesson ),
			)
		);

		/* Assert. */
		self::assertSame(
			array(
				$plain_lesson => array(
					'complete'    => 1,
					'in-progress' => 1,
				),
				$quiz_lesson  => array(
					'failed' => 1,
					'passed' => 2,
				),
			),
			$actual
		);
	}

	public function testCountStatusesByPost_InvalidTypeGiven_ReturnsEmptyArray(): void {
		/* Arrange. */
		$service = $this->get_service();
		$this->setExpectedIncorrectUsage( get_class( $service ) . '::count_statuses_by_post' );

		/* Act. */
		$actual = $service->count_statuses_by_post( array( 'type' => 'invalid' ) );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	public function testCountStatusesByPost_MixedPostStatusesGiven_IncludesOnlyPublishedAndPrivatePosts(): void {
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
			if ( in_array( $status, array( 'publish', 'private' ), true ) ) {
				$expected[ $course_id ] = array( 'complete' => 1 );
			}
		}
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( 'type' => 'course' ) );

		/* Assert. */
		self::assertSame( $expected, $actual );
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

	/**
	 * Store the lesson and quiz statuses in the form used by this backend.
	 */
	abstract protected function seed_lesson_with_quiz_status( int $lesson_id, int $quiz_id, int $user_id, string $lesson_status, string $quiz_status ): void;

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
		add_filter( 'sensei_lesson_progress_get_lesson_id', array( new \Sensei\WPML\Lesson_Progress(), 'translate_lesson_id' ) );
	}
}
