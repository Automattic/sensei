<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Block_View_Results class.
 */
class Sensei_Block_View_Results_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * View Results block.
	 *
	 * @var Sensei_Block_View_Results
	 */
	private $block;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/button-view-results -->
<div class="wp-block-sensei-lms-button-view-results is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">View Results</a></div>
<!-- /wp:sensei-lms/button-view-results -->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		$this->factory = new Sensei_Factory();
		$this->block   = new Sensei_Block_View_Results();
		$this->course  = $this->factory->course->create_and_get( [ 'post_name' => 'view-results-block' ] );

		$GLOBALS['post'] = $this->course;
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/button-view-results' );
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Doesn't render the block if the student has not completed the course.
	 *
	 * @covers Sensei_Block_View_Results::render
	 */
	public function testRender_CourseNotCompleted_ReturnsEmptyString() {
		$user_id = $this->factory->user->create();
		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );
		$this->login_as( $user_id );

		$result = $this->block->render( [], self::CONTENT );

		$this->assertEmpty( $result );
	}

	/**
	 * Renders the block and links to the Course Completed page if the student has completed the course.
	 *
	 * @covers Sensei_Block_View_Results::render
	 */
	public function testRender_CourseCompleted_ReturnsModifiedBlockContent() {
		// Student.
		$user_id = $this->factory->user->create();
		$this->manuallyEnrolStudentInCourse( $user_id, $this->course->ID );
		Sensei_Utils::update_course_status( $user_id, $this->course->ID, 'complete' );
		$this->login_as( $user_id );

		// Course Completed page.
		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $page_id );

		$result = $this->block->render( [], self::CONTENT );

		$this->assertMatchesRegularExpression(
			"|<form method=\"get\" action=\"http://example.org/\?page_id={$page_id}&#038;course_id={$this->course->ID}\".*><input type=\"hidden\" name=\"page_id\" value=\"{$page_id}\"><input type=\"hidden\" name=\"course_id\" value=\"{$this->course->ID}\">|",
			$result
		);
	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 *
	 * @covers Sensei_Block_View_Results::render
	 */
	public function testRender_Page_ReturnsEmptyString() {
		// Update the global post object ID to be the course ID, but change its post type to a page.
		$GLOBALS['post'] = (object) [
			'post_type' => 'page',
		];

		$result = $this->block->render( [], self::CONTENT );

		$this->assertEmpty( $result );
	}
}
