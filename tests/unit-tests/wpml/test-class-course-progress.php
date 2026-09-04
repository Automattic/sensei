<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Course_Progress;

class Course_Progress_Test extends \WP_UnitTestCase {
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
		$course_progress = new Course_Progress();

		/* Act. */
		$course_progress->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_course_is_user_enrolled_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_block_take_course_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_progress_create_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_progress_get_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_progress_has_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_progress_delete_for_course_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_progress_find_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_count_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_start_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_check_for_activity_args', array( $course_progress, 'translate_course_query_args' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_learners_query_args', array( $course_progress, 'translate_learners_query_args' ) ) );
	}

	public function testTranslateLearnersQueryArgs_TranslatedCourseFiltered_FindsTheOriginalCourseStudents() {
		/* Arrange. */
		$user_id              = $this->factory->user->create();
		$original_course_id   = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		\Sensei_Utils::update_course_status( $user_id, $original_course_id, 'complete' );

		$this->stub_translations( array( $translated_course_id => $original_course_id ) );
		( new Course_Progress() )->init();

		/* Act. */
		$learners = ( new \Sensei_Db_Query_Learners( array( 'filter_by_course_id' => $translated_course_id ) ) )->get_all();

		/* Assert. */
		$this->assertEquals( array( $user_id ), wp_list_pluck( $learners, 'user_id' ) );
	}

	public function testTranslateCourseQueryArgs_TranslatedCourseQueried_FindsTheOriginalCourseProgress() {
		/* Arrange. */
		$user_id              = $this->factory->user->create();
		$original_course_id   = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		\Sensei_Utils::update_course_status( $user_id, $original_course_id, 'complete' );

		$this->stub_translations( array( $translated_course_id => $original_course_id ) );
		( new Course_Progress() )->init();

		/* Act. */
		$actual = \Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $translated_course_id,
				'type'    => 'sensei_course_status',
			)
		);

		/* Assert. */
		$this->assertSame( 1, $actual );
	}

	public function testTranslateCourseQueryArgs_LessonGiven_KeepsTheLessonId() {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();

		$this->stub_translations( array( $lesson_id => 999 ) );

		$course_progress = new Course_Progress();

		/* Act. */
		$actual = $course_progress->translate_course_query_args( array( 'post_id' => $lesson_id ) );

		/* Assert. */
		$this->assertSame( array( 'post_id' => $lesson_id ), $actual );
	}

	public function testTranslateCourseId_WhenCalled_ReturnsMatchingValue() {
		/* Arrange. */
		$course_id = 1;

		$course_progress = new Course_Progress();

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
			function ( $course_id, $type, $original, $original_language_code ) {
				if ( 1 === $course_id && 'course' === $type && true === $original && 'en' === $original_language_code ) {
					return 2;
				} else {
					return 3;
				}
			},
			10,
			4
		);

		/* Act. */
		$actual = $course_progress->translate_course_id( $course_id );

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
