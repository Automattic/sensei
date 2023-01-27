<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Continue_Course_Block class.
 */
class Sensei_Continue_Course_Block_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Continue Course block.
	 *
	 * @var Sensei_Continue_Course_Block
	 */
	private $block;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/button-continue-course -->
<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">Continue</a></div>
<!-- /wp:sensei-lms/button-continue-course -->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		$this->factory = new Sensei_Factory();
		$this->block   = new Sensei_Continue_Course_Block();
		$this->course  = $this->factory->course->create_and_get( [ 'post_name' => 'continue-course-block' ] );

		$GLOBALS['post'] = $this->course;
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/button-continue-course' );
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Doesn't render the block if the student is not enrolled in the course.
	 *
	 * @covers Sensei_Continue_Course_Block::render
	 */
	public function testRender_NotEnrolled_ReturnsEmptyString() {
		$this->login_as_student();

		$result = $this->block->render( [], self::CONTENT );

		$this->assertEmpty( $result );
	}

	/**
	 * Doesn't render the block if the student is enrolled and has already completed the course.
	 *
	 * @covers Sensei_Continue_Course_Block::render
	*/
	public function testRender_EnrolledAndCourseCompleted_ReturnsEmptyString() {
		$user_id = $this->factory->user->create();
		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );
		Sensei_Utils::update_course_status( $user_id, $this->course->ID, 'complete' );
		$this->login_as( $user_id );

		$result = $this->block->render( [], self::CONTENT );

		$this->assertEmpty( $result );
	}

	/**
	 * Renders the block and links to the course page if the student is enrolled but has not completed the course.
	 *
	 * @covers Sensei_Continue_Course_Block::render
	 */
	public function testRender_EnrolledAndCourseNotCompleted_ReturnsModifiedBlockContent() {
		$user_id = $this->factory->user->create();
		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );
		$this->login_as( $user_id );

		$result = $this->block->render( [], self::CONTENT );

		$this->assertRegExp( '|<form action="http://example.org/\?course=continue-course-block" method="get".*>|', $result );
	}

	public function testRender_EnrolledAndStartedLesson_ReturnsModifiedBlockContentWithLessonUrl() {
		/* Arrange */
		$user_id           = $this->factory->user->create();
		$course_lesson_ids = $this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $this->course->ID ] ] );

		$this->login_as( $user_id );

		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );
		Sensei_Utils::user_start_lesson( $user_id, $course_lesson_ids[0] );

		/* Act */
		$result = $this->block->render( [], self::CONTENT );

		/* Assert */
		$lesson_title = get_post( $course_lesson_ids[0] )->post_name;
		$this->assertRegExp( '|<form action="http://example.org/\?lesson=' . $lesson_title . '" method="get".*>|', $result );
	}


	public function testRender_WhenTheCourseDoesntHaveALesson_ReturnsLinkToTheCourse() {
		/* Arrange */
		$user_id = $this->factory->user->create();

		$this->login_as( $user_id );

		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );

		/* Act */
		$result = $this->block->render( [], self::CONTENT );

		/* Assert */
		$course_title = $this->course->post_name;
		$this->assertRegExp( '|<form action="http://example.org/\?course=' . $course_title . '" method="get".*>|', $result );
	}

	public function testRender_WhenTheStudentDoesntHaveStartedALesson_ReturnsLinkToFirstLesson() {
		/* Arrange */
		$user_id           = $this->factory->user->create();
		$course_lesson_ids = $this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $this->course->ID ] ] );

		$this->login_as( $user_id );

		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );
		Sensei_Utils::user_start_lesson( $user_id, $course_lesson_ids[0], true );

		/* Act */
		$result = $this->block->render( [], self::CONTENT );

		/* Assert */
		$lesson_title = get_post( $course_lesson_ids[1] )->post_name;
		$this->assertRegExp( '|<form action="http://example.org/\?lesson=' . $lesson_title . '" method="get".*>|', $result );
	}
}
