<?php
/**
 * This file contains the Sensei_Learning_Mode_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Learning_Mode_Test class.
 *
 * @group learning-mode
 */
class Sensei_Learning_Mode_Test extends WP_UnitTestCase {

	use Sensei_Test_Login_Helpers;
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Sensei Factory helper class - useful to create objects for testing.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Instance of `Sensei_Learning_Mode_Option` under test.
	 *
	 * @var Sensei_Learning_Mode_Theme
	 */
	private $instance;

	/**
	 * Setup method. Run first on every test execution.
	 */
	public function setup() {
		parent::setup();
		$this->factory  = new Sensei_Factory();
		$this->instance = Sensei_Learning_Mode::instance();
		$this->prepareEnrolmentManager();
	}

	/**
	 * Testing the Course Theme class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Learning_Mode' ), 'Sensei Course Theme class should exist' );
	}

	public function testPreviewModeNotAllowedToNonPreviliged() {
		$course          = $this->factory->course->create_and_get();
		$GLOBALS['post'] = $course;

		$this->login_as_student();

		$_GET[ Sensei_Learning_Mode::PREVIEW_QUERY_VAR ] = (string) $course->ID;
		$allowed                                        = Sensei_Learning_Mode::is_preview_mode( $course->ID );
		$this->assertFalse( $allowed, 'Should not allow preview to students.' );
	}

	public function testPreviewModeNotAllowedIfNotCoursePage() {
		$post            = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;

		$this->login_as_admin();

		$allowed = Sensei_Learning_Mode::is_preview_mode( $post->ID );
		$this->assertFalse( $allowed, 'Should not allow preview if not a course related page.' );
	}

	public function testPreviewModeNotAllowedIfPreviewQueryNotCurrentCourse() {
		$course          = $this->factory->course->create_and_get();
		$another_course  = $this->factory->course->create_and_get();
		$GLOBALS['post'] = $course;

		$this->login_as_admin();

		$_GET[ Sensei_Learning_Mode::PREVIEW_QUERY_VAR ] = (string) $another_course->ID;
		$allowed                                        = Sensei_Learning_Mode::is_preview_mode( $course->ID );
		$this->assertFalse( $allowed, 'Should not allow preview if preview query id is not current course page.' );
	}

	public function testAddLessonVideoToContentAddsOnlyOnceEvenIfCalledMultipleTimes() {
		$course_info = $this->factory->get_course_with_lessons();
		$course_id   = $course_info['course_id'];
		$lesson_id   = array_pop( $course_info['lesson_ids'] );
		$lesson      = get_post( $lesson_id );

		// Setup globals.
		$GLOBALS['post']                = $lesson;
		$GLOBALS['wp_query']->post      = $lesson;
		$GLOBALS['wp_query']->is_single = true;

		// Set video embed.
		update_post_meta( $lesson_id, '_lesson_video_embed', 'VIDEO_EMBED_CODE' );

		// Enable Learning Mode (course theme).
		update_post_meta( $course_id, Sensei_Learning_Mode_Option::THEME_POST_META_NAME, Sensei_Learning_Mode_Option::SENSEI_THEME );

		// Enrol student in course.
		$user_id = $this->login_as_student()->get_user_by_role( 'subscriber' );
		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		$input = 'THE LESSON CONTENT';
		// Call method two times.
		$output = $this->instance->add_lesson_video_to_content( $input );
		$output = $this->instance->add_lesson_video_to_content( $output );
		// Expect only one video embed class.
		$this->assertEquals( 1, substr_count( $output, Sensei_Frontend::VIDEO_EMBED_CLASS ) );
	}
}
