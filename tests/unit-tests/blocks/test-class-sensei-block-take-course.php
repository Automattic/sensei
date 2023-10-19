<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Block_Take_Course class.
 *
 * @group course-structure
 */
class Sensei_Block_Take_Course_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Take course block.
	 *
	 * @var Sensei_Block_Take_Course
	 */
	private $block;

	/**
	 * Current course.
	 *
	 * @var WP_Post
	 */
	private $course;

	/**
	 * Keep initial state of Sensei()->notices.
	 *
	 * @var Sensei_Notices|null
	 */
	private $initial_notices;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {

		parent::setUp();
		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		$this->block     = new Sensei_Block_Take_Course();
		$this->course    = $this->factory->course->create_and_get( [ 'post_name' => 'take-block-course' ] );
		$GLOBALS['post'] = $this->course;

		$this->initial_notices = Sensei()->notices;

	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/button-take-course' );

		Sensei()->notices = $this->initial_notices;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * The take course block is registered and renders content.
	 */
	public function testBlockRegistered() {
		$post_content = '<!-- wp:sensei-lms/button-take-course {"style":{"color":{"text":"#d03f3f"}},"className":"has-text-align-full is-style-outline"} -->
<div class="wp-block-sensei-lms-button-take-course has-text-align-full is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-text-color" style="color:#d03f3f">Take Course</button></div>
<!-- /wp:sensei-lms/button-take-course -->';

		$result = do_blocks( $post_content );

		$this->assertStringContainsString( '<form', $result );
		$this->assertStringContainsString( 'Take Course</button>', $result );
	}

	/**
	 * When there is an eligible user, returns the button with a form that allows the user to enroll in course.
	 */
	public function testEnrollFormWhenNotEnrolled() {

		$this->login_as_student();

		$result = do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );

		$form   = '/^\s*<form method="POST" action=".*\/\?course=take-block-course">.+<\/form>$/ms';
		$nonce  = '/<input type="hidden" id="woothemes_sensei_start_course_noonce" name="woothemes_sensei_start_course_noonce" value=".+" \/>/';
		$action = '<input type="hidden" name="course_start" value="1" />';

		$this->assertMatchesRegularExpression( $form, $result, 'Should be wrapped in a form tag' );
		$this->assertStringContainsString( $action, $result, 'Should have course_start action input field' );
		$this->assertMatchesRegularExpression( $nonce, $result, 'Should have nonce input field' );
		$this->assertStringContainsString( '<button class="sensei-stop-double-submission sensei-stop-double-submission wp-block-button__link">Take Course</button>', $result, 'Should contain block content' );

	}

	/**
	 * When the user is not logged in, the button links to the My Courses page to log in.
	 */
	public function testLoginPageLinkWhenNotLoggedIn() {

		// Create the My Courses page.
		$my_courses_page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'My Courses',
				'post_name'  => 'my-courses',
			]
		);
		Sensei()->settings->set( 'my_course_page', $my_courses_page_id );

		$this->logout();

		$result = do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );

		$form = '/^\s*<form method="GET" action=".*page_id=' . $my_courses_page_id . '">.+<\/form>\s*$/ms';

		$this->assertMatchesRegularExpression( $form, $result, 'Should be wrapped in a form tag' );
		$this->assertStringContainsString( '<button class="sensei-stop-double-submission wp-block-button__link">Take Course</button>', $result, 'Should contain block content' );
	}

	/**
	 * When the course has an unmet prerequisite, button is disabled with a message.
	 */
	public function testDisabledWhenPrerequisiteUnmet() {
		$GLOBALS['wp_query']->is_single = true;

		$property = new ReflectionProperty( 'Sensei_Notices', 'has_printed' );
		$property->setAccessible( true );
		$property->setValue( Sensei()->notices, false );

		$course_pre = $this->factory->course->create_and_get();
		add_post_meta( $this->course->ID, '_course_prerequisite', $course_pre->ID );

		$this->login_as_student();

		$result = do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );

		$this->assertStringContainsString( '<button disabled="disabled" class="sensei-stop-double-submission wp-block-button__link">Take Course</button>', $result, 'Button should be disabled' );

		ob_start();
		Sensei()->notices->maybe_print_notices();
		$actual_notices = ob_get_clean();
		$notice         = '/You must first complete <a .*>' . preg_quote( $course_pre->post_title, '/' ) . '<\/a> before taking this course/';

		$this->assertMatchesRegularExpression( $notice, $actual_notices, 'Should contain notice of the prerequisite course' );

	}

	/**
	 * No button is rendered when the user is already enrolled.
	 */
	public function testHiddenWhenUserEnrolled() {

		$student = $this->factory->user->create();
		$this->manuallyEnrolStudentInCourse( $student, $this->course->ID );

		$this->login_as( $student );

		$result = do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );

		$this->assertEmpty( $result );
	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 *
	 * @covers Sensei_Block_Take_Course::render_take_course_block
	 */
	public function testRenderTakeCourseBlock_Page_ReturnsEmptyString() {
		$GLOBALS['post'] = (object) [
			'ID'        => 0,
			'post_type' => 'page',
		];

		$result = do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );

		$this->assertEmpty( $result );
	}

	/**
	 * Self-Enrollment Not Allowed.
	 */
	public function testRenderTakeCourseBlock_WhenSelfEnrollmentIsNotAllowed_AddsNotice() {
		/* Arrange. */
		$GLOBALS['wp_query']->is_single = true;

		$this->login_as_student();
		update_post_meta( $this->course->ID, '_sensei_self_enrollment_not_allowed', true );

		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		/* Expect & Act */
		$notices->expects( self::once() )
			->method( 'add_notice' )
			->with( $this->stringContains( 'Please contact the course administrator to sign up for this course.' ) );
		do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );
	}

	/**
	 * Self-Enrollment Not In Course Page.
	 */
	public function testRenderTakeCourseBlock_WhenSelfEnrollmentIsNotAllowedAndNotInSinglePost_DoesNotAddNotice() {
		/* Arrange. */
		$GLOBALS['wp_query']->is_single = false;

		$this->login_as_student();
		update_post_meta( $this->course->ID, '_sensei_self_enrollment_not_allowed', true );

		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		/* Expect & Act */
		$notices->expects( self::never() )
			->method( 'add_notice' );
		do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );
	}

	/**
	 * Self-Enrollment Not Allowed And User Is Enrolled.
	 */
	public function testRenderTakeCourseBlock_WhenSelfEnrollmentIsNotAllowedAndUserIsEnrolled_DoesNotAddNotice() {
		/* Arrange. */
		$GLOBALS['wp_query']->is_single = true;

		$student = $this->factory->user->create();
		$this->manuallyEnrolStudentInCourse( $student, $this->course->ID );

		$this->login_as( $student );
		update_post_meta( $this->course->ID, '_sensei_self_enrollment_not_allowed', true );

		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		/* Expect & Act */
		$notices->expects( self::never() )
			->method( 'add_notice' );
		do_blocks( '<!-- wp:sensei-lms/button-take-course {"align":"right"} --><button class="sensei-stop-double-submission wp-block-button__link">Take Course</button><!-- /wp:sensei-lms/button-take-course -->' );
	}
}
