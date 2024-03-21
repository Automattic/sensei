<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Course_Progress;

class Course_Progress_Test extends \WP_UnitTestCase {

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
		$this->assertEquals( 10, has_filter( 'sensei_course_manual_enrolment_enroll_learner_course_id', array( $course_progress, 'translate_course_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_course_manual_enrolment_withdraw_learner_course_id', array( $course_progress, 'translate_course_id' ) ) );
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
}
