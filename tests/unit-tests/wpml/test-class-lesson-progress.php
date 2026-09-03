<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Lesson_Progress;

class Lesson_Progress_Test extends \WP_UnitTestCase {
	/**
	 * Sensei Factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new \Sensei_Factory();
	}

	public function tear_down(): void {
		remove_all_filters( 'wpml_current_language' );
		remove_all_filters( 'wpml_element_language_details' );
		remove_all_filters( 'wpml_object_id' );
		parent::tear_down();
		$this->factory->tearDown();
	}

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$lesson_progress = new Lesson_Progress();

		/* Act. */
		$lesson_progress->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_utils_user_completed_lesson_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_create_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_get_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_has_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_delete_for_lesson_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_find_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_cache_key_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_check_for_activity_args', array( $lesson_progress, 'translate_lesson_query_args' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_grading_filter_statuses', array( $lesson_progress, 'translate_lesson_query_args' ) ) );
		$this->assertEquals( 20, has_filter( 'sensei_count_statuses_args', array( $lesson_progress, 'translate_lesson_query_args' ) ), 'The grading totals hook should run after the teacher restrictions.' );
	}

	public function testTranslateLessonQueryArgs_TranslatedLessonQueried_FindsTheOriginalLessonProgress() {
		/* Arrange. */
		$user_id              = $this->factory->user->create();
		$original_lesson_id   = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();

		\Sensei_Utils::update_lesson_status( $user_id, $original_lesson_id, 'complete' );

		$this->stub_translations( array( $translated_lesson_id => $original_lesson_id ) );
		( new Lesson_Progress() )->init();

		/* Act. */
		$actual = \Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $translated_lesson_id,
				'type'    => 'sensei_lesson_status',
				'status'  => 'any',
			)
		);

		/* Assert. */
		$this->assertSame( 1, $actual );
	}

	public function testTranslateLessonQueryArgs_TranslatedLessonsGivenInPostIn_ReturnsTheOriginalLessonIds() {
		/* Arrange. */
		$original_lesson_id   = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();

		$this->stub_translations( array( $translated_lesson_id => $original_lesson_id ) );

		$lesson_progress = new Lesson_Progress();

		/* Act. */
		$actual = $lesson_progress->translate_lesson_query_args( array( 'post__in' => array( $translated_lesson_id ) ) );

		/* Assert. */
		$this->assertSame( array( 'post__in' => array( $original_lesson_id ) ), $actual );
	}

	public function testTranslateLessonQueryArgs_NoLessonsSentinelGiven_KeepsTheSentinel() {
		/* Arrange. */
		// Anything that translated the sentinel would turn it into a real ID.
		$this->stub_translations( array( 0 => 123 ) );

		$lesson_progress = new Lesson_Progress();

		/* Act. */
		$actual = $lesson_progress->translate_lesson_query_args( array( 'post__in' => array( 0 ) ) );

		/* Assert. */
		$this->assertSame( array( 'post__in' => array( 0 ) ), $actual, 'The "no lessons" restriction must keep matching nothing.' );
	}

	public function testTranslateLessonId_QuizCacheReadWithTranslatedLesson_ReadsTheOriginalLessonKey() {
		/* Arrange. */
		$user_id              = $this->factory->user->create();
		$original_lesson_id   = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$this->factory->quiz->create( array( 'post_parent' => $translated_lesson_id ) );

		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'es',
				);
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $object_id, $type ) use ( $translated_lesson_id, $original_lesson_id ) {
				if ( 'lesson' === $type && $translated_lesson_id === $object_id ) {
					return $original_lesson_id;
				}
				return $object_id;
			},
			10,
			2
		);

		( new Lesson_Progress() )->init();

		set_transient( 'quiz_grades_' . $user_id . '_' . $original_lesson_id, array( 99 => 1 ) );

		/* Act. */
		$grades = Sensei()->quiz->get_user_grades( $translated_lesson_id, $user_id );

		/* Assert. */
		$this->assertSame( array( 99 => 1 ), $grades, 'Reading with the translated lesson should hit the original lesson cache key.' );
	}

	public function testTranslateLessonId_WhenCalled_ReturnsMatchingValue() {
		/* Arrange. */
		$lesson_id = 1;

		$lesson_progress = new Lesson_Progress();

		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'fr',
				);
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $lesson_id, $type, $original, $original_language_code ) {
				if ( 1 === $lesson_id && 'lesson' === $type && true === $original && 'en' === $original_language_code ) {
					return 2;
				} else {
					return 3;
				}
			},
			10,
			4
		);

		/* Act. */
		$actual = $lesson_progress->translate_lesson_id( $lesson_id );

		/* Assert. */
		$this->assertEquals( 2, $actual );
	}

	/**
	 * Make the given translated IDs resolve to their originals.
	 *
	 * @param array $map Translated ID => original ID.
	 */
	private function stub_translations( array $map ) {
		add_filter(
			'wpml_current_language',
			function () {
				return 'es';
			}
		);

		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'es',
				);
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $object_id ) use ( $map ) {
				return $map[ $object_id ] ?? $object_id;
			},
			10,
			1
		);
	}
}
