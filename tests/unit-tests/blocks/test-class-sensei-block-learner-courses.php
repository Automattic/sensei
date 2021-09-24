<?php

/**
 * Tests for Sensei_Learner_Courses_Block class.
 */
class Sensei_Block_Learner_Courses_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Learner Courses block.
	 *
	 * @var Sensei_Learner_Courses_Block
	 */
	private $block;


	/**
	 * Set up the test.
	 */
	public function setUp() {

		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->block     = new Sensei_Learner_Courses_Block();
		$this->course    = $this->factory->course->create_and_get( [ 'post_name' => 'learner-courses-block' ] );
		$GLOBALS['post'] = $this->course;

	}

	public function tearDown() {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/learner-courses' );
	}

	/**
	 * The Learner Courses block is registered and renders content.
	 */
	public function testBlockRegistered() {
		$post_content = '<!-- wp:sensei-lms/learner-courses --><!-- /wp:sensei-lms/learner-courses -->';

		$this->login_as_student();

		$result = do_blocks( $post_content );

		$this->assertContains( 'All', $result );
		$this->assertContains( 'Active', $result );
		$this->assertContains( 'Completed', $result );
	}

	/**
	 * Lists the learner's courses.
	 */
	public function testShowsUsersCourses() {
		$course  = $this->factory->course->create_and_get();
		$student = $this->factory->user->create();
		$this->login_as( $student );
		$this->manuallyEnrolStudentInCourse( $student, $course->ID );

		$result = $this->block->render( [], '' );

		$this->assertContains( $course->post_title, $result );

	}

}
