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
	public function setUp() {
		parent::setUp();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		$this->factory = new Sensei_Factory();
		$this->block   = new Sensei_Continue_Course_Block();
		$this->course  = $this->factory->course->create_and_get( [ 'post_name' => 'continue-course-block' ] );

		$GLOBALS['post'] = $this->course;
	}

	public function tearDown() {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/button-continue-course' );
	}

	public static function tearDownAfterClass() {
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

		$this->assertRegExp( '|<a href="http://example.org/\?course=continue-course-block".*>Continue</a>|', $result );
	}
}
