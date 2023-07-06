<?php
/**
 * File with class for testing Sensei Guest User.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Guest_Users class.
 *
 * @group Guest User
 */
class Sensei_Guest_User_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory    = new Sensei_Factory();
		$this->guest_user = new Sensei_Guest_User();
	}

	private function setup_course( $open_access ) {
		$course_data = $this->factory->get_course_with_lessons();
		$course_id   = $course_data['course_id'];
		update_post_meta( $course_id, '_open_access', $open_access );
		global $post;
		$post = get_post( $course_id );

		return $course_data;
	}

	/**
	 * Test if guest is created for take course requests when course is open access.
	 *
	 * @testWith [ true ]
	 *           [ false ]
	 *
	 * @param bool $open_access
	 */
	public function testGuestUserCreatedOnTakeCourseRequest( $open_access ) {

		$this->setup_course( $open_access );
		$this->logout();

		$_POST['course_start']                         = 1;
		$_POST['woothemes_sensei_start_course_noonce'] = wp_create_nonce( 'woothemes_sensei_start_course_noonce' );

		do_action( 'wp' );

		$this->assertEquals( is_user_logged_in(), $open_access );
		if ( $open_access ) {
			$this->assertRegexp( '/^sensei_guest_.*$/', wp_get_current_user()->user_login );
			$this->assertRegexp( '/^Guest Student 00.*$/', wp_get_current_user()->display_name );
		}

	}

	/**
	 * Test that a guest user is created, enrolled and completes lesson when clicking complete lesson.
	 */
	public function testGuestUserEnrolledOnCompleteLessonRequest() {

		[ 'course_id' => $course_id, 'lesson_ids' => [ $lesson_id ] ] = $this->setup_course( true );
		$this->logout();

		global $post;
		$post = get_post( $lesson_id );

		$_POST['quiz_action']                             = 'lesson-complete';
		$_POST['woothemes_sensei_complete_lesson_noonce'] = wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' );

		do_action( 'wp' );

		// Guest user is created and enrolled.
		$this->assertTrue( is_user_logged_in(), 'Guest user was not created' );
		$this->assertTrue( Sensei_Course::is_user_enrolled( $course_id, get_current_user_id() ), 'Guest user was not enrolled' );

		// The 'complete lesson' action is also executed.
		$this->assertTrue( Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() ), 'Lesson was not completed' );

	}

	/**
	 * Feature can be disabled with a filter.
	 */
	public function testFeatureDisabled() {

		add_filter( 'sensei_feature_open_access_courses', '__return_false' );

		$this->setup_course( true );
		$this->logout();

		$_POST['course_start']                         = 1;
		$_POST['woothemes_sensei_start_course_noonce'] = wp_create_nonce( 'woothemes_sensei_start_course_noonce' );

		do_action( 'wp' );

		$this->assertEquals( false, is_user_logged_in(), 'Logged out user should not be able to start course when feature is disabled.' );

	}

	/**
	 * Feature can be disabled for a single course with a filter.
	 */
	public function testOpenAccessDisabledForCourse() {

		[ 'course_id' => $course_id ] = $this->setup_course( true );
		$this->logout();

		add_filter(
			'sensei_course_open_access',
			function( $is_open_access, $filtered_course_id ) use ( $course_id ) {
				return ( $course_id === $filtered_course_id ) ? false : $is_open_access;
			},
			10,
			2
		);

		$_POST['course_start']                         = 1;
		$_POST['woothemes_sensei_start_course_noonce'] = wp_create_nonce( 'woothemes_sensei_start_course_noonce' );

		do_action( 'wp' );

		$this->assertEquals( false, is_user_logged_in(), 'Logged out user should not be able to start course when open access is disabled for the course.' );

	}

	public function testSkipWpMail_ReturnIsTrueAndHasGuestUser_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => [ 'user1@example.com', 'user2@guest.senseilms' ],
			'headers' => [
				'Cc'       => 'user3@example.com,user4@example.com',
				'Bcc'      => 'user5@example.com,user6@example.com',
				'Reply-To' => 'user7@example.com,user8@example.com',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( true, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_HasGuestUserInTo_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com,user2@guest.senseilms',
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_HasGuestUserInCc_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'Cc' => 'user2@guest.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}


	public function testSkipWpMail_HasGuestUserInBcc_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'Bcc' => 'user2@guest.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_HasGuestUserInReplyTo_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'Reply-To' => 'user2@guest.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}


	public function testSkipWpMail_HasGuestUserInFrom_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'From' => 'User 2 <user2@guest.senseilms>',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}


	public function testSkipWpMail_HeadersPassedAsString_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => "Cc: user3@example.com,user4@guest.senseilms\r\nBcc: user5@example.com,user6@example.com\r\nReply-To: user7@example.com,user8@example.com",
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_EmailsArentGuestUsers_ReturnsAtts() {
		// Arrange
		$atts = [
			'to'      => [ 'user1@example.com', 'user2@example.com' ],
			'headers' => [
				'Cc'       => 'user3@example.com,user4@example.com',
				'Bcc'      => 'user5@example.com,user6@example.com',
				'Reply-To' => 'user7@example.com,user8@example.com',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertNull( $result );
	}


	public function testSkipWpMail_ChecksOnlyCcBccReplyToAndFromHeaders_ReturnsNull() {
		// Arrange
		$atts = [
			'to'      => [ 'user1@example.com', 'user2@example.com' ],
			'headers' => [
				'Cc'         => 'User 3 <user3@example.com>,user4@example.com',
				'Bcc'        => 'user5@example.com,User 6 <user6@example.com>',
				'Reply-To'   => 'user7@example.com,user8@example.com',
				'From'       => 'User 9 <user9@example.com>',
				'X-Reply-To' => 'user10@guest.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertNull( $result );
	}

	public function testSkipWpMail_SingleToWithGuestEmail_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'John Doe <user1@guest.senseilms>',
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_SingleToWithValidEmail_ReturnsNull() {
		// Arrange
		$atts = [
			'to'      => 'John Doe <user1@example.com>',
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->guest_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertNull( $result );
	}

}
